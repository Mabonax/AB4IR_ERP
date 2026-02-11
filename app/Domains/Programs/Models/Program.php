<?php

namespace App\Domains\Programs\Models;

use App\Domains\Projects\Models\ProgramMilestoneTemplate;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Program extends Model
{
    use HasFactory;

    protected $table = 'programs';

    protected $fillable = [
        'title',
        'description',
        'slug',
    ];

    public function milestoneTemplates(): HasMany
    {
        return $this->hasMany(ProgramMilestoneTemplate::class, 'program_id');
    }
}
