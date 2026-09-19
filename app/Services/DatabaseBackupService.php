<?php

namespace App\Services;

use App\Models\DatabaseAuditLog;
use App\Models\DatabaseBackup;
use Exception;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Log;

class DatabaseBackupService
{
    /**
     * Backup directory path.
     */
    protected string $backupDir;

    public function __construct()
    {
        $this->backupDir = base_path('database_backups');
        if (!File::isDirectory($this->backupDir)) {
            File::makeDirectory($this->backupDir, 0755, true);
        }
    }

    /**
     * Create an automated backup before import.
     */
    public function createBackup(?int $adminId = null, ?string $adminEmail = null, ?string $sourceFilename = null): DatabaseBackup
    {
        $timestamp = now()->format('Y_m_d_His');
        $randomSeq = str_pad((string) mt_rand(1, 999), 3, '0', STR_PAD_LEFT);
        $backupName = "backup_{$timestamp}_{$randomSeq}.sql";
        $backupPath = $this->backupDir . DIRECTORY_SEPARATOR . $backupName;

        try {
            // Generate SQL dump of current database tables
            $this->generateDatabaseDump($backupPath);

            $fileSize = File::exists($backupPath) ? File::size($backupPath) : 0;
            $dbVersion = DB::selectOne("SELECT VERSION() as v")?->v ?? 'MySQL 8.x';

            $backup = DatabaseBackup::create([
                'backup_name' => $backupName,
                'backup_path' => $backupPath,
                'file_size' => $fileSize,
                'admin_id' => $adminId ?? auth()->id(),
                'admin_email' => $adminEmail ?? auth()->user()?->email ?? 'admin@crm.internal',
                'source_filename' => $sourceFilename,
                'database_version' => $dbVersion,
                'status' => 'completed',
                'metadata' => [
                    'generated_at' => now()->toIso8601String(),
                    'tables_backed_up' => count($this->getDatabaseTables()),
                ],
            ]);

            DatabaseAuditLog::record(
                action: 'BACKUP CREATED',
                newValue: "Created database backup snapshot {$backupName} ({$fileSize} bytes)",
                metadata: ['backup_id' => $backup->id, 'path' => $backupPath]
            );

            return $backup;
        } catch (\Throwable $e) {
            Log::error("DatabaseBackupService failed: " . $e->getMessage());

            return DatabaseBackup::create([
                'backup_name' => $backupName,
                'backup_path' => $backupPath,
                'file_size' => 0,
                'admin_id' => $adminId ?? auth()->id(),
                'admin_email' => $adminEmail ?? auth()->user()?->email ?? 'admin@crm.internal',
                'source_filename' => $sourceFilename,
                'database_version' => 'MySQL',
                'status' => 'failed',
                'metadata' => ['error' => $e->getMessage()],
            ]);
        }
    }

    /**
     * Restore database from backup snapshot (Rollback mechanism).
     */
    public function rollback(DatabaseBackup $backup): bool
    {
        if (!File::exists($backup->backup_path)) {
            throw new Exception("Backup file not found at {$backup->backup_path}");
        }

        $sqlContent = File::get($backup->backup_path);
        if (empty($sqlContent)) {
            throw new Exception("Backup file is empty.");
        }

        try {
            DB::statement("SET FOREIGN_KEY_CHECKS=0;");
            DB::unprepared($sqlContent);
            DB::statement("SET FOREIGN_KEY_CHECKS=1;");

            $backup->update(['status' => 'restored']);

            DatabaseAuditLog::record(
                action: 'DATABASE RESTORED',
                newValue: "Restored database from snapshot {$backup->backup_name}",
                metadata: ['backup_id' => $backup->id]
            );

            return true;
        } catch (\Throwable $e) {
            DB::statement("SET FOREIGN_KEY_CHECKS=1;");
            Log::error("DatabaseBackupService rollback failed: " . $e->getMessage());
            throw $e;
        }
    }


    /**
     * Pure PHP MySQL dump generator (Environment agnostic, runs reliably on Windows/Linux).
     */
    protected function generateDatabaseDump(string $outputPath): void
    {
        $handle = fopen($outputPath, 'w');
        if (!$handle) {
            throw new Exception("Cannot create backup file at {$outputPath}");
        }

        $header = "-- ========================================================\n"
            . "-- JRV CRM Automated Database Backup Snapshot\n"
            . "-- Generated: " . date('Y-m-d H:i:s') . "\n"
            . "-- Host: " . config('database.connections.mysql.host') . "\n"
            . "-- Database: " . DB::getDatabaseName() . "\n"
            . "-- ========================================================\n\n"
            . "SET FOREIGN_KEY_CHECKS=0;\n\n";

        fwrite($handle, $header);

        $tables = $this->getDatabaseTables();

        foreach ($tables as $table) {
            // Do not back up audit logs or backups table itself to save space/recursion
            if (in_array($table, ['database_backups', 'database_audit_logs', 'sessions'])) {
                continue;
            }

            // 1. Table structure
            $createTableStmt = DB::selectOne("SHOW CREATE TABLE `{$table}`");
            if ($createTableStmt) {
                $createProperty = 'Create Table';
                $createSql = $createTableStmt->$createProperty ?? (array_values((array)$createTableStmt)[1] ?? '');
                if (!empty($createSql)) {
                    fwrite($handle, "DROP TABLE IF EXISTS `{$table}`;\n");
                    fwrite($handle, $createSql . ";\n\n");
                }
            }

            // 2. Table records in chunks
            $count = DB::table($table)->count();
            if ($count > 0) {
                DB::table($table)->orderBy(DB::raw('1'))->chunk(200, function ($rows) use ($handle, $table) {
                    $insertSql = "INSERT INTO `{$table}` VALUES ";
                    $valueTuples = [];

                    foreach ($rows as $row) {
                        $tuple = [];
                        foreach ((array)$row as $val) {
                            if ($val === null) {
                                $tuple[] = "NULL";
                            } elseif (is_numeric($val)) {
                                $tuple[] = $val;
                            } else {
                                $tuple[] = "'" . addslashes((string)$val) . "'";
                            }
                        }
                        $valueTuples[] = "(" . implode(', ', $tuple) . ")";
                    }

                    $insertSql .= implode(', ', $valueTuples) . ";\n";
                    fwrite($handle, $insertSql);
                });
                fwrite($handle, "\n");
            }
        }

        fwrite($handle, "SET FOREIGN_KEY_CHECKS=1;\n");
        fclose($handle);
    }

    /**
     * Get list of database table names.
     */
    protected function getDatabaseTables(): array
    {
        $dbName = DB::getDatabaseName();
        $tables = DB::select("SHOW TABLES");
        $property = "Tables_in_{$dbName}";

        $names = [];
        foreach ($tables as $row) {
            $arr = (array)$row;
            $names[] = $arr[$property] ?? reset($arr);
        }
        return $names;
    }
}
