<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DatabaseAuditLog extends Model
{
    use HasFactory;

    public $timestamps = false;

    protected $fillable = [
        'user_id',
        'user_email',
        'action',
        'table_name',
        'column_name',
        'previous_value',
        'new_value',
        'metadata',
        'ip_address',
        'created_at',
    ];

    protected $casts = [
        'metadata' => 'array',
        'created_at' => 'datetime',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public static function record(
        string $action,
        ?string $tableName = null,
        ?string $columnName = null,
        ?string $previousValue = null,
        ?string $newValue = null,
        ?array $metadata = null,
        ?int $userId = null,
        ?string $userEmail = null
    ): static {
        $user = auth()->user();
        return static::create([
            'user_id' => $userId ?? $user?->id,
            'user_email' => $userEmail ?? $user?->email ?? 'admin@crm.internal',
            'action' => $action,
            'table_name' => $tableName,
            'column_name' => $columnName,
            'previous_value' => $previousValue,
            'new_value' => $newValue,
            'metadata' => $metadata,
            'ip_address' => request()?->ip(),
            'created_at' => now(),
        ]);
    }
}
