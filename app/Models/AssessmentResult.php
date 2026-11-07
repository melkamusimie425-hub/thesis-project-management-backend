<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable([
    'schedule_id', 'assessor_id', 'student_id', 'work_type', 'criteria_id', 'score', 'comment',
])]
class AssessmentResult extends Model
{
    protected $casts = [
        'score' => 'decimal:2',
    ];

    public function schedule(): BelongsTo
    {
        return $this->belongsTo(Schedule::class);
    }

    public function assessor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assessor_id');
    }

    public function student(): BelongsTo
    {
        return $this->belongsTo(User::class, 'student_id');
    }

    public function criteria(): BelongsTo
    {
        return $this->belongsTo(AssessmentCriteria::class, 'criteria_id');
    }
}
