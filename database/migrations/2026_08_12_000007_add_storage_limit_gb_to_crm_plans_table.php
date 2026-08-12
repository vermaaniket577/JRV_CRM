<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('crm_plans') && !Schema::hasColumn('crm_plans', 'storage_limit_gb')) {
            Schema::table('crm_plans', function (Blueprint $table) {
                $table->integer('storage_limit_gb')->default(25)->after('max_contacts');
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('crm_plans') && Schema::hasColumn('crm_plans', 'storage_limit_gb')) {
            Schema::table('crm_plans', function (Blueprint $table) {
                $table->dropColumn(['storage_limit_gb']);
            });
        }
    }
};
