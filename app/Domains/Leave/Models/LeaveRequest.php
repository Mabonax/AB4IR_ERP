<?php

namespace App\Domains\Leave\Models;

use App\Domains\Staff\Models\StaffMember;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class LeaveRequest extends Model
{
    use HasFactory;

    protected $table = 'leave_requests';

    protected $fillable = [
        'staff_member_id',
        'manager_id',
        'start_date',
        'end_date',
        'total_days',
        'reason',
        'status',
        'manager_comment',
        'hr_comment',
        'manager_approved_at',
        'hr_approved_at',
        'submitted_at',
    ];

    protected $casts = [
        'start_date' => 'date',
        'end_date' => 'date',
        'manager_approved_at' => 'datetime',
        'hr_approved_at' => 'datetime',
        'submitted_at' => 'datetime',
    ];

    public function staffMember()
    {
        return $this->belongsTo(StaffMember::class, 'staff_member_id');
    }

    public function manager()
    {
        return $this->belongsTo(StaffMember::class, 'manager_id');
    }
}
