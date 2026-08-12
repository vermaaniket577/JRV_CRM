<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Padhadhikari extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'designation',
        'caste_group',
        'region',
        'contact_number',
        'email',
        'term_start',
        'term_end',
        'status',
        'responsibilities',
        'avatar_url',
    ];

    protected $casts = [
        'term_start' => 'date',
        'term_end' => 'date',
    ];
}
