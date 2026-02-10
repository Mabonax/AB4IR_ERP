<?php

namespace App\Domains\Projects\Models;

use App\Domains\Facilitators\Models\Facilitator;
use App\Models\Provinces;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ProjectLocation extends Model
{
    use HasFactory;

    protected $table = 'project_locations';

    protected $fillable = [
        'project_id',
        'facilitator_id',
        'province_id',
    ];

    public function project()
    {
        return $this->belongsTo(Project::class);
    }

    public function facilitator()
    {
        return $this->belongsTo(Facilitator::class);
    }

    public function province()
    {
        return $this->belongsTo(Provinces::class, 'province_id');
    }

    public function enrollments()
    {
        return $this->hasMany(ProjectEnrollment::class);
    }
}
