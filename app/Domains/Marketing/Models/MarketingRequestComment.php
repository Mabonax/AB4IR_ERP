<?php

namespace App\Domains\Marketing\Models;

use App\Models\User;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class MarketingRequestComment extends Model
{
    use HasFactory;

    protected $table = 'marketing_request_comments';

    protected $fillable = [
        'marketing_request_id',
        'user_id',
        'message',
    ];

    public function request(): BelongsTo
    {
        return $this->belongsTo(MarketingRequest::class, 'marketing_request_id');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
