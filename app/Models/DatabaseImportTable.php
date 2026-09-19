<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class DatabaseImportTable extends Model
{
    use HasFactory;

    protected $fillable = [
        'database_import_id',
        'table_name',
        'target_table_name',
        'status',
        'action',
        'records_count',
        'columns_count',
        'create_statement',
        'primary_key',
        'is_dangerous',
        'requires_approval',
        'is_approved',
    ];

    protected $casts = [
        'records_count' => 'integer',
        'columns_count' => 'integer',
        'is_dangerous' => 'boolean',
        'requires_approval' => 'boolean',
        'is_approved' => 'boolean',
    ];

    public function import(): BelongsTo
    {
        return $this->belongsTo(DatabaseImport::class, 'database_import_id');
    }

    public function columns(): HasMany
    {
        return $this->hasMany(DatabaseImportColumn::class, 'database_import_table_id');
    }

    public function mappings(): HasMany
    {
        return $this->hasMany(DatabaseFieldMapping::class, 'database_import_table_id');
    }
}
