<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class EmployeeProfile extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'employee_code',
        'department',
        'designation',
        'salary',
        'attendance_status',
        'payment_status',
        'gender',
        'age',
        'phone',
        'state',
        'city',
        'joining_date',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
