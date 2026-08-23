<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('properties')) {
            Schema::create('properties', function (Blueprint $table) {
                $table->id();
                $table->foreignId('tenant_id')->nullable()->constrained('tenants')->nullOnDelete();
                $table->string('property_code')->unique();
                $table->string('title');
                $table->enum('listing_type', ['For Rent', 'For Sale', 'Lease', 'PG / Co-living'])->default('For Rent');
                $table->enum('property_type', ['Apartment', 'Villa / House', 'Studio Flat', 'Commercial Office', 'Retail Shop', 'Penthouse'])->default('Apartment');
                $table->decimal('price', 14, 2)->default(0.00); // Rent or Sale price
                $table->decimal('security_deposit', 14, 2)->default(0.00)->nullable();
                $table->integer('bedrooms')->default(2);
                $table->integer('bathrooms')->default(2);
                $table->integer('carpet_area_sqft')->default(850)->nullable();
                $table->enum('furnishing_status', ['Fully Furnished', 'Semi-Furnished', 'Unfurnished'])->default('Semi-Furnished');
                $table->string('city')->default('Mumbai');
                $table->string('state')->default('Maharashtra');
                $table->string('locality')->nullable();
                $table->text('address')->nullable();
                $table->string('owner_name')->nullable();
                $table->string('owner_phone')->nullable();
                $table->string('owner_email')->nullable();
                $table->json('amenities')->nullable(); // Parking, Lift, Gym, Swimming Pool, Security, Power Backup
                $table->enum('status', ['Available', 'Under Offer', 'Rented Out', 'Sold'])->default('Available');
                $table->string('image_url')->nullable();
                $table->text('description')->nullable();
                $table->timestamps();
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('properties');
    }
};
