<?php

namespace App\Models;

use App\Traits\BelongsToTenant;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ImportLog extends Model
{
    use HasFactory, BelongsToTenant;

    protected $fillable = [
        'tenant_id',
        'import_source',
        'entity_type',
        'file_name',
        'total_rows',
        'imported_rows',
        'failed_rows',
        'status',
        'summary',
    ];
}
