<?php

namespace App\Domains\StaffAttendance\Models;

use App\Domains\Staff\Models\StaffMember;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;

class StaffAttendanceOverride extends Model
{
    protected $fillable = [
        'staff_member_id',
        'requested_by_user_id',
        'opened_by_user_id',
        'attendance_date',
        'reason',
        'request_reason',
        'status',
        'approved_at',
        'used_at',
    ];

    protected $casts = [
        'attendance_date' => 'date',
        'approved_at' => 'datetime',
        'used_at' => 'datetime',
    ];

    public function staffMember()
    {
        return $this->belongsTo(StaffMember::class, 'staff_member_id');
    }

    public function openedBy()
    {
        return $this->belongsTo(User::class, 'opened_by_user_id');
    }

    public function requestedBy()
    {
        return $this->belongsTo(User::class, 'requested_by_user_id');
    }
}
