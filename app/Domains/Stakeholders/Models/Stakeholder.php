<?php

namespace App\Domains\Stakeholders\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Stakeholder extends Model
{
    use HasFactory;

    protected $table = 'stakeholders';

    protected $fillable = [
        'organization_name',
        'name',
        'email',
        'contact_number',
        'status',
    ];

    public function contact()
    {
        return $this->hasOne(StakeholderContact::class)->latestOfMany();
    }

    public function contacts()
    {
        return $this->hasMany(StakeholderContact::class);
    }
}
