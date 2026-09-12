<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class CrmDatabase extends Model
{
    use HasFactory;

    protected $fillable = [
        'tenant_id',
        'name',
        'database_name',
        'original_file',
        'file_size_bytes',
        'status',
        'tables_count',
        'relationships_count',
        'total_records',
        'import_summary',
    ];

    protected $casts = [
        'import_summary' => 'array',
        'file_size_bytes' => 'integer',
        'tables_count' => 'integer',
        'relationships_count' => 'integer',
        'total_records' => 'integer',
    ];

    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }

    public function tables(): HasMany
    {
        return $this->hasMany(CrmTable::class, 'database_id')->orderBy('menu_order');
    }

    public function relationships(): HasMany
    {
        return $this->hasMany(CrmRelationship::class, 'database_id');
    }
}
