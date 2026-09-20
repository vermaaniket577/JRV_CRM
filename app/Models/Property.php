<?php

namespace App\Models;

use App\Traits\BelongsToTenant;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Property extends Model
{
    use HasFactory, BelongsToTenant;

    protected $fillable = [
        'tenant_id',
        'property_code',
        'title',
        'listing_type',
        'property_type',
        'price',
        'security_deposit',
        'bedrooms',
        'bathrooms',
        'carpet_area_sqft',
        'furnishing_status',
        'city',
        'state',
        'locality',
        'address',
        'owner_name',
        'owner_phone',
        'owner_email',
        'amenities',
        'status',
        'image_url',
        'description',
    ];

    protected $casts = [
        'amenities' => 'array',
        'price' => 'decimal:2',
        'security_deposit' => 'decimal:2',
        'bedrooms' => 'integer',
        'bathrooms' => 'integer',
        'carpet_area_sqft' => 'integer',
    ];

    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }
}
