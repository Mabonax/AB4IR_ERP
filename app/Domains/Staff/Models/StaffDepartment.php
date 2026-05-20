<?php

namespace App\Domains\Staff\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class StaffDepartment extends Model
{
    use HasFactory;

    protected $table = 'staff_departments';

    protected $fillable = [
        'name',
        'description',
    ];

    public function staffMembers()
    {
        return $this->hasMany(StaffMember::class, 'department_id');
    }
}
