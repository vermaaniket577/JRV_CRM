<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Industry extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'slug',
        'icon',
        'description',
        'color',
        'display_order',
        'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'display_order' => 'integer',
    ];

    public function businessTypes(): HasMany
    {
        return $this->hasMany(BusinessType::class)->orderBy('display_order');
    }

    public function modules(): HasMany
    {
        return $this->hasMany(IndustryModule::class)->orderBy('display_order');
    }

    public function pipelineTemplates(): HasMany
    {
        return $this->hasMany(IndustryPipelineTemplate::class);
    }

    public function fields(): HasMany
    {
        return $this->hasMany(IndustryField::class)->orderBy('display_order');
    }

    public function dashboardWidgets(): HasMany
    {
        return $this->hasMany(IndustryDashboardWidget::class)->orderBy('display_order');
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }
}
