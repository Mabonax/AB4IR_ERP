<?php

namespace App\Domains\Projects\Models;

use App\Domains\Beneficiaries\Models\Beneficiary;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ProjectEnrollment extends Model
{
    use HasFactory;

    protected $table = 'project_enrollments';

    protected $fillable = [
        'project_id',
        'beneficiary_id',
        'status',
        'enrolled_at',
    ];

    protected $casts = [
        'enrolled_at' => 'datetime',
    ];

    public function project()
    {
        return $this->belongsTo(Project::class);
    }

    public function beneficiary()
    {
        return $this->belongsTo(Beneficiary::class);
    }
}
