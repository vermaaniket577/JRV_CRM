<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ImportLog extends Model
{
    use HasFactory;

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
