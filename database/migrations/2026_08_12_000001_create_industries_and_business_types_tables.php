<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Master industry sectors (Education, Healthcare, Real Estate, etc.)
        Schema::create('industries', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('slug')->unique();
            $table->string('icon', 10)->default('🏢');
            $table->string('description')->nullable();
            $table->string('color', 30)->default('slate');
            $table->integer('display_order')->default(0);
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        // Sub-types within each industry (e.g. Education → School, College, EdTech)
        Schema::create('business_types', function (Blueprint $table) {
            $table->id();
            $table->foreignId('industry_id')->constrained('industries')->cascadeOnDelete();
            $table->string('name');
            $table->string('slug');
            $table->string('description')->nullable();
            $table->integer('display_order')->default(0);
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->unique(['industry_id', 'slug']);
        });

        // Default modules activated when a tenant selects an industry
        Schema::create('industry_modules', function (Blueprint $table) {
            $table->id();
            $table->foreignId('industry_id')->constrained('industries')->cascadeOnDelete();
            $table->string('module_key');
            $table->string('label');
            $table->string('icon', 50)->default('FolderIcon');
            $table->string('route')->default('/');
            $table->integer('display_order')->default(0);
            $table->boolean('is_default')->default(true);
            $table->timestamps();

            $table->unique(['industry_id', 'module_key']);
        });

        // Pipeline templates per industry
        Schema::create('industry_pipeline_templates', function (Blueprint $table) {
            $table->id();
            $table->foreignId('industry_id')->constrained('industries')->cascadeOnDelete();
            $table->string('name');
            $table->boolean('is_default')->default(true);
            $table->timestamps();
        });

        // Stages within pipeline templates
        Schema::create('industry_pipeline_stage_templates', function (Blueprint $table) {
            $table->id();
            $table->foreignId('template_id')->constrained('industry_pipeline_templates')->cascadeOnDelete();
            $table->string('name');
            $table->integer('display_order')->default(0);
            $table->integer('win_probability')->default(0);
            $table->enum('stage_type', ['open', 'won', 'lost'])->default('open');
            $table->timestamps();
        });

        // Industry-specific custom fields (auto-added to leads/contacts for the industry)
        Schema::create('industry_fields', function (Blueprint $table) {
            $table->id();
            $table->foreignId('industry_id')->constrained('industries')->cascadeOnDelete();
            $table->string('module_key')->default('leads');
            $table->string('field_key');
            $table->string('label');
            $table->string('field_type', 50)->default('text');
            $table->json('options')->nullable();
            $table->boolean('is_required')->default(false);
            $table->string('default_value')->nullable();
            $table->integer('display_order')->default(0);
            $table->timestamps();

            $table->unique(['industry_id', 'module_key', 'field_key']);
        });

        // Dashboard widget templates per industry
        Schema::create('industry_dashboard_widgets', function (Blueprint $table) {
            $table->id();
            $table->foreignId('industry_id')->constrained('industries')->cascadeOnDelete();
            $table->string('widget_key');
            $table->string('label');
            $table->string('widget_type', 30)->default('stat');
            $table->json('config')->nullable();
            $table->integer('display_order')->default(0);
            $table->integer('grid_cols')->default(1);
            $table->timestamps();

            $table->unique(['industry_id', 'widget_key']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('industry_dashboard_widgets');
        Schema::dropIfExists('industry_fields');
        Schema::dropIfExists('industry_pipeline_stage_templates');
        Schema::dropIfExists('industry_pipeline_templates');
        Schema::dropIfExists('industry_modules');
        Schema::dropIfExists('business_types');
        Schema::dropIfExists('industries');
    }
};
