<?php

namespace App\Domains\Projects\Models;

use App\Models\User;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ProjectClosureEvidence extends Model
{
    use HasFactory;

    protected $table = 'project_closure_evidence';

    protected $fillable = [
        'project_id',
        'project_closure_id',
        'category',
        'title',
        'file_name',
        'disk',
        'path',
        'mime_type',
        'file_size',
        'notes',
        'uploaded_by_user_id',
    ];

    public function project()
    {
        return $this->belongsTo(Project::class);
    }

    public function closure()
    {
        return $this->belongsTo(ProjectClosure::class, 'project_closure_id');
    }

    public function uploadedBy()
    {
        return $this->belongsTo(User::class, 'uploaded_by_user_id');
    }
}
