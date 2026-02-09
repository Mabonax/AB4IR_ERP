<?php

namespace App\Domains\Staff\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class StaffNextOfKin extends Model
{
    use HasFactory;

    protected $table = 'staff_next_of_kin';

    protected $fillable = [
        'staff_member_id',
        'full_name',
        'relationship',
        'phone',
        'email',
    ];

    public function staffMember()
    {
        return $this->belongsTo(StaffMember::class);
    }
}
