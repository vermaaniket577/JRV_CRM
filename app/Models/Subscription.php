<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Subscription extends Model
{
    use HasFactory;

    protected $table = 'member_subscriptions';

    protected $fillable = [
        'tenant_id',
        'member_id',
        'plan_tier',
        'amount_paid',
        'currency',
        'payment_status',
        'contact_view_limit',
        'contact_views_used',
        'starts_at',
        'expires_at',
    ];

    protected $casts = [
        'starts_at' => 'datetime',
        'expires_at' => 'datetime',
        'amount_paid' => 'decimal:2',
        'contact_view_limit' => 'integer',
        'contact_views_used' => 'integer',
    ];

    public function member(): BelongsTo
    {
        return $this->belongsTo(Member::class);
    }
}
