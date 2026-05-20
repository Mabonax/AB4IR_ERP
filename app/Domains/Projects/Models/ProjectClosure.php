<?php

namespace App\Domains\Projects\Models;

use App\Models\User;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ProjectClosure extends Model
{
    use HasFactory;

    protected $table = 'project_closures';

    protected $fillable = [
        'project_id',
        'closure_date',
        'requested_by_user_id',
        'concluded_by_user_id',
        'signoff_notes',
        'final_report_summary',
        'snapshot',
    ];

    protected $casts = [
        'closure_date' => 'date:Y-m-d',
        'snapshot' => 'array',
    ];

    public function project()
    {
        return $this->belongsTo(Project::class);
    }

    public function requestedBy()
    {
        return $this->belongsTo(User::class, 'requested_by_user_id');
    }

    public function concludedBy()
    {
        return $this->belongsTo(User::class, 'concluded_by_user_id');
    }

    public function reports()
    {
        return $this->hasMany(ProjectReport::class, 'project_closure_id');
    }

    public function evidence()
    {
        return $this->hasMany(ProjectClosureEvidence::class, 'project_closure_id')->latest();
    }
}
