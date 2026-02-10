<?php

namespace App\Domains\Projects\Models;

use App\Domains\Facilitators\Models\Facilitator;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ProjectLocation extends Model
{
    use HasFactory;

    protected $table = 'project_locations';

    protected $fillable = [
        'project_id',
        'facilitator_id',
        'location',
    ];

    public function project()
    {
        return $this->belongsTo(Project::class);
    }

    public function facilitator()
    {
        return $this->belongsTo(Facilitator::class);
    }
}
