<?php

namespace App\Domains\Beneficiaries\Models;

use App\Domains\Projects\Models\Project;
use App\Domains\Projects\Models\ProjectEnrollment;
use App\Models\NextOfKin;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Beneficiary extends Model
{
    use HasFactory;
    use SoftDeletes;

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
        'next_of_kin_id',
        'created_by',
        'updated_by',
    ];

    protected $casts = [
        'dob' => 'date:Y-m-d',
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
}
