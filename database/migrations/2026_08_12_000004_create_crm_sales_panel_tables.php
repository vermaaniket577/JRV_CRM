<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('crm_plans', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('slug')->unique();
            $table->decimal('price_monthly', 10, 2);
            $table->decimal('price_annual', 10, 2);
            $table->integer('max_users')->default(5);
            $table->integer('max_contacts')->default(1000);
            $table->json('features')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        Schema::create('crm_subscriptions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained('tenants')->onDelete('cascade');
            $table->foreignId('crm_plan_id')->constrained('crm_plans')->onDelete('cascade');
            $table->string('status')->default('active'); // active, pending, canceled, refunded
            $table->string('billing_cycle')->default('monthly'); // monthly, annual
            $table->timestamp('starts_at')->nullable();
            $table->timestamp('ends_at')->nullable();
            $table->timestamps();
        });

        Schema::create('crm_transactions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->nullable()->constrained('tenants')->onDelete('cascade');
            $table->foreignId('subscription_id')->nullable();
            $table->string('transaction_code')->unique();
            $table->string('customer_name');
            $table->string('customer_email');
            $table->string('plan_tier'); // Starter, Pro, Enterprise
            $table->decimal('amount', 10, 2);
            $table->string('payment_status')->default('Paid'); // Paid, Pending, Refunded
            $table->string('payment_method')->default('Credit Card');
            $table->timestamp('purchase_date')->useCurrent();
            $table->timestamps();
        });

        Schema::create('crm_sales_leads', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->nullable();
            $table->string('customer_name');
            $table->string('company_name');
            $table->string('email');
            $table->string('phone')->nullable();
            $table->string('industry')->default('Technology');
            $table->string('deal_stage')->default('New'); // New, Qualified, Proposal, Won, Lost
            $table->decimal('estimated_mrr', 10, 2)->default(0.00);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('crm_sales_leads');
        Schema::dropIfExists('crm_transactions');
        Schema::dropIfExists('crm_subscriptions');
        Schema::dropIfExists('crm_plans');
    }
};
