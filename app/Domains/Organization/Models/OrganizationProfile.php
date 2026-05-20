<?php

namespace App\Domains\Organization\Models;

use App\Models\User;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class OrganizationProfile extends Model
{
    use HasFactory;

    protected $table = 'organization_profiles';

    protected $fillable = [
        'name',
        'legal_name',
        'tagline',
        'mission',
        'vision',
        'objectives',
        'focus_areas',
        'about',
        'core_values',
        'service_offering',
        'website',
        'email',
        'phone',
        'address_line_1',
        'address_line_2',
        'city',
        'province',
        'country',
        'postal_code',
        'primary_logo_path',
        'light_logo_path',
        'dark_logo_path',
        'icon_logo_path',
        'impact_total',
        'impact_digital',
        'impact_physical',
        'trainings_conducted',
        'impact_website',
        'impact_walkins',
        'impact_facebook',
        'impact_x',
        'impact_linkedin',
        'impact_livestreaming',
        'impact_instagram',
        'impact_youtube',
        'created_by_user_id',
        'updated_by_user_id',
    ];

    public function createdBy()
    {
        return $this->belongsTo(User::class, 'created_by_user_id');
    }

    public function updatedBy()
    {
        return $this->belongsTo(User::class, 'updated_by_user_id');
    }
}
