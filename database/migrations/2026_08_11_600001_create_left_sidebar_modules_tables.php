<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::dropIfExists('job_applications');
        Schema::dropIfExists('job_postings');
        Schema::dropIfExists('padhadhikaris');
        Schema::dropIfExists('auto_update_rules');
        Schema::dropIfExists('broadcast_messages');

        // 1. BroadCast Messages Table
        Schema::create('broadcast_messages', function (Blueprint $table) {
            $table->id();
            $table->string('title');
            $table->enum('channel', ['WhatsApp', 'SMS', 'Email', 'All'])->default('WhatsApp');
            $table->string('target_audience')->default('All Verified Members');
            $table->text('message_body');
            $table->integer('sent_count')->default(0);
            $table->integer('delivered_count')->default(0);
            $table->integer('read_count')->default(0);
            $table->enum('status', ['Draft', 'Scheduled', 'Sending', 'Completed', 'Failed'])->default('Completed');
            $table->timestamp('scheduled_at')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });

        // 2. Auto Update Rules Table
        Schema::create('auto_update_rules', function (Blueprint $table) {
            $table->id();
            $table->string('rule_name');
            $table->string('trigger_event');
            $table->string('frequency')->default('Daily');
            $table->string('action_type');
            $table->boolean('is_active')->default(true);
            $table->integer('processed_count')->default(0);
            $table->timestamp('last_run_at')->nullable();
            $table->timestamps();
        });

        // 3. Padhadhikari (Community Office Bearers) Table
        Schema::create('padhadhikaris', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('designation');
            $table->string('caste_group')->default('Jain Digambar');
            $table->string('region')->default('National');
            $table->string('contact_number');
            $table->string('email');
            $table->date('term_start');
            $table->date('term_end')->nullable();
            $table->enum('status', ['Active', 'Term Ended', 'Honorary'])->default('Active');
            $table->text('responsibilities')->nullable();
            $table->string('avatar_url')->nullable();
            $table->timestamps();
        });

        // 4. Staff Recruitment Vacancies Table
        Schema::create('job_postings', function (Blueprint $table) {
            $table->id();
            $table->string('title');
            $table->string('department');
            $table->string('location')->default('San Francisco, CA');
            $table->string('employment_type')->default('Full-Time');
            $table->decimal('salary_min', 10, 2)->default(30000.00);
            $table->decimal('salary_max', 10, 2)->default(60000.00);
            $table->enum('status', ['Active', 'Draft', 'Closed'])->default('Active');
            $table->text('description')->nullable();
            $table->timestamps();
        });

        // 5. Job Applications Table
        Schema::create('job_applications', function (Blueprint $table) {
            $table->id();
            $table->foreignId('job_posting_id')->constrained('job_postings')->cascadeOnDelete();
            $table->string('applicant_name');
            $table->string('email');
            $table->string('phone');
            $table->string('experience_years')->default('3+ years');
            $table->enum('stage', ['Applied', 'Screening', 'Interview Scheduled', 'Offer Sent', 'Hired', 'Rejected'])->default('Applied');
            $table->string('resume_url')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('job_applications');
        Schema::dropIfExists('job_postings');
        Schema::dropIfExists('padhadhikaris');
        Schema::dropIfExists('auto_update_rules');
        Schema::dropIfExists('broadcast_messages');
    }
};
