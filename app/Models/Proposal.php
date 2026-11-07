<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphMany;

#[Fillable([
    'student_id', 'advisor_id', 'academic_year_id', 'title', 'abstract', 'status',
    'reviewer_recommendation', 'modified_title', 'general_comment', 'submitted_at',
])]
class Proposal extends Model
{
    protected $casts = [
        'submitted_at' => 'datetime',
    ];

    public function student(): BelongsTo
    {
        return $this->belongsTo(User::class, 'student_id');
    }

    public function advisor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'advisor_id');
    }

    public function academicYear(): BelongsTo
    {
        return $this->belongsTo(AcademicYear::class);
    }

    public function thesis(): HasMany
    {
        return $this->hasMany(Thesis::class);
    }

    public function documents(): MorphMany
    {
        return $this->morphMany(Document::class, 'work');
    }

    public function schedules(): MorphMany
    {
        return $this->morphMany(Schedule::class, 'work');
    }
}
