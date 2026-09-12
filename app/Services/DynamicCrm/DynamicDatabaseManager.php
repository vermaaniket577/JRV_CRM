<?php

namespace App\Services\DynamicCrm;

use App\Models\Tenant;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * Runtime database connection manager for tenant-isolated dynamic CRM databases.
 * Creates, connects, and manages dedicated MySQL databases per tenant.
 */
class DynamicDatabaseManager
{
    protected ?string $originalDatabase = null;

    /**
     * Ensure a dedicated database exists for the tenant. Returns the database name.
     */
    public function ensureTenantDatabase(Tenant $tenant): string
    {
        $dbName = $this->getTenantDatabaseName($tenant);

        // Create database if it doesn't exist
        try {
            DB::statement("CREATE DATABASE IF NOT EXISTS `{$dbName}` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
        } catch (\Throwable $e) {
            Log::warning("Could not create database `{$dbName}`: " . $e->getMessage());
            // Fall back to main database
            return config('database.connections.mysql.database', 'jrv_crm');
        }

        // Update tenant record
        if ($tenant->database_name !== $dbName) {
            $tenant->update([
                'database_name' => $dbName,
                'database_status' => 'active',
                'database_created_at' => now(),
            ]);
        }

        return $dbName;
    }

    /**
     * Get the database name for a tenant (deterministic).
     */
    public function getTenantDatabaseName(Tenant $tenant): string
    {
        if (!empty($tenant->database_name)) {
            return $tenant->database_name;
        }

        $cleanSub = preg_replace('/[^a-zA-Z0-9_]/', '_', strtolower($tenant->subdomain ?: ($tenant->slug ?: 'crm')));
        $cleanSub = substr($cleanSub, 0, 20);
        return "crm_tenant_{$tenant->id}_{$cleanSub}";
    }

    /**
     * Set up the dynamic_crm database connection for a tenant and connect.
     */
    public function connectToTenantDb(Tenant $tenant): string
    {
        $dbName = $this->getTenantDatabaseName($tenant);

        // Configure the dynamic connection at runtime
        config([
            'database.connections.dynamic_crm' => array_merge(
                config('database.connections.mysql'),
                [
                    'database' => $dbName,
                    'read' => ['host' => [config('database.connections.mysql.host', '127.0.0.1')]],
                    'write' => ['host' => [config('database.connections.mysql.host', '127.0.0.1')]],
                ]
            ),
        ]);

        // Purge any existing connection and reconnect
        DB::purge('dynamic_crm');
        DB::reconnect('dynamic_crm');

        return $dbName;
    }

    /**
     * Get the query builder for a table in the tenant's database.
     */
    public function table(Tenant $tenant, string $tableName)
    {
        $this->connectToTenantDb($tenant);
        return DB::connection('dynamic_crm')->table($tableName);
    }

    /**
     * Run a raw SELECT query on the tenant's database.
     */
    public function select(Tenant $tenant, string $query, array $bindings = []): array
    {
        $this->connectToTenantDb($tenant);
        return DB::connection('dynamic_crm')->select($query, $bindings);
    }

    /**
     * Execute a raw statement on the tenant's database.
     */
    public function statement(Tenant $tenant, string $query, array $bindings = []): bool
    {
        $this->connectToTenantDb($tenant);
        return DB::connection('dynamic_crm')->statement($query, $bindings);
    }

    /**
     * Get a qualified table name for cross-database queries from the main connection.
     */
    public function qualifiedTableName(Tenant $tenant, string $tableName): string
    {
        $mainDb = config('database.connections.mysql.database', 'jrv_crm');
        $tenantDb = $this->getTenantDatabaseName($tenant);

        if ($tenantDb !== $mainDb) {
            return "`{$tenantDb}`.`{$tableName}`";
        }

        return "`{$tableName}`";
    }

    /**
     * Check if a specific table exists in the tenant's database.
     */
    public function tableExists(Tenant $tenant, string $tableName): bool
    {
        $dbName = $this->getTenantDatabaseName($tenant);

        try {
            $result = DB::select(
                "SELECT TABLE_NAME FROM INFORMATION_SCHEMA.TABLES WHERE TABLE_SCHEMA = ? AND TABLE_NAME = ?",
                [$dbName, $tableName]
            );
            return !empty($result);
        } catch (\Throwable $e) {
            return false;
        }
    }

    /**
     * Get all table names in the tenant's database.
     */
    public function listTables(Tenant $tenant): array
    {
        $dbName = $this->getTenantDatabaseName($tenant);

        try {
            $results = DB::select(
                "SELECT TABLE_NAME FROM INFORMATION_SCHEMA.TABLES WHERE TABLE_SCHEMA = ? ORDER BY TABLE_NAME",
                [$dbName]
            );
            return array_map(fn($r) => $r->TABLE_NAME, $results);
        } catch (\Throwable $e) {
            return [];
        }
    }

    /**
     * Get column info for a table from INFORMATION_SCHEMA.
     */
    public function getTableColumns(Tenant $tenant, string $tableName): array
    {
        $dbName = $this->getTenantDatabaseName($tenant);

        try {
            return DB::select(
                "SELECT COLUMN_NAME, DATA_TYPE, COLUMN_TYPE, IS_NULLABLE, COLUMN_DEFAULT, COLUMN_KEY, EXTRA
                 FROM INFORMATION_SCHEMA.COLUMNS
                 WHERE TABLE_SCHEMA = ? AND TABLE_NAME = ?
                 ORDER BY ORDINAL_POSITION",
                [$dbName, $tableName]
            );
        } catch (\Throwable $e) {
            return [];
        }
    }

    /**
     * Check if the tenant's database is reachable.
     */
    public function isDatabaseReachable(Tenant $tenant): bool
    {
        $dbName = $this->getTenantDatabaseName($tenant);

        try {
            $result = DB::select("SELECT SCHEMA_NAME FROM INFORMATION_SCHEMA.SCHEMATA WHERE SCHEMA_NAME = ?", [$dbName]);
            return !empty($result);
        } catch (\Throwable $e) {
            return false;
        }
    }

    /**
     * Purge the dynamic connection to free resources.
     */
    public function disconnect(): void
    {
        try {
            DB::purge('dynamic_crm');
        } catch (\Throwable $e) {
            // Silent
        }
    }
}
