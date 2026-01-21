<?php

namespace App\Domains\Beneficiaries\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Beneficiary extends Model
{
    use HasFactory;

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
    'street_address',
    'address_line_2',
    'city',
    'location_id',
    'postal_code',
    'highest_qualification',
    'next_of_kin_id',
    'created_by',
    'updated_by',
];

}
