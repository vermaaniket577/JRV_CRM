<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CrmRelationship extends Model
{
    use HasFactory;

    protected $fillable = [
        'database_id',
        'tenant_id',
        'source_table',
        'source_column',
        'target_table',
        'target_column',
        'relationship_type',
        'display_label',
    ];

    public function database(): BelongsTo
    {
        return $this->belongsTo(CrmDatabase::class, 'database_id');
    }

    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }

    /**
     * Get the inverse relationship type.
     */
    public function getInverseTypeAttribute(): string
    {
        return match ($this->relationship_type) {
            'belongsTo' => 'hasMany',
            'hasMany' => 'belongsTo',
            'hasOne' => 'belongsTo',
            default => 'hasMany',
        };
    }
}
