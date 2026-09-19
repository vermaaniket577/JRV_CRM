<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DatabaseImportColumn extends Model
{
    use HasFactory;

    protected $fillable = [
        'database_import_table_id',
        'column_name',
        'data_type',
        'is_nullable',
        'is_primary',
        'is_auto_increment',
        'default_value',
        'status',
        'existing_data_type',
        'existing_nullable',
        'existing_default',
        'action',
        'requires_approval',
        'is_approved',
    ];

    protected $casts = [
        'is_nullable' => 'boolean',
        'is_primary' => 'boolean',
        'is_auto_increment' => 'boolean',
        'existing_nullable' => 'boolean',
        'requires_approval' => 'boolean',
        'is_approved' => 'boolean',
    ];

    public function importTable(): BelongsTo
    {
        return $this->belongsTo(DatabaseImportTable::class, 'database_import_table_id');
    }
}
