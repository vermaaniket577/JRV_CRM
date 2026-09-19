<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class DatabaseBackup extends Model
{
    use HasFactory;

    protected $fillable = [
        'backup_name',
        'backup_path',
        'file_size',
        'admin_id',
        'admin_email',
        'source_filename',
        'database_version',
        'status',
        'metadata',
    ];

    protected $casts = [
        'file_size' => 'integer',
        'metadata' => 'array',
    ];

    public function admin(): BelongsTo
    {
        return $this->belongsTo(User::class, 'admin_id');
    }

    public function imports(): HasMany
    {
        return $this->hasMany(DatabaseImport::class, 'backup_id');
    }
}
