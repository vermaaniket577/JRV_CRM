<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::dropIfExists('mail_logs');
        Schema::dropIfExists('tenant_mail_settings');

        // 1. Tenant Official Mail Settings Table
        Schema::create('tenant_mail_settings', function (Blueprint $table) {
            $table->id();
            $table->string('official_email')->default('info@jainshadimilan.com');
            $table->string('sender_name')->default('JSM Matrimonial Team');
            $table->string('mail_driver')->default('smtp');
            $table->string('mail_host')->default('smtp.gmail.com');
            $table->integer('mail_port')->default(587);
            $table->string('mail_encryption')->default('tls');
            $table->string('mail_username')->nullable();
            $table->string('mail_password')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        // 2. Mail Logs Table
        Schema::create('mail_logs', function (Blueprint $table) {
            $table->id();
            $table->string('recipient_email');
            $table->string('recipient_name')->nullable();
            $table->string('subject');
            $table->text('body');
            $table->string('template_name')->default('Custom Mail');
            $table->enum('status', ['Sent', 'Failed', 'Pending'])->default('Sent');
            $table->foreignId('sent_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('mail_logs');
        Schema::dropIfExists('tenant_mail_settings');
    }
};
