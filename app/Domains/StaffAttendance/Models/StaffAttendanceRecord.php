<?php

namespace App\Domains\StaffAttendance\Models;

use App\Domains\Staff\Models\StaffMember;
use Illuminate\Database\Eloquent\Model;

class StaffAttendanceRecord extends Model
{
    protected $fillable = [
        'staff_member_id',
        'late_override_id',
        'attendance_date',
        'clock_in_at',
        'clock_out_at',
        'clock_in_status',
        'clock_in_source',
        'clock_out_source',
    ];

    protected $casts = [
        'attendance_date' => 'date',
        'clock_in_at' => 'datetime',
        'clock_out_at' => 'datetime',
    ];

    public function staffMember()
    {
        return $this->belongsTo(StaffMember::class, 'staff_member_id');
    }

    public function lateOverride()
    {
        return $this->belongsTo(StaffAttendanceOverride::class, 'late_override_id');
    }

    public function activities()
    {
        return $this->hasMany(StaffAttendanceActivity::class, 'staff_attendance_record_id');
    }
}
