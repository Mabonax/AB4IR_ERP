<?php

namespace App\Domains\Finance\Models;

use App\Domains\Staff\Models\StaffDepartment;
use App\Domains\Staff\Models\StaffMember;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class TravelClaim extends Model
{
    use HasFactory;

    protected $fillable = [
        'claim_number',
        'claimant_staff_member_id',
        'department_id',
        'submitted_by_user_id',
        'checked_by_staff_member_id',
        'claim_month',
        'claimant_name',
        'claimant_address',
        'vehicle_make_model',
        'vehicle_type',
        'vehicle_year',
        'engine_volume',
        'tariff_per_km',
        'home_distance_km',
        'status',
        'approval_status',
        'submitted_at',
        'finance_received_at',
        'received_by_user_id',
        'approved_by_user_id',
        'approval_decided_at',
        'finance_paid_at',
        'paid_by_user_id',
        'finance_comment',
        'approval_comment',
        'total_actual_distance_km',
        'total_claimable_distance_km',
        'total_amount',
    ];

    protected $casts = [
        'claim_month' => 'date',
        'submitted_at' => 'datetime',
        'finance_received_at' => 'datetime',
        'approval_decided_at' => 'datetime',
        'finance_paid_at' => 'datetime',
        'tariff_per_km' => 'decimal:2',
        'home_distance_km' => 'decimal:2',
        'total_actual_distance_km' => 'decimal:2',
        'total_claimable_distance_km' => 'decimal:2',
        'total_amount' => 'decimal:2',
    ];

    public function claimant()
    {
        return $this->belongsTo(StaffMember::class, 'claimant_staff_member_id');
    }

    public function department()
    {
        return $this->belongsTo(StaffDepartment::class, 'department_id');
    }

    public function submittedBy()
    {
        return $this->belongsTo(User::class, 'submitted_by_user_id');
    }

    public function checkedBy()
    {
        return $this->belongsTo(StaffMember::class, 'checked_by_staff_member_id');
    }

    public function receivedBy()
    {
        return $this->belongsTo(User::class, 'received_by_user_id');
    }

    public function approvedBy()
    {
        return $this->belongsTo(User::class, 'approved_by_user_id');
    }

    public function paidBy()
    {
        return $this->belongsTo(User::class, 'paid_by_user_id');
    }

    public function trips()
    {
        return $this->hasMany(TravelClaimTrip::class)->orderBy('travel_date')->orderBy('id');
    }
}
