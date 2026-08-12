<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class AutoUpdateRule extends Model
{
    use HasFactory;

    protected $fillable = [
        'rule_name',
        'trigger_event',
        'frequency',
        'action_type',
        'is_active',
        'processed_count',
        'last_run_at',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'processed_count' => 'integer',
        'last_run_at' => 'datetime',
    ];
}
