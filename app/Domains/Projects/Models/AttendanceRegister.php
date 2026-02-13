<?php

namespace App\Domains\Projects\Models;

use App\Domains\Facilitators\Models\Facilitator;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class AttendanceRegister extends Model
{
    use HasFactory;

    protected $table = 'attendance_registers';

    protected $fillable = [
        'project_id',
        'project_location_id',
        'facilitator_id',
        'attendance_date',
        'is_holiday',
        'holiday_reason',
        'holiday_marked_by_user_id',
    ];

    protected $casts = [
        'attendance_date' => 'date:Y-m-d',
        'is_holiday' => 'boolean',
    ];

    public function project()
    {
        return $this->belongsTo(Project::class);
    }

    public function location()
    {
        return $this->belongsTo(ProjectLocation::class, 'project_location_id');
    }

    public function facilitator()
    {
        return $this->belongsTo(Facilitator::class);
    }

    public function holidayMarkedBy()
    {
        return $this->belongsTo(User::class, 'holiday_marked_by_user_id');
    }

    public function entries()
    {
        return $this->hasMany(AttendanceEntry::class, 'attendance_register_id');
    }
}
