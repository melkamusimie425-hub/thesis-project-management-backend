<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphTo;

#[Fillable([
    'uploaded_by', 'work_type', 'work_id', 'original_name', 'file_path',
    'mime_type', 'size', 'version', 'category', 'is_published', 'description',
])]
class Document extends Model
{
    protected $casts = [
        'size' => 'integer',
        'version' => 'integer',
        'is_published' => 'boolean',
    ];

    /** The project / proposal / thesis this document belongs to. */
    public function work(): MorphTo
    {
        return $this->morphTo();
    }

    public function uploader(): BelongsTo
    {
        return $this->belongsTo(User::class, 'uploaded_by');
    }

    public function reviews(): HasMany
    {
        return $this->hasMany(DocumentReview::class);
    }
}
