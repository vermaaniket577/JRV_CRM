<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::dropIfExists('member_subscriptions');
        Schema::dropIfExists('member_shortlists');
        Schema::dropIfExists('interactions');
        Schema::dropIfExists('member_preferences');
        Schema::dropIfExists('member_documents');
        Schema::dropIfExists('members');

        // 1. Members (The Bio-data Profiles Table)
        Schema::create('members', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->nullable()->constrained('tenants')->cascadeOnDelete();
            $table->string('member_code')->unique();
            $table->string('first_name');
            $table->string('last_name');
            $table->enum('gender', ['Male', 'Female', 'Other'])->index();
            $table->date('date_of_birth')->index();
            $table->integer('age')->index();
            $table->integer('height_cm')->nullable()->index();
            $table->string('marital_status')->default('Never Married')->index();
            $table->string('religion')->default('Jain')->index();
            $table->string('caste')->default('Jain')->index();
            $table->string('sub_caste')->nullable()->index();
            $table->string('gotra')->nullable()->index();
            $table->string('mother_gotra')->nullable()->index();
            
            // Education & Professional Details
            $table->string('education_level')->nullable()->index();
            $table->string('education_field')->nullable();
            $table->string('occupation_type')->nullable()->index();
            $table->string('designation')->nullable();
            $table->string('company_name')->nullable();
            $table->decimal('annual_income', 12, 2)->default(0.00)->index();
            
            // Family & Personal Background
            $table->string('father_name')->nullable();
            $table->string('father_occupation')->nullable();
            $table->string('mother_name')->nullable();
            $table->integer('brothers_count')->default(0);
            $table->integer('sisters_count')->default(0);
            $table->string('family_type')->default('Nuclear');
            $table->string('family_status')->default('Upper Middle Class');
            
            // Contact & Location
            $table->string('email')->unique();
            $table->string('phone')->index();
            $table->string('alternate_phone')->nullable();
            $table->string('address_street')->nullable();
            $table->string('city')->index();
            $table->string('state')->index();
            $table->string('country')->default('India')->index();
            $table->string('pincode')->nullable();

            // Profile Photo & Bio
            $table->string('avatar_path')->nullable();
            $table->text('about_me')->nullable();

            // Verification & Membership Status
            $table->enum('verification_status', ['Unverified', 'Pending Review', 'Verified', 'Rejected'])->default('Unverified')->index();
            $table->timestamp('verified_at')->nullable();
            $table->foreignId('verified_by')->nullable()->constrained('users')->nullOnDelete();

            $table->foreignId('assigned_matchmaker_id')->nullable()->constrained('users')->nullOnDelete();
            $table->enum('status', ['Active', 'Inactive', 'Match Found', 'Married', 'Suspended'])->default('Active')->index();
            $table->timestamps();
            $table->softDeletes();

            // Fast Composite Indexes for Multi-Criteria Searches
            $table->index(['tenant_id', 'gender', 'age', 'state', 'verification_status'], 'idx_members_search_core');
            $table->index(['caste', 'sub_caste', 'gotra'], 'idx_members_community');
        });

        // 2. Member Identity Proof Documents
        Schema::create('member_documents', function (Blueprint $table) {
            $table->id();
            $table->foreignId('member_id')->constrained('members')->cascadeOnDelete();
            $table->enum('document_type', ['Aadhar Card', 'Passport', 'PAN Card', 'Driving License', 'Educational Degree', 'Income Proof'])->index();
            $table->string('document_number')->nullable();
            $table->string('file_path');
            $table->enum('status', ['Pending', 'Approved', 'Rejected'])->default('Pending')->index();
            $table->text('rejection_reason')->nullable();
            $table->foreignId('reviewed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('reviewed_at')->nullable();
            $table->timestamps();
        });

        // 3. Member Partner Matching Preferences
        Schema::create('member_preferences', function (Blueprint $table) {
            $table->id();
            $table->foreignId('member_id')->constrained('members')->cascadeOnDelete();
            $table->integer('age_min')->default(21);
            $table->integer('age_max')->default(35);
            $table->integer('height_min_cm')->default(150);
            $table->integer('height_max_cm')->default(190);
            $table->string('marital_status')->nullable();
            $table->string('religion')->nullable();
            $table->string('caste')->nullable();
            $table->text('excluded_gotras')->nullable();
            $table->string('preferred_education')->nullable();
            $table->string('preferred_occupation')->nullable();
            $table->decimal('min_income', 12, 2)->default(0.00);
            $table->string('preferred_state')->nullable();
            $table->string('preferred_city')->nullable();
            $table->timestamps();
        });

        // 4. Matchmaking Pipeline Interactions & Status Updates
        Schema::create('interactions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->nullable()->constrained('tenants')->cascadeOnDelete();
            $table->foreignId('member_id')->constrained('members')->cascadeOnDelete();
            $table->foreignId('target_member_id')->nullable()->constrained('members')->cascadeOnDelete();
            $table->enum('stage', ['Interested', 'Contact Shared', 'Meeting Scheduled', 'Talks in Progress', 'Matched', 'Closed Lost'])->default('Interested')->index();
            $table->text('notes')->nullable();
            $table->dateTime('scheduled_at')->nullable();
            $table->foreignId('created_by')->constrained('users')->cascadeOnDelete();
            $table->timestamps();
        });

        // 5. Staff Candidate Shortlists for Clients
        Schema::create('member_shortlists', function (Blueprint $table) {
            $table->id();
            $table->foreignId('client_member_id')->constrained('members')->cascadeOnDelete();
            $table->foreignId('shortlisted_member_id')->constrained('members')->cascadeOnDelete();
            $table->text('notes')->nullable();
            $table->foreignId('shortlisted_by')->constrained('users')->cascadeOnDelete();
            $table->timestamps();

            $table->unique(['client_member_id', 'shortlisted_member_id'], 'unique_client_shortlist');
        });

        // 6. Member Subscriptions & Tier Control
        Schema::create('member_subscriptions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->nullable()->constrained('tenants')->cascadeOnDelete();
            $table->foreignId('member_id')->constrained('members')->cascadeOnDelete();
            $table->enum('plan_tier', ['Free', 'Premium', 'Platinum', 'VIP'])->default('Free')->index();
            $table->decimal('amount_paid', 10, 2)->default(0.00);
            $table->string('currency')->default('INR');
            $table->enum('payment_status', ['Paid', 'Pending', 'Failed', 'Refunded'])->default('Paid')->index();
            $table->integer('contact_view_limit')->default(5);
            $table->integer('contact_views_used')->default(0);
            $table->dateTime('starts_at');
            $table->dateTime('expires_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('member_subscriptions');
        Schema::dropIfExists('member_shortlists');
        Schema::dropIfExists('interactions');
        Schema::dropIfExists('member_preferences');
        Schema::dropIfExists('member_documents');
        Schema::dropIfExists('members');
    }
};
