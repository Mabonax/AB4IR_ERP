<?php

namespace App\Models;

use App\Domains\Beneficiaries\Models\Beneficiary;
use Illuminate\Database\Eloquent\Model;

class NextOfKin extends Model
{
    protected $fillable = [
        'name',
        'surname',
        'relationship',
        'phone',
        'email',
    ];

    public function beneficiary()
    {
        return $this->hasOne(Beneficiary::class);
    }
}
