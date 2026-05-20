<?php

namespace App\Domains\Stakeholders\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class StakeholderContact extends Model
{
    use HasFactory;

    protected $table = 'stakeholder_contacts';

    protected $fillable = [
        'stakeholder_id',
        'full_name',
        'email',
        'contact_number',
        'position',
    ];

    public function stakeholder()
    {
        return $this->belongsTo(Stakeholder::class);
    }
}
