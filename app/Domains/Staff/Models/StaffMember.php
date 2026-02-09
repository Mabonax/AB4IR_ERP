<?php

namespace App\Domains\Staff\Models;

use App\Models\User;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class StaffMember extends Model
{
    use HasFactory;

    protected $table = 'staff_members';

    protected $fillable = [
        'user_id',
        'department_id',
        'first_name',
        'last_name',
        'email',
        'phone',
        'employee_number',
        'status',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function department()
    {
        return $this->belongsTo(StaffDepartment::class, 'department_id');
    }

    public function nextOfKin()
    {
        return $this->hasOne(StaffNextOfKin::class);
    }
}
