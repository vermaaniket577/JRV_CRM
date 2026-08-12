<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class IndustryPipelineStageTemplate extends Model
{
    use HasFactory;

    protected $fillable = [
        'template_id',
        'name',
        'display_order',
        'win_probability',
        'stage_type',
    ];

    protected $casts = [
        'display_order' => 'integer',
        'win_probability' => 'integer',
    ];

    public function template(): BelongsTo
    {
        return $this->belongsTo(IndustryPipelineTemplate::class, 'template_id');
    }
}
