<?php

namespace App\Domains\Beneficiaries\Models;

use App\Domains\Projects\Models\Project;
use App\Domains\Projects\Models\ProjectEnrollment;
use App\Models\NextOfKin;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Beneficiary extends Model
{
    use HasFactory;
    use SoftDeletes;

    public const ACTIVE_LIFECYCLE_STATUSES = ['enrolled'];

    protected $table = 'beneficiaries';

    protected $fillable = [
        'name',
        'surname',
        'dob',
        'age',
        'id_number',
        'email',
        'phone',
        'gender',
        'project_id',
        'street_address',
        'address_line_2',
        'city',
        'province_id',
        'postal_code',
        'highest_qualification',
        'attendance_status',
        'status',
        'status_reason',
        'graduated_at',
        'graduated_by',
        'exited_at',
        'exited_by',
        'exit_reason',
        'suspended_at',
        'suspended_by',
        'reactivated_at',
        'reactivated_by',
        'next_of_kin_id',
        'created_by',
        'updated_by',
    ];

    protected $casts = [
        'dob' => 'date:Y-m-d',
        'graduated_at' => 'datetime',
        'exited_at' => 'datetime',
        'suspended_at' => 'datetime',
        'reactivated_at' => 'datetime',
    ];

    public function nextOfKin()
    {
        return $this->belongsTo(NextOfKin::class, 'next_of_kin_id');
    }

    public function project()
    {
        return $this->belongsTo(Project::class);
    }

    public function projectEnrollments()
    {
        return $this->hasMany(ProjectEnrollment::class);
    }

    public function outcomes()
    {
        return $this->hasMany(BeneficiaryOutcome::class)->latest('recorded_at');
    }

    public function latestOutcome()
    {
        return $this->hasOne(BeneficiaryOutcome::class)->latestOfMany('recorded_at');
    }

    public function history()
    {
        return $this->hasMany(BeneficiaryHistory::class)->latest();
    }

    public function graduatedBy()
    {
        return $this->belongsTo(User::class, 'graduated_by');
    }

    public function exitedBy()
    {
        return $this->belongsTo(User::class, 'exited_by');
    }

    public function suspendedBy()
    {
        return $this->belongsTo(User::class, 'suspended_by');
    }

    public function reactivatedBy()
    {
        return $this->belongsTo(User::class, 'reactivated_by');
    }

    public function getFullNameAttribute(): string
    {
        return trim(($this->name ?? '').' '.($this->surname ?? ''));
    }

    public function isLifecycleActive(): bool
    {
        return in_array($this->status ?? 'enrolled', self::ACTIVE_LIFECYCLE_STATUSES, true);
    }
}
