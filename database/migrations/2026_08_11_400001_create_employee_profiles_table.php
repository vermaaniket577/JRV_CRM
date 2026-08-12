<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('employee_profiles', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->string('employee_code')->unique();
            $table->string('department')->default('Sales');
            $table->string('designation')->default('Staff Member');
            $table->decimal('salary', 12, 2)->default(35000.00);
            $table->enum('attendance_status', ['Present', 'Absent', 'On Leave', 'Half Day'])->default('Present');
            $table->enum('payment_status', ['Paid', 'Pending', 'Processing'])->default('Paid');
            $table->enum('gender', ['Male', 'Female', 'Other'])->default('Male');
            $table->integer('age')->default(28);
            $table->string('phone')->nullable();
            $table->string('state')->default('California');
            $table->string('city')->default('San Francisco');
            $table->date('joining_date')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('employee_profiles');
    }
};
