<?php

namespace App\Domains\Facilitators\Models;

use App\Models\Provinces;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Facilitator extends Model
{
    use HasFactory;

    protected $table = 'facilitators';

    protected $fillable = [
        'user_id',
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

    public function getRouteKeyName(): string
    {
        return 'id';
    }

    public function province()
    {
        return $this->belongsTo(Provinces::class, 'province_id');
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function projectLocations()
    {
        return $this->hasMany(\App\Domains\Projects\Models\ProjectLocation::class);
    }
}
