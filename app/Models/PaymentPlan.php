<?php

namespace App\Models;

use App\Traits\BelongsToTenant;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class PaymentPlan extends Model
{
    use HasFactory, SoftDeletes, BelongsToTenant;

    protected $fillable = [
        'tenant_id',
        'contact_id',
        'user_id',
        'invoice_number',
        'customer_name',
        'customer_email',
        'customer_phone',
        'title',
        'total_amount',
        'currency',
        'plan_type',
        'status',
        'payment_token',
        'due_date',
        'notes',
    ];

    protected $casts = [
        'total_amount' => 'decimal:2',
        'due_date' => 'date',
    ];

    protected $appends = [
        'paid_amount',
        'pending_amount',
        'paid_percent',
    ];

    public function installments(): HasMany
    {
        return $this->hasMany(PaymentPlanInstallment::class)->orderBy('installment_number');
    }

    public function contact(): BelongsTo
    {
        return $this->belongsTo(Contact::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }

    public function getPaidAmountAttribute(): float
    {
        return (float) $this->installments()->where('status', 'paid')->sum('amount');
    }

    public function getPendingAmountAttribute(): float
    {
        return max(0, (float) $this->total_amount - $this->paid_amount);
    }

    public function getPaidPercentAttribute(): int
    {
        if ($this->total_amount <= 0) return 100;
        return (int) round(($this->paid_amount / $this->total_amount) * 100);
    }

    public function recalculateStatus(): void
    {
        $paid = $this->paid_amount;
        if ($paid >= $this->total_amount && $this->total_amount > 0) {
            $this->status = 'paid';
        } elseif ($paid > 0) {
            $this->status = 'partially_paid';
        } else {
            $this->status = 'pending';
        }
        $this->save();
    }
}
