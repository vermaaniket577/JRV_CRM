<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DatabaseImportError extends Model
{
    use HasFactory;

    public $timestamps = false;

    protected $fillable = [
        'database_import_id',
        'table_name',
        'row_number',
        'column_name',
        'raw_data',
        'error_message',
        'created_at',
    ];

    protected $casts = [
        'row_number' => 'integer',
        'raw_data' => 'array',
        'created_at' => 'datetime',
    ];

    public function import(): BelongsTo
    {
        return $this->belongsTo(DatabaseImport::class, 'database_import_id');
    }
}
