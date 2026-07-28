<?php

namespace App\Domains\Compliance\Models;

use App\Domains\Organisation\Models\Organisation;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ComplianceRecord extends Model
{
    use HasFactory;

    protected $fillable = [
        'organisation_id',
        'title',
        'compliance_area',
        'reference_code',
        'filing_frequency',
        'due_date',
        'submitted_at',
        'status',
        'owner_name',
        'meta',
        'notes',
    ];

    protected function casts(): array
    {
        return [
            'due_date' => 'date',
            'submitted_at' => 'date',
            'meta' => 'array',
        ];
    }

    public function organisation(): BelongsTo
    {
        return $this->belongsTo(Organisation::class);
    }
}
