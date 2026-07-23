<?php

namespace App\Domains\Documents\Services;

use App\Domains\Assets\Models\Asset;
use App\Domains\Beneficiaries\Models\Beneficiary;
use App\Domains\Documents\Models\DocumentFile;
use App\Domains\Documents\Models\DocumentFolder;
use App\Domains\Events\Models\Event;
use App\Domains\Marketing\Models\MarketingAsset;
use App\Domains\Organization\Models\OrganizationProfile;
use App\Domains\Programs\Models\Program;
use App\Domains\Projects\Models\Project;
use App\Domains\Projects\Models\ProjectLocation;
use App\Domains\Staff\Models\StaffDepartment;
use App\Domains\Staff\Models\StaffMember;
use App\Domains\Stakeholders\Models\Stakeholder;
use App\Models\User;
use App\Policies\Concerns\InteractsWithDomainPermissions;

class DocumentAccessService
{
    use InteractsWithDomainPermissions;

    public function canViewAny(User $user): bool
    {
        foreach (['organization', 'programs', 'projects', 'events', 'beneficiaries', 'stakeholders', 'human-resources', 'assets', 'marketing'] as $domain) {
            if ($this->canViewDomain($user, $domain)) {
                return true;
            }
        }

        return false;
    }

    public function canViewFolder(User $user, DocumentFolder $folder): bool
    {
        if ($folder->isLibraryGroup()) {
            return $this->canViewAny($user);
        }

        if ($folder->owner_type === User::class) {
            return (int) $folder->owner_id === (int) $user->id
                || $this->canViewDomain($user, 'human-resources')
                || $this->canManageDomain($user, 'human-resources');
        }

        return $this->canViewOwner($user, $folder->owner_type);
    }

    public function canManageFolder(User $user, DocumentFolder $folder): bool
    {
        if ($folder->isLibraryGroup()) {
            return false;
        }

        if ($folder->owner_type === User::class) {
            return (int) $folder->owner_id === (int) $user->id
                || $this->canManageDomain($user, 'human-resources');
        }

        return $this->canManageOwner($user, $folder->owner_type);
    }

    public function canViewFile(User $user, DocumentFile $file): bool
    {
        return $this->canViewFolder($user, $file->folder);
    }

    public function canManageFile(User $user, DocumentFile $file): bool
    {
        return $this->canManageFolder($user, $file->folder);
    }

    public function canVersionFile(User $user, DocumentFile $file): bool
    {
        return $this->canManageFile($user, $file)
            || $user->can('documents.version')
            || $user->can('documents.manage');
    }

    public function canApproveFile(User $user, DocumentFile $file): bool
    {
        return $this->canManageOwner($user, $file->folder?->owner_type)
            || $user->can('documents.approve')
            || $user->can('documents.manage');
    }

    public function canCheckoutFile(User $user, DocumentFile $file): bool
    {
        return $this->canManageFile($user, $file)
            || $user->can('documents.checkout')
            || $user->can('documents.manage');
    }

    public function canViewOwner(User $user, ?string $ownerType): bool
    {
        return match ($ownerType) {
            OrganizationProfile::class => $this->canViewDomain($user, 'organization'),
            Program::class => $this->canViewDomain($user, 'programs'),
            Project::class, ProjectLocation::class => $this->canViewDomain($user, 'projects'),
            Event::class => $this->canViewDomain($user, 'events'),
            Beneficiary::class => $this->canViewDomain($user, 'beneficiaries'),
            Stakeholder::class => $this->canViewDomain($user, 'stakeholders'),
            Asset::class => $this->canViewDomain($user, 'assets'),
            MarketingAsset::class => $this->canViewDomain($user, 'marketing'),
            StaffDepartment::class => $this->canViewDomain($user, 'human-resources'),
            StaffMember::class, User::class => $this->canViewDomain($user, 'human-resources') || $this->canManageDomain($user, 'human-resources'),
            null => false,
            default => false,
        };
    }

    public function canManageOwner(User $user, ?string $ownerType): bool
    {
        return match ($ownerType) {
            OrganizationProfile::class => $this->canManageDomain($user, 'organization'),
            Program::class => $this->canManageDomain($user, 'programs'),
            Project::class, ProjectLocation::class => $this->canManageDomain($user, 'projects'),
            Event::class => $this->canManageDomain($user, 'events'),
            Beneficiary::class => $this->canManageDomain($user, 'beneficiaries'),
            Stakeholder::class => $this->canManageDomain($user, 'stakeholders'),
            Asset::class => $this->canManageDomain($user, 'assets'),
            MarketingAsset::class => $this->canManageDomain($user, 'marketing'),
            StaffDepartment::class => $this->canManageDomain($user, 'human-resources'),
            StaffMember::class, User::class => $this->canManageDomain($user, 'human-resources'),
            null => false,
            default => false,
        };
    }
}
