<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('deals', function (Blueprint $table) {
            $table->id();
            $table->string('title')->index();
            $table->decimal('value', 15, 2)->default(0.00)->index();
            $table->string('currency', 3)->default('USD');
            $table->foreignId('pipeline_id')->constrained('pipelines')->restrictOnDelete();
            $table->foreignId('stage_id')->constrained('pipeline_stages')->restrictOnDelete();
            $table->foreignId('company_id')->nullable()->constrained('companies')->nullOnDelete();
            $table->foreignId('contact_id')->nullable()->constrained('contacts')->nullOnDelete();
            $table->foreignId('assigned_to')->nullable()->constrained('users')->nullOnDelete();
            $table->date('expected_close_date')->nullable()->index();
            $table->timestamp('closed_at')->nullable();
            $table->string('lost_reason')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->index(['pipeline_id', 'stage_id'], 'idx_deals_pipeline_stage');
            $table->index(['assigned_to', 'stage_id'], 'idx_deals_assigned_stage');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('deals');
    }
};
