<?php

namespace App\Domains\Projects\Models;

use App\Domains\Programs\Models\Program;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ProgramMilestoneTemplate extends Model
{
    use HasFactory;

    protected $table = 'program_milestone_templates';

    protected $fillable = [
        'program_id',
        'title',
        'description',
        'sort_order',
        'max_score',
    ];

    public function program()
    {
        return $this->belongsTo(Program::class);
    }
}
