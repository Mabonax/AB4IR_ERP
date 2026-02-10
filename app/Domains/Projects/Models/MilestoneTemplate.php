<?php

namespace App\Domains\Projects\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class MilestoneTemplate extends Model
{
    use HasFactory;

    protected $table = 'milestone_templates';

    protected $fillable = [
        'title',
        'description',
        'sort_order',
        'max_score',
    ];
}
