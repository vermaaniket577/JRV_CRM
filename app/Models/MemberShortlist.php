<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class MemberShortlist extends Model
{
    use HasFactory;

    protected $fillable = [
        'client_member_id',
        'shortlisted_member_id',
        'notes',
        'shortlisted_by',
    ];

    public function clientMember(): BelongsTo
    {
        return $this->belongsTo(Member::class, 'client_member_id');
    }

    public function shortlistedMember(): BelongsTo
    {
        return $this->belongsTo(Member::class, 'shortlisted_member_id');
    }

    public function staff(): BelongsTo
    {
        return $this->belongsTo(User::class, 'shortlisted_by');
    }
}
