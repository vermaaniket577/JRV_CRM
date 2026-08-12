<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\SoftDeletes;

class Member extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'tenant_id',
        'member_code',
        'first_name',
        'last_name',
        'gender',
        'date_of_birth',
        'age',
        'height_cm',
        'marital_status',
        'religion',
        'caste',
        'sub_caste',
        'gotra',
        'mother_gotra',
        'education_level',
        'education_field',
        'occupation_type',
        'designation',
        'company_name',
        'annual_income',
        'father_name',
        'father_occupation',
        'mother_name',
        'brothers_count',
        'sisters_count',
        'family_type',
        'family_status',
        'email',
        'phone',
        'alternate_phone',
        'address_street',
        'city',
        'state',
        'country',
        'pincode',
        'avatar_path',
        'about_me',
        'verification_status',
        'verified_at',
        'verified_by',
        'assigned_matchmaker_id',
        'status',
    ];

    protected $casts = [
        'date_of_birth' => 'date',
        'verified_at' => 'datetime',
        'annual_income' => 'decimal:2',
        'age' => 'integer',
        'height_cm' => 'integer',
        'brothers_count' => 'integer',
        'sisters_count' => 'integer',
    ];

    public function getFullNameAttribute(): string
    {
        return "{$this->first_name} {$this->last_name}";
    }

    public function matchmaker(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assigned_matchmaker_id');
    }

    public function verifier(): BelongsTo
    {
        return $this->belongsTo(User::class, 'verified_by');
    }

    public function documents(): HasMany
    {
        return $this->hasMany(MemberDocument::class);
    }

    public function preferences(): HasOne
    {
        return $this->hasOne(MemberPreference::class);
    }

    public function interactions(): HasMany
    {
        return $this->hasMany(Interaction::class);
    }

    public function shortlists(): HasMany
    {
        return $this->hasMany(MemberShortlist::class, 'client_member_id');
    }

    public function activeSubscription(): HasOne
    {
        return $this->hasOne(Subscription::class)->latestOfMany();
    }
}
