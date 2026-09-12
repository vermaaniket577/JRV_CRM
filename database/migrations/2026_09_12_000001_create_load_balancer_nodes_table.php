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
        Schema::create('load_balancer_nodes', function (Blueprint $table) {
            $table->id();
            $table->string('name', 100);
            $table->string('host', 150);
            $table->unsignedInteger('port')->default(8000);
            $table->string('protocol', 10)->default('http');
            $table->unsignedInteger('weight')->default(1);
            $table->string('status', 30)->default('healthy'); // healthy, degraded, offline, maintenance
            $table->string('health_check_url', 255)->default('/up');
            $table->unsignedInteger('active_connections')->default(0);
            $table->unsignedBigInteger('total_requests')->default(0);
            $table->unsignedBigInteger('failed_requests')->default(0);
            $table->decimal('avg_latency_ms', 8, 2)->default(0.00);
            $table->boolean('is_backup')->default(false);
            $table->boolean('is_enabled')->default(true);
            $table->timestamp('last_checked_at')->nullable();
            $table->text('last_error')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('load_balancer_nodes');
    }
};
