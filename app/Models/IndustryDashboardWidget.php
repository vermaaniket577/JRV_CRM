<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class IndustryDashboardWidget extends Model
{
    use HasFactory;

    protected $fillable = [
        'industry_id',
        'widget_key',
        'label',
        'widget_type',
        'config',
        'display_order',
        'grid_cols',
    ];

    protected $casts = [
        'config' => 'array',
        'display_order' => 'integer',
        'grid_cols' => 'integer',
    ];

    public function industry(): BelongsTo
    {
        return $this->belongsTo(Industry::class);
    }
}
