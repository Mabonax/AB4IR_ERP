<?php

namespace App\Domains\Facilitators\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Facilitator extends Model
{
    use HasFactory;

    protected $table = 'facilitators';

    protected $fillable = [
        'name',
        'surname',
        'dob',
        'id_number',
        'address',
        'email',
        'cell',
        'specialization',
    ];

    protected $casts = [
        'dob' => 'date:Y-m-d',
    ];
}
