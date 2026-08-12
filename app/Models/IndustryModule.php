<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class IndustryModule extends Model
{
    use HasFactory;

    protected $fillable = [
        'industry_id',
        'module_key',
        'label',
        'icon',
        'route',
        'display_order',
        'is_default',
    ];

    protected $casts = [
        'is_default' => 'boolean',
        'display_order' => 'integer',
    ];

    public function industry(): BelongsTo
    {
        return $this->belongsTo(Industry::class);
    }
}
