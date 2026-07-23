<?php

namespace App\Domains\Beneficiaries\Models;

use App\Models\User;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class BeneficiaryHistory extends Model
{
    use HasFactory;

    protected $table = 'beneficiary_history';

    protected $fillable = [
        'beneficiary_id',
        'actor_user_id',
        'action',
        'from_status',
        'to_status',
        'summary',
        'reason',
        'meta',
    ];

    protected $casts = [
        'meta' => 'array',
    ];

    public function beneficiary()
    {
        return $this->belongsTo(Beneficiary::class);
    }

    public function actor()
    {
        return $this->belongsTo(User::class, 'actor_user_id');
    }
}
