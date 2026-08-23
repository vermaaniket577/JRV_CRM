<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('tenant_custom_columns', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('tenant_id');
            $table->string('table_name')->default('crm_records');
            $table->string('column_key');
            $table->string('column_label');
            $table->string('column_type')->default('text'); // text, number, currency, date, dropdown, email, boolean, textarea
            $table->json('options')->nullable(); // For dropdown choices
            $table->boolean('is_required')->default(false);
            $table->boolean('is_default')->default(false);
            $table->string('default_value')->nullable();
            $table->integer('display_order')->default(0);
            $table->boolean('is_visible')->default(true);
            $table->timestamps();

            $table->foreign('tenant_id')->references('id')->on('tenants')->onDelete('cascade');
            $table->unique(['tenant_id', 'table_name', 'column_key']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tenant_custom_columns');
    }
};
