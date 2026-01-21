<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App\Domains\Beneficiaries\Models\Beneficiary;

class Next_of_kin extends Model
{
    protected $fillable = [
        'name',
        'surname',
        'relationship',
        'phone',
        'email',
    ];
    

    public function beneficiaries()
    {
        return $this->belongsTo(Beneficiary::class, 'next_of_kin_id');
    }

}
