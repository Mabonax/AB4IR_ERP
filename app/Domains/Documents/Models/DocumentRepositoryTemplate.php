<?php

namespace App\Domains\Documents\Models;

use App\Models\User;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class DocumentRepositoryTemplate extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'slug',
        'owner_type',
        'description',
        'is_system',
        'created_by',
    ];

    protected $casts = [
        'is_system' => 'boolean',
    ];

    public function items(): HasMany
    {
        return $this->hasMany(DocumentRepositoryTemplateItem::class, 'template_id')
            ->whereNull('parent_item_id')
            ->orderBy('sort_order')
            ->orderBy('name');
    }

    public function allItems(): HasMany
    {
        return $this->hasMany(DocumentRepositoryTemplateItem::class, 'template_id')
            ->orderBy('sort_order')
            ->orderBy('name');
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}
