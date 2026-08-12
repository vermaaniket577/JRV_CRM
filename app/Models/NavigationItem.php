<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class NavigationItem extends Model
{
    use HasFactory;

    protected $fillable = [
        'tenant_id',
        'key',
        'label',
        'route',
        'icon',
        'display_order',
        'is_enabled',
    ];

    protected $casts = [
        'is_enabled' => 'boolean',
        'display_order' => 'integer',
    ];
}
