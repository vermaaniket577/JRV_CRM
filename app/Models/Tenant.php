<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\SoftDeletes;

class Tenant extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'uuid',
        'name',
        'slug',
        'subdomain',
        'custom_domain',
        'domain',
        'logo_path',
        'primary_color_hex',
        'currency',
        'status',
        'database_name',
        'database_host',
        'database_port',
        'database_username',
        'database_password',
        'database_status',
        'database_created_at',
        'selected_columns_meta',
        'trial_ends_at',
        'industry_id',
        'business_type_id',
        'employee_range',
        'crm_goals',
        'onboarding_completed',
    ];

    protected $casts = [
        'trial_ends_at' => 'datetime',
        'database_created_at' => 'datetime',
        'crm_goals' => 'array',
        'selected_columns_meta' => 'array',
        'onboarding_completed' => 'boolean',
    ];

    protected $appends = [
        'subdomain_url',
    ];

    public function getSubdomainUrlAttribute(): string
    {
        $sub = $this->subdomain ?: preg_replace('/[^a-zA-Z0-9]/', '', strtolower($this->slug ?: 'crm'));
        $appUrl = config('app.url', 'http://localhost');
        $host = parse_url($appUrl, PHP_URL_HOST) ?: 'localhost';
        $port = parse_url($appUrl, PHP_URL_PORT);
        $scheme = parse_url($appUrl, PHP_URL_SCHEME) ?: 'http';
        
        $portStr = $port ? ":{$port}" : '';
        return "{$scheme}://{$sub}.{$host}{$portStr}";
    }

    public function industry(): BelongsTo
    {
        return $this->belongsTo(Industry::class);
    }

    public function businessType(): BelongsTo
    {
        return $this->belongsTo(BusinessType::class);
    }

    public function users(): HasMany
    {
        return $this->hasMany(User::class);
    }

    public function subscription(): HasOne
    {
        return $this->hasOne(Subscription::class);
    }

    public function deals(): HasMany
    {
        return $this->hasMany(Deal::class);
    }

    public function contacts(): HasMany
    {
        return $this->hasMany(Contact::class);
    }

    public function companies(): HasMany
    {
        return $this->hasMany(Company::class);
    }

    public function customColumns(): HasMany
    {
        return $this->hasMany(TenantCustomColumn::class)->orderBy('display_order');
    }

    public function crmRecords(): HasMany
    {
        return $this->hasMany(TenantCrmRecord::class)->latest();
    }

    public function navigationItems(): HasMany
    {
        return $this->hasMany(NavigationItem::class)->orderBy('display_order');
    }
}

