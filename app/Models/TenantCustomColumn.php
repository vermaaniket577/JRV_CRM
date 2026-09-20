<?php

namespace App\Models;

use App\Traits\BelongsToTenant;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TenantCustomColumn extends Model
{
    use HasFactory, BelongsToTenant;

    protected $fillable = [
        'tenant_id',
        'table_name',
        'column_key',
        'column_label',
        'column_type',
        'options',
        'is_required',
        'is_default',
        'default_value',
        'display_order',
        'is_visible',
    ];

    protected $casts = [
        'options' => 'array',
        'is_required' => 'boolean',
        'is_default' => 'boolean',
        'is_visible' => 'boolean',
        'display_order' => 'integer',
    ];

    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }
}
