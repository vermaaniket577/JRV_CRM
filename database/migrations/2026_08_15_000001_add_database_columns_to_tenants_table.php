<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('tenants', function (Blueprint $table) {
            if (!Schema::hasColumn('tenants', 'database_name')) {
                $table->string('database_name')->nullable()->after('status');
            }
            if (!Schema::hasColumn('tenants', 'database_host')) {
                $table->string('database_host')->default('127.0.0.1')->after('database_name');
            }
            if (!Schema::hasColumn('tenants', 'database_port')) {
                $table->string('database_port')->default('3306')->after('database_host');
            }
            if (!Schema::hasColumn('tenants', 'database_username')) {
                $table->string('database_username')->default('root')->after('database_port');
            }
            if (!Schema::hasColumn('tenants', 'database_password')) {
                $table->string('database_password')->nullable()->after('database_username');
            }
            if (!Schema::hasColumn('tenants', 'database_status')) {
                $table->string('database_status')->default('pending')->after('database_password');
            }
            if (!Schema::hasColumn('tenants', 'database_created_at')) {
                $table->timestamp('database_created_at')->nullable()->after('database_status');
            }
            if (!Schema::hasColumn('tenants', 'selected_columns_meta')) {
                $table->json('selected_columns_meta')->nullable()->after('database_created_at');
            }
        });
    }

    public function down(): void
    {
        Schema::table('tenants', function (Blueprint $table) {
            $table->dropColumn([
                'database_name',
                'database_host',
                'database_port',
                'database_username',
                'database_password',
                'database_status',
                'database_created_at',
                'selected_columns_meta',
            ]);
        });
    }
};
