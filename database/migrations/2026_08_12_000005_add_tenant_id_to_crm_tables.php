<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('contacts') && !Schema::hasColumn('contacts', 'tenant_id')) {
            Schema::table('contacts', function (Blueprint $table) {
                $table->foreignId('tenant_id')->nullable()->after('id')->constrained('tenants')->nullOnDelete();
            });
        }

        if (Schema::hasTable('companies') && !Schema::hasColumn('companies', 'tenant_id')) {
            Schema::table('companies', function (Blueprint $table) {
                $table->foreignId('tenant_id')->nullable()->after('id')->constrained('tenants')->nullOnDelete();
            });
        }

        if (Schema::hasTable('pipelines') && !Schema::hasColumn('pipelines', 'tenant_id')) {
            Schema::table('pipelines', function (Blueprint $table) {
                $table->foreignId('tenant_id')->nullable()->after('id')->constrained('tenants')->nullOnDelete();
            });
        }

        if (Schema::hasTable('deal_stages') && !Schema::hasColumn('deal_stages', 'tenant_id')) {
            Schema::table('deal_stages', function (Blueprint $table) {
                $table->foreignId('tenant_id')->nullable()->after('id')->constrained('tenants')->nullOnDelete();
            });
        }

        if (Schema::hasTable('deals') && !Schema::hasColumn('deals', 'tenant_id')) {
            Schema::table('deals', function (Blueprint $table) {
                $table->foreignId('tenant_id')->nullable()->after('id')->constrained('tenants')->nullOnDelete();
            });
        }
    }

    public function down(): void
    {
        foreach (['contacts', 'companies', 'pipelines', 'deal_stages', 'deals'] as $tableName) {
            if (Schema::hasTable($tableName) && Schema::hasColumn($tableName, 'tenant_id')) {
                Schema::table($tableName, function (Blueprint $table) {
                    $table->dropColumn('tenant_id');
                });
            }
        }
    }
};
