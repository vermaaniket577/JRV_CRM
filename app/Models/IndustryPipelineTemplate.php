<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class IndustryPipelineTemplate extends Model
{
    use HasFactory;

    protected $fillable = [
        'industry_id',
        'name',
        'is_default',
    ];

    protected $casts = [
        'is_default' => 'boolean',
    ];

    public function industry(): BelongsTo
    {
        return $this->belongsTo(Industry::class);
    }

    public function stages(): HasMany
    {
        return $this->hasMany(IndustryPipelineStageTemplate::class, 'template_id')->orderBy('display_order');
    }
}
