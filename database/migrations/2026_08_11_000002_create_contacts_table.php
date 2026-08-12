<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('contacts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->nullable()->constrained('companies')->nullOnDelete();
            $table->string('first_name', 100)->index();
            $table->string('last_name', 100)->index();
            $table->string('email')->index();
            $table->string('phone', 50)->nullable()->index();
            $table->string('job_title', 100)->nullable();
            $table->string('avatar_path')->nullable();
            $table->string('source', 50)->nullable()->index();
            $table->enum('status', ['lead', 'prospect', 'customer', 'archived'])->default('lead')->index();
            $table->foreignId('owner_id')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->softDeletes();

            $table->index(['first_name', 'last_name'], 'idx_contacts_full_name');
            $table->index(['company_id', 'owner_id'], 'idx_contacts_company_owner');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('contacts');
    }
};
