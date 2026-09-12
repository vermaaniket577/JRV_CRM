<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // ── Uploaded database registry ──
        Schema::create('crm_databases', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('tenant_id');
            $table->string('name');                        // Human-readable label
            $table->string('database_name');                // Actual MySQL database name
            $table->string('original_file')->nullable();    // Uploaded filename
            $table->unsignedBigInteger('file_size_bytes')->default(0);
            $table->string('status')->default('active');    // active | importing | failed
            $table->unsignedInteger('tables_count')->default(0);
            $table->unsignedInteger('relationships_count')->default(0);
            $table->unsignedBigInteger('total_records')->default(0);
            $table->json('import_summary')->nullable();     // Detailed import stats
            $table->timestamps();

            $table->foreign('tenant_id')->references('id')->on('tenants')->onDelete('cascade');
            $table->index(['tenant_id', 'status']);
        });

        // ── Discovered table metadata ──
        Schema::create('crm_tables', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('database_id');
            $table->unsignedBigInteger('tenant_id');
            $table->string('table_name');                   // Actual MySQL table name
            $table->string('display_name');                 // Pretty label for UI
            $table->string('icon')->default('TableCellsIcon');
            $table->boolean('is_active')->default(true);
            $table->boolean('is_visible_in_menu')->default(true);
            $table->unsignedInteger('menu_order')->default(0);
            $table->unsignedBigInteger('record_count')->default(0);
            $table->string('primary_key_column')->default('id');
            $table->string('display_column')->nullable();   // e.g. 'name', 'title' — used when referenced by FK
            $table->timestamps();

            $table->foreign('database_id')->references('id')->on('crm_databases')->onDelete('cascade');
            $table->foreign('tenant_id')->references('id')->on('tenants')->onDelete('cascade');
            $table->unique(['database_id', 'table_name']);
            $table->index(['tenant_id', 'is_active']);
        });

        // ── Column metadata per table ──
        Schema::create('crm_columns', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('table_id');
            $table->unsignedBigInteger('tenant_id');
            $table->string('column_name');                  // Actual MySQL column name
            $table->string('display_name');                 // Pretty label for UI
            $table->string('data_type')->default('varchar');// MySQL type: varchar, int, text, enum, etc.
            $table->string('form_type')->default('text');   // UI type: text, textarea, number, date, select, checkbox
            $table->boolean('is_primary')->default(false);
            $table->boolean('is_auto_increment')->default(false);
            $table->boolean('is_nullable')->default(true);
            $table->boolean('is_searchable')->default(false);
            $table->boolean('is_visible')->default(true);
            $table->boolean('is_editable')->default(true);
            $table->boolean('is_required')->default(false);
            $table->boolean('is_foreign_key')->default(false);
            $table->string('references_table')->nullable(); // FK target table
            $table->string('references_column')->nullable();// FK target column
            $table->string('display_column')->nullable();   // FK display column (e.g. 'name')
            $table->string('default_value')->nullable();
            $table->json('enum_values')->nullable();        // For ENUM/SET columns
            $table->unsignedInteger('display_order')->default(0);
            $table->timestamps();

            $table->foreign('table_id')->references('id')->on('crm_tables')->onDelete('cascade');
            $table->foreign('tenant_id')->references('id')->on('tenants')->onDelete('cascade');
            $table->unique(['table_id', 'column_name']);
        });

        // ── Detected relationships ──
        Schema::create('crm_relationships', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('database_id');
            $table->unsignedBigInteger('tenant_id');
            $table->string('source_table');
            $table->string('source_column');
            $table->string('target_table');
            $table->string('target_column');
            $table->string('relationship_type')->default('belongsTo'); // belongsTo, hasMany, hasOne
            $table->string('display_label')->nullable();    // Human label for UI
            $table->timestamps();

            $table->foreign('database_id')->references('id')->on('crm_databases')->onDelete('cascade');
            $table->foreign('tenant_id')->references('id')->on('tenants')->onDelete('cascade');
            $table->index(['tenant_id', 'source_table']);
            $table->index(['tenant_id', 'target_table']);
        });

        // ── Audit log ──
        Schema::create('crm_activity_logs', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('tenant_id');
            $table->unsignedBigInteger('user_id')->nullable();
            $table->string('action');           // created, updated, deleted, uploaded, searched
            $table->string('table_name')->nullable();
            $table->unsignedBigInteger('record_id')->nullable();
            $table->json('changes')->nullable();
            $table->string('ip_address')->nullable();
            $table->timestamps();

            $table->foreign('tenant_id')->references('id')->on('tenants')->onDelete('cascade');
            $table->index(['tenant_id', 'created_at']);
            $table->index(['tenant_id', 'table_name']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('crm_activity_logs');
        Schema::dropIfExists('crm_relationships');
        Schema::dropIfExists('crm_columns');
        Schema::dropIfExists('crm_tables');
        Schema::dropIfExists('crm_databases');
    }
};
