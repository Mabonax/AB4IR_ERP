<?php

namespace App\Domains\Organisation\Models;

use App\Domains\Committees\Models\Committee;
use App\Domains\Compliance\Models\ComplianceRecord;
use App\Domains\Governance\Models\GovernanceStructure;
use App\Domains\Meetings\Models\Meeting;
use App\Domains\Resolutions\Models\Resolution;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Organisation extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'registration_number',
        'organisation_type',
        'npo_number',
        'pbo_number',
        'tax_reference_number',
        'constitution_version',
        'registered_at',
        'npo_registered_at',
        'pbo_registered_at',
        'contact_details',
        'status',
    ];

    protected function casts(): array
    {
        return [
            'registered_at' => 'date',
            'npo_registered_at' => 'date',
            'pbo_registered_at' => 'date',
            'contact_details' => 'array',
        ];
    }

    public function complianceRecords(): HasMany
    {
        return $this->hasMany(ComplianceRecord::class);
    }

    public function governanceStructures(): HasMany
    {
        return $this->hasMany(GovernanceStructure::class);
    }

    public function committees(): HasMany
    {
        return $this->hasMany(Committee::class);
    }

    public function meetings(): HasMany
    {
        return $this->hasMany(Meeting::class);
    }

    public function resolutions(): HasMany
    {
        return $this->hasMany(Resolution::class);
    }
}
