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
        if (!Schema::hasTable('payment_plans')) {
            Schema::create('payment_plans', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('tenant_id')->default(7)->index();
                $table->unsignedBigInteger('contact_id')->nullable()->index();
                $table->unsignedBigInteger('user_id')->nullable()->index(); // Created by user
                $table->string('invoice_number')->unique();
                $table->string('customer_name');
                $table->string('customer_email');
                $table->string('customer_phone');
                $table->string('title');
                $table->decimal('total_amount', 12, 2)->default(0.00);
                $table->string('currency', 10)->default('INR');
                $table->enum('plan_type', ['one_time', 'installments'])->default('one_time');
                $table->enum('status', ['pending', 'partially_paid', 'paid', 'overdue', 'cancelled'])->default('pending');
                $table->string('payment_token', 64)->unique();
                $table->date('due_date')->nullable();
                $table->text('notes')->nullable();
                $table->timestamps();
                $table->softDeletes();
            });
        }

        if (!Schema::hasTable('payment_plan_installments')) {
            Schema::create('payment_plan_installments', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('payment_plan_id')->index();
                $table->integer('installment_number')->default(1);
                $table->string('title')->nullable();
                $table->decimal('amount', 12, 2)->default(0.00);
                $table->date('due_date')->nullable();
                $table->enum('status', ['pending', 'paid', 'overdue'])->default('pending');
                $table->timestamp('paid_at')->nullable();
                $table->string('payment_method')->nullable();
                $table->string('transaction_ref')->nullable();
                $table->string('utr_number')->nullable();
                $table->string('proof_url')->nullable();
                $table->text('notes')->nullable();
                $table->timestamps();

                $table->foreign('payment_plan_id')->references('id')->on('payment_plans')->onDelete('cascade');
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('payment_plan_installments');
        Schema::dropIfExists('payment_plans');
    }
};
