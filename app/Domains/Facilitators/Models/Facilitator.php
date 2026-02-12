<?php

namespace App\Domains\Facilitators\Models;

use App\Models\Provinces;
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
        'province_id',
    ];

    protected $casts = [
        'dob' => 'date:Y-m-d',
    ];

    public function province()
    {
        return $this->belongsTo(Provinces::class, 'province_id');
    }
}
