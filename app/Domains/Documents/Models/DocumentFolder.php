<?php

namespace App\Domains\Documents\Models;

use App\Models\User;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class DocumentFolder extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'parent_id',
        'owner_type',
        'owner_id',
        'folder_type',
        'created_by',
    ];

    public const TYPE_LIBRARY_GROUP = 'library_group';

    public const TYPE_PROGRAM_ROOT = 'program_root';

    public const TYPE_PROJECT_ROOT = 'project_root';

    public const TYPE_STANDARD = 'standard';

    public function parent(): BelongsTo
    {
        return $this->belongsTo(self::class, 'parent_id');
    }

    public function children(): HasMany
    {
        return $this->hasMany(self::class, 'parent_id')->orderBy('name');
    }

    public function files(): HasMany
    {
        return $this->hasMany(DocumentFile::class, 'folder_id')->orderBy('title');
    }

    public function owner(): MorphTo
    {
        return $this->morphTo();
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function isLibraryGroup(): bool
    {
        return $this->folder_type === self::TYPE_LIBRARY_GROUP;
    }
}
