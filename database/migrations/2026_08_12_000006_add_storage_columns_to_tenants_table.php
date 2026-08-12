<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('tenants') && !Schema::hasColumn('tenants', 'storage_limit_mb')) {
            Schema::table('tenants', function (Blueprint $table) {
                $table->integer('storage_limit_mb')->default(5120)->after('status'); // 5 GB default trial limit
                $table->float('storage_used_mb')->default(1250.0)->after('storage_limit_mb'); // Current storage used in MB
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('tenants') && Schema::hasColumn('tenants', 'storage_limit_mb')) {
            Schema::table('tenants', function (Blueprint $table) {
                $table->dropColumn(['storage_limit_mb', 'storage_used_mb']);
            });
        }
    }
};
