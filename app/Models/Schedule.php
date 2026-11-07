<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphTo;

#[Fillable([
    'work_type', 'work_id', 'academic_year_id', 'coordinator_id', 'date', 'time',
    'place', 'room', 'examiner_one_id', 'examiner_two_id', 'chair_id', 'status',
])]
class Schedule extends Model
{
    public function work(): MorphTo
    {
        return $this->morphTo();
    }

    public function academicYear(): BelongsTo
    {
        return $this->belongsTo(AcademicYear::class);
    }

    public function coordinator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'coordinator_id');
    }

    public function examinerOne(): BelongsTo
    {
        return $this->belongsTo(User::class, 'examiner_one_id');
    }

    public function examinerTwo(): BelongsTo
    {
        return $this->belongsTo(User::class, 'examiner_two_id');
    }

    public function chair(): BelongsTo
    {
        return $this->belongsTo(User::class, 'chair_id');
    }

    public function results(): HasMany
    {
        return $this->hasMany(AssessmentResult::class);
    }

    /** Human readable type label. */
    public function getTypeLabelAttribute(): string
    {
        return match ($this->work_type) {
            'IndustrialProjectGroup' => 'Industrial Project Defense',
            'Proposal' => 'Proposal Review',
            'Thesis' => 'Thesis Defense',
            default => ucfirst($this->work_type ?? ''),
        };
    }
}
