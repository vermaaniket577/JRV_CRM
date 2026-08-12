<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('tenants', function (Blueprint $table) {
            $table->foreignId('industry_id')->nullable()->after('uuid')->constrained('industries')->nullOnDelete();
            $table->foreignId('business_type_id')->nullable()->after('industry_id')->constrained('business_types')->nullOnDelete();
            $table->string('employee_range', 20)->nullable()->after('business_type_id');
            $table->json('crm_goals')->nullable()->after('employee_range');
            $table->boolean('onboarding_completed')->default(false)->after('crm_goals');
        });
    }

    public function down(): void
    {
        Schema::table('tenants', function (Blueprint $table) {
            $table->dropForeign(['industry_id']);
            $table->dropForeign(['business_type_id']);
            $table->dropColumn(['industry_id', 'business_type_id', 'employee_range', 'crm_goals', 'onboarding_completed']);
        });
    }
};
