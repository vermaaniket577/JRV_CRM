<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DatabaseFieldMapping extends Model
{
    use HasFactory;

    protected $fillable = [
        'database_import_id',
        'database_import_table_id',
        'source_table',
        'target_table',
        'source_column',
        'target_column',
        'confidence',
        'confidence_score',
        'is_confirmed',
        'transformation_rule',
    ];

    protected $casts = [
        'confidence_score' => 'float',
        'is_confirmed' => 'boolean',
    ];

    public function import(): BelongsTo
    {
        return $this->belongsTo(DatabaseImport::class, 'database_import_id');
    }

    public function importTable(): BelongsTo
    {
        return $this->belongsTo(DatabaseImportTable::class, 'database_import_table_id');
    }
}
