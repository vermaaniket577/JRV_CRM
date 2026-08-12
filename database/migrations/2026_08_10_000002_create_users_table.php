<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('users')) {
            Schema::table('users', function (Blueprint $table) {
                if (!Schema::hasColumn('users', 'tenant_id')) {
                    $table->foreignId('tenant_id')->nullable()->after('id')->constrained('tenants')->cascadeOnDelete();
                    $table->boolean('is_super_admin')->default(false)->after('password')->index();
                    $table->boolean('is_tenant_admin')->default(false)->after('is_super_admin')->index();
                    $table->enum('status', ['active', 'inactive', 'invited'])->default('active')->after('is_tenant_admin')->index();
                    $table->index(['tenant_id', 'email'], 'idx_users_tenant_email');
                }
            });
        } else {
            Schema::create('users', function (Blueprint $table) {
                $table->id();
                $table->foreignId('tenant_id')->nullable()->constrained('tenants')->cascadeOnDelete();
                $table->string('name');
                $table->string('email')->index();
                $table->string('password');
                $table->boolean('is_super_admin')->default(false)->index();
                $table->boolean('is_tenant_admin')->default(false)->index();
                $table->enum('status', ['active', 'inactive', 'invited'])->default('active')->index();
                $table->rememberToken();
                $table->timestamps();
                $table->softDeletes();

                $table->index(['tenant_id', 'email'], 'idx_users_tenant_email');
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('users');
    }
};
