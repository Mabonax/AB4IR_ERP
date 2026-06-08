<?php

namespace App\Domains\StaffAttendance\Models;

use App\Domains\Staff\Models\StaffMember;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;

class StaffAttendanceActivity extends Model
{
    protected $fillable = [
        'staff_member_id',
        'staff_attendance_record_id',
        'actor_user_id',
        'action',
        'reason',
        'meta',
        'occurred_at',
    ];

    protected $casts = [
        'meta' => 'array',
        'occurred_at' => 'datetime',
    ];

    public function staffMember()
    {
        return $this->belongsTo(StaffMember::class, 'staff_member_id');
    }

    public function record()
    {
        return $this->belongsTo(StaffAttendanceRecord::class, 'staff_attendance_record_id');
    }

    public function actor()
    {
        return $this->belongsTo(User::class, 'actor_user_id');
    }
}
