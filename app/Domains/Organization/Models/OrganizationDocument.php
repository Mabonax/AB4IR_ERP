<?php

namespace App\Domains\Organization\Models;

use App\Domains\Staff\Models\StaffDepartment;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class OrganizationDocument extends Model
{
    use HasFactory;

    protected $fillable = [
        'organization_profile_id',
        'title',
        'document_type',
        'description',
        'audience_scope',
        'department_id',
        'slot_key',
        'replace_existing',
        'is_active',
        'effective_from',
        'effective_until',
        'disk',
        'path',
        'file_name',
        'mime_type',
        'file_size',
        'source_type',
        'source_id',
        'published_by_user_id',
    ];

    protected $casts = [
        'replace_existing' => 'boolean',
        'is_active' => 'boolean',
        'effective_from' => 'datetime',
        'effective_until' => 'datetime',
        'file_size' => 'integer',
    ];

    public function profile(): BelongsTo
    {
        return $this->belongsTo(OrganizationProfile::class, 'organization_profile_id');
    }

    public function department(): BelongsTo
    {
        return $this->belongsTo(StaffDepartment::class, 'department_id');
    }

    public function publishedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'published_by_user_id');
    }

    public function targetUsers(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'organization_document_user')
            ->withTimestamps();
    }
}
