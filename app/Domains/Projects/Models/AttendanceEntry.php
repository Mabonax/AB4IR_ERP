<?php

namespace App\Domains\Projects\Models;

use App\Domains\Beneficiaries\Models\Beneficiary;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class AttendanceEntry extends Model
{
    use HasFactory;

    protected $table = 'attendance_entries';

    protected $fillable = [
        'attendance_register_id',
        'beneficiary_id',
        'status',
        'excused_reason',
    ];

    public function register()
    {
        return $this->belongsTo(AttendanceRegister::class, 'attendance_register_id');
    }

    public function beneficiary()
    {
        return $this->belongsTo(Beneficiary::class);
    }
}
