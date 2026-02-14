<?php

namespace App\Domains\Assets\Models;

use App\Domains\Projects\Models\Project;
use App\Domains\Staff\Models\StaffDepartment;
use App\Domains\Staff\Models\StaffMember;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class AssetAssignment extends Model
{
    use HasFactory;

    protected $table = 'asset_assignments';

    protected $fillable = [
        'asset_id',
        'department_id',
        'staff_member_id',
        'project_id',
        'assigned_by',
        'returned_by',
        'assigned_at',
        'returned_at',
        'notes',
    ];

    protected $casts = [
        'assigned_at' => 'datetime',
        'returned_at' => 'datetime',
    ];

    public function asset()
    {
        return $this->belongsTo(Asset::class);
    }

    public function department()
    {
        return $this->belongsTo(StaffDepartment::class, 'department_id');
    }

    public function staffMember()
    {
        return $this->belongsTo(StaffMember::class, 'staff_member_id');
    }

    public function project()
    {
        return $this->belongsTo(Project::class);
    }

    public function assignedBy()
    {
        return $this->belongsTo(User::class, 'assigned_by');
    }

    public function returnedBy()
    {
        return $this->belongsTo(User::class, 'returned_by');
    }
}

