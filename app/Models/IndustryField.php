<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class IndustryField extends Model
{
    use HasFactory;

    protected $fillable = [
        'industry_id',
        'module_key',
        'field_key',
        'label',
        'field_type',
        'options',
        'is_required',
        'default_value',
        'display_order',
    ];

    protected $casts = [
        'options' => 'array',
        'is_required' => 'boolean',
        'display_order' => 'integer',
    ];

    public function industry(): BelongsTo
    {
        return $this->belongsTo(Industry::class);
    }
}
