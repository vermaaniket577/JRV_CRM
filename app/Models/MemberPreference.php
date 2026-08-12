<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class MemberPreference extends Model
{
    use HasFactory;

    protected $fillable = [
        'member_id',
        'age_min',
        'age_max',
        'height_min_cm',
        'height_max_cm',
        'marital_status',
        'religion',
        'caste',
        'excluded_gotras',
        'preferred_education',
        'preferred_occupation',
        'min_income',
        'preferred_state',
        'preferred_city',
    ];

    protected $casts = [
        'age_min' => 'integer',
        'age_max' => 'integer',
        'height_min_cm' => 'integer',
        'height_max_cm' => 'integer',
        'min_income' => 'decimal:2',
    ];

    public function member(): BelongsTo
    {
        return $this->belongsTo(Member::class);
    }
}
