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
        'manager_id',
        'is_ceo',
        'is_board_member',
        'first_name',
        'last_name',
        'email',
        'phone',
        'employee_number',
        'start_date',
        'status',
    ];

    protected $casts = [
        'start_date' => 'date',
        'is_ceo' => 'boolean',
        'is_board_member' => 'boolean',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function department()
    {
        return $this->belongsTo(StaffDepartment::class, 'department_id');
    }

    public function manager()
    {
        return $this->belongsTo(self::class, 'manager_id');
    }

    public function directReports()
    {
        return $this->hasMany(self::class, 'manager_id');
    }

    public function nextOfKin()
    {
        return $this->hasOne(StaffNextOfKin::class);
    }
}
