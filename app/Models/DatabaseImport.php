<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class DatabaseImport extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'file_name',
        'file_path',
        'file_size',
        'status',
        'import_mode',
        'tables_detected',
        'columns_detected',
        'records_detected',
        'records_inserted',
        'records_updated',
        'records_skipped',
        'records_failed',
        'schema_changes_summary',
        'backup_id',
        'started_at',
        'completed_at',
        'error_message',
    ];

    protected $casts = [
        'schema_changes_summary' => 'array',
        'started_at' => 'datetime',
        'completed_at' => 'datetime',
        'file_size' => 'integer',
        'tables_detected' => 'integer',
        'columns_detected' => 'integer',
        'records_detected' => 'integer',
        'records_inserted' => 'integer',
        'records_updated' => 'integer',
        'records_skipped' => 'integer',
        'records_failed' => 'integer',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function backup(): BelongsTo
    {
        return $this->belongsTo(DatabaseBackup::class, 'backup_id');
    }

    public function tables(): HasMany
    {
        return $this->hasMany(DatabaseImportTable::class);
    }

    public function mappings(): HasMany
    {
        return $this->hasMany(DatabaseFieldMapping::class);
    }

    public function errors(): HasMany
    {
        return $this->hasMany(DatabaseImportError::class);
    }
}
