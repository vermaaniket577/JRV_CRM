<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // 1. Database Backups Table
        if (!Schema::hasTable('database_backups')) {
            Schema::create('database_backups', function (Blueprint $table) {
                $table->id();
                $table->string('backup_name');
                $table->string('backup_path');
                $table->bigInteger('file_size')->default(0);
                $table->foreignId('admin_id')->nullable()->constrained('users')->nullOnDelete();
                $table->string('admin_email')->nullable();
                $table->string('source_filename')->nullable();
                $table->string('database_version')->nullable();
                $table->enum('status', ['completed', 'failed', 'restored'])->default('completed');
                $table->json('metadata')->nullable();
                $table->timestamps();
            });
        }

        // 2. Database Imports Table
        if (!Schema::hasTable('database_imports')) {
            Schema::create('database_imports', function (Blueprint $table) {
                $table->id();
                $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
                $table->string('file_name');
                $table->string('file_path');
                $table->bigInteger('file_size')->default(0);
                $table->enum('status', [
                    'PENDING',
                    'ANALYZING',
                    'READY',
                    'IMPORTING',
                    'COMPLETED',
                    'PARTIAL',
                    'FAILED',
                    'ROLLED_BACK',
                ])->default('PENDING');
                $table->enum('import_mode', [
                    'insert_only',
                    'update_existing',
                    'upsert',
                    'skip_duplicates',
                ])->default('upsert');
                $table->integer('tables_detected')->default(0);
                $table->integer('columns_detected')->default(0);
                $table->integer('records_detected')->default(0);
                $table->integer('records_inserted')->default(0);
                $table->integer('records_updated')->default(0);
                $table->integer('records_skipped')->default(0);
                $table->integer('records_failed')->default(0);
                $table->json('schema_changes_summary')->nullable();
                $table->foreignId('backup_id')->nullable()->constrained('database_backups')->nullOnDelete();
                $table->timestamp('started_at')->nullable();
                $table->timestamp('completed_at')->nullable();
                $table->text('error_message')->nullable();
                $table->timestamps();
            });
        }

        // 3. Database Import Tables
        if (!Schema::hasTable('database_import_tables')) {
            Schema::create('database_import_tables', function (Blueprint $table) {
                $table->id();
                $table->foreignId('database_import_id')->constrained('database_imports')->cascadeOnDelete();
                $table->string('table_name');
                $table->string('target_table_name')->nullable();
                $table->enum('status', ['NEW', 'EXISTS', 'MODIFIED', 'IDENTICAL', 'CONFLICT'])->default('NEW');
                $table->enum('action', ['create', 'update', 'add_columns', 'ignore', 'custom_map'])->default('create');
                $table->integer('records_count')->default(0);
                $table->integer('columns_count')->default(0);
                $table->longText('create_statement')->nullable();
                $table->string('primary_key')->nullable();
                $table->boolean('is_dangerous')->default(false);
                $table->boolean('requires_approval')->default(false);
                $table->boolean('is_approved')->default(false);
                $table->timestamps();
            });
        }

        // 4. Database Import Columns
        if (!Schema::hasTable('database_import_columns')) {
            Schema::create('database_import_columns', function (Blueprint $table) {
                $table->id();
                $table->foreignId('database_import_table_id')->constrained('database_import_tables')->cascadeOnDelete();
                $table->string('column_name');
                $table->string('data_type');
                $table->boolean('is_nullable')->default(true);
                $table->boolean('is_primary')->default(false);
                $table->boolean('is_auto_increment')->default(false);
                $table->string('default_value')->nullable();
                $table->enum('status', ['NEW', 'EXISTS', 'MODIFIED', 'IDENTICAL', 'CONFLICT'])->default('NEW');
                $table->string('existing_data_type')->nullable();
                $table->boolean('existing_nullable')->nullable();
                $table->string('existing_default')->nullable();
                $table->enum('action', ['add', 'modify', 'keep', 'ignore', 'review'])->default('add');
                $table->boolean('requires_approval')->default(false);
                $table->boolean('is_approved')->default(false);
                $table->timestamps();
            });
        }

        // 5. Database Field Mappings
        if (!Schema::hasTable('database_field_mappings')) {
            Schema::create('database_field_mappings', function (Blueprint $table) {
                $table->id();
                $table->foreignId('database_import_id')->constrained('database_imports')->cascadeOnDelete();
                $table->foreignId('database_import_table_id')->nullable()->constrained('database_import_tables')->cascadeOnDelete();
                $table->string('source_table');
                $table->string('target_table');
                $table->string('source_column');
                $table->string('target_column');
                $table->enum('confidence', ['high', 'medium', 'low', 'custom'])->default('high');
                $table->decimal('confidence_score', 4, 2)->default(1.00);
                $table->boolean('is_confirmed')->default(true);
                $table->string('transformation_rule')->nullable();
                $table->timestamps();
            });
        }

        // 6. Database Import Errors
        if (!Schema::hasTable('database_import_errors')) {
            Schema::create('database_import_errors', function (Blueprint $table) {
                $table->id();
                $table->foreignId('database_import_id')->constrained('database_imports')->cascadeOnDelete();
                $table->string('table_name');
                $table->integer('row_number')->nullable();
                $table->string('column_name')->nullable();
                $table->json('raw_data')->nullable();
                $table->text('error_message');
                $table->timestamp('created_at')->useCurrent();
            });
        }

        // 7. Database Audit Logs
        if (!Schema::hasTable('database_audit_logs')) {
            Schema::create('database_audit_logs', function (Blueprint $table) {
                $table->id();
                $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
                $table->string('user_email')->nullable();
                $table->string('action'); // CREATE TABLE, ADD COLUMN, MODIFY COLUMN, IMPORT DATA, RESTORE BACKUP
                $table->string('table_name')->nullable();
                $table->string('column_name')->nullable();
                $table->text('previous_value')->nullable();
                $table->text('new_value')->nullable();
                $table->json('metadata')->nullable();
                $table->string('ip_address', 45)->nullable();
                $table->timestamp('created_at')->useCurrent();
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('database_audit_logs');
        Schema::dropIfExists('database_import_errors');
        Schema::dropIfExists('database_field_mappings');
        Schema::dropIfExists('database_import_columns');
        Schema::dropIfExists('database_import_tables');
        Schema::dropIfExists('database_imports');
        Schema::dropIfExists('database_backups');
    }
};
