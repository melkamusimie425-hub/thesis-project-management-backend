<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable([
    'student_id', 'work_type', 'work_id', 'academic_year_id', 'advisor_score',
    'examiner_one_score', 'examiner_two_score', 'final_score', 'letter_grade',
    'remark', 'recorded_by',
])]
class FinalGrade extends Model
{
    protected $casts = [
        'advisor_score' => 'decimal:2',
        'examiner_one_score' => 'decimal:2',
        'examiner_two_score' => 'decimal:2',
        'final_score' => 'decimal:2',
    ];

    public function student(): BelongsTo
    {
        return $this->belongsTo(User::class, 'student_id');
    }

    public function academicYear(): BelongsTo
    {
        return $this->belongsTo(AcademicYear::class);
    }

    public function recordedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'recorded_by');
    }
}
