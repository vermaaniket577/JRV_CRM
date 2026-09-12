<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class CrmTable extends Model
{
    use HasFactory;

    protected $fillable = [
        'database_id',
        'tenant_id',
        'table_name',
        'display_name',
        'icon',
        'is_active',
        'is_visible_in_menu',
        'menu_order',
        'record_count',
        'primary_key_column',
        'display_column',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'is_visible_in_menu' => 'boolean',
        'menu_order' => 'integer',
        'record_count' => 'integer',
    ];

    public function database(): BelongsTo
    {
        return $this->belongsTo(CrmDatabase::class, 'database_id');
    }

    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }

    public function columns(): HasMany
    {
        return $this->hasMany(CrmColumn::class, 'table_id')->orderBy('display_order');
    }

    /**
     * Get visible columns for listing pages.
     */
    public function visibleColumns(): HasMany
    {
        return $this->hasMany(CrmColumn::class, 'table_id')
            ->where('is_visible', true)
            ->orderBy('display_order');
    }

    /**
     * Get searchable columns.
     */
    public function searchableColumns(): HasMany
    {
        return $this->hasMany(CrmColumn::class, 'table_id')
            ->where('is_searchable', true);
    }

    /**
     * Get editable columns for forms.
     */
    public function editableColumns(): HasMany
    {
        return $this->hasMany(CrmColumn::class, 'table_id')
            ->where('is_editable', true)
            ->where('is_primary', false)
            ->where('is_auto_increment', false)
            ->orderBy('display_order');
    }

    /**
     * Get relationships where this table is the source (belongsTo).
     */
    public function outgoingRelationships()
    {
        return CrmRelationship::where('tenant_id', $this->tenant_id)
            ->where('source_table', $this->table_name)
            ->get();
    }

    /**
     * Get relationships where this table is the target (hasMany).
     */
    public function incomingRelationships()
    {
        return CrmRelationship::where('tenant_id', $this->tenant_id)
            ->where('target_table', $this->table_name)
            ->get();
    }
}
