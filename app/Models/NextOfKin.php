<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App\Domains\Beneficiaries\Models\Beneficiary;

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
