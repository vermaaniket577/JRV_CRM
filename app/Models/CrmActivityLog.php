<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CrmActivityLog extends Model
{
    use HasFactory;

    protected $fillable = [
        'tenant_id',
        'user_id',
        'action',
        'table_name',
        'record_id',
        'changes',
        'ip_address',
    ];

    protected $casts = [
        'changes' => 'array',
        'record_id' => 'integer',
    ];

    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Log a CRM action.
     */
    public static function log(
        int $tenantId,
        string $action,
        ?string $tableName = null,
        ?int $recordId = null,
        ?array $changes = null,
        ?int $userId = null,
    ): static {
        return static::create([
            'tenant_id' => $tenantId,
            'user_id' => $userId ?? auth()->id(),
            'action' => $action,
            'table_name' => $tableName,
            'record_id' => $recordId,
            'changes' => $changes,
            'ip_address' => request()?->ip(),
        ]);
    }
}
