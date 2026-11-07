<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphMany;

#[Fillable([
    'name', 'approved_title', 'academic_year_id', 'advisor_id', 'created_by', 'status',
])]
class IndustrialProjectGroup extends Model
{
    public function academicYear(): BelongsTo
    {
        return $this->belongsTo(AcademicYear::class);
    }

    public function advisor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'advisor_id');
    }

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /** Students in the group (BR-01: max five). */
    public function members(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'group_members', 'group_id', 'student_id')
            ->withPivot(['is_leader', 'agreed'])
            ->withTimestamps();
    }

    public function titles(): HasMany
    {
        return $this->hasMany(ProjectTitle::class, 'group_id');
    }

    public function documents(): MorphMany
    {
        return $this->morphMany(Document::class, 'work');
    }

    public function schedules(): MorphMany
    {
        return $this->morphMany(Schedule::class, 'work');
    }

    /** Scope a query to only groups advised by the given instructor. */
    public function scopeAdvisedBy(Builder $query, int $userId): Builder
    {
        return $query->where('advisor_id', $userId);
    }
}
