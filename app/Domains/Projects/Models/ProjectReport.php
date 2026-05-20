<?php

namespace App\Domains\Projects\Models;

use App\Models\User;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ProjectReport extends Model
{
    use HasFactory;

    protected $table = 'project_reports';

    protected $fillable = [
        'project_id',
        'project_closure_id',
        'report_type',
        'title',
        'report_date',
        'executive_summary',
        'key_findings',
        'recommendations',
        'snapshot',
        'created_by_user_id',
    ];

    protected $casts = [
        'report_date' => 'date:Y-m-d',
        'snapshot' => 'array',
    ];

    public function project()
    {
        return $this->belongsTo(Project::class);
    }

    public function closure()
    {
        return $this->belongsTo(ProjectClosure::class, 'project_closure_id');
    }

    public function createdBy()
    {
        return $this->belongsTo(User::class, 'created_by_user_id');
    }
}
