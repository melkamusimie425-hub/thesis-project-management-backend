<?php

namespace App\Http\Controllers;

use App\Models\AcademicYear;
use App\Models\AssessmentResult;
use App\Models\FinalGrade;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class FinalGradeController extends Controller
{
    /**
     * Compile the final grade for a student from advisor + two examiners
     * (BR-06 / BR-15 / EUC-05). The coordinator triggers this.
     */
    public function compile(Request $request)
    {
        $data = $request->validate([
            'student_id' => ['required', 'exists:users,id'],
            'work_type' => ['required', 'string'],
            'work_id' => ['nullable', 'integer'],
        ]);

        $activeYear = AcademicYear::where('is_active', true)->first();

        // Group all assessment results by assessor and average.
        $results = AssessmentResult::where('student_id', $data['student_id'])
            ->where('work_type', $data['work_type'])
            ->with('criteria')
            ->get();

        if ($results->isEmpty()) {
            return response()->json(['message' => 'No assessment results found to compile.'], 422);
        }

        // Compute total per assessor (sum of weighted score).
        $byAssessor = $results->groupBy('assessor_id')->map(function ($group) {
            $total = $group->sum(fn ($r) => $r->score * ($r->criteria->weight / 100));

            return round($total, 2);
        })->values();

        $scores = $byAssessor->all();
        $advisorScore = $scores[0] ?? null;
        $examinerOne = $scores[1] ?? null;
        $examinerTwo = $scores[2] ?? null;

        // BR-06: average of advisor + two examiners.
        $final = collect($scores)->avg();
        $final = $final ? round($final, 2) : null;

        $grade = FinalGrade::updateOrCreate(
            [
                'student_id' => $data['student_id'],
                'work_type' => $data['work_type'],
                'work_id' => $data['work_id'] ?? null,
                'academic_year_id' => $activeYear?->id,
            ],
            [
                'advisor_score' => $advisorScore,
                'examiner_one_score' => $examinerOne,
                'examiner_two_score' => $examinerTwo,
                'final_score' => $final,
                'letter_grade' => $this->toLetterGrade($final),
                'remark' => $this->toRemark($final),
                'recorded_by' => $request->user()->id,
            ]
        );

        return response()->json([
            'message' => 'Final grade compiled successfully.',
            'grade' => $grade,
        ]);
    }

    public function index(Request $request)
    {
        $grades = FinalGrade::with(['student:id,first_name,middle_name,last_name,id_number', 'academicYear:id,year,semester'])
            ->latest()
            ->paginate(20);

        return response()->json(['grades' => $grades]);
    }

    private function toLetterGrade(?float $score): ?string
    {
        if ($score === null) {
            return null;
        }

        return match (true) {
            $score >= 90 => 'A+',
            $score >= 85 => 'A',
            $score >= 80 => 'A-',
            $score >= 75 => 'B+',
            $score >= 70 => 'B',
            $score >= 65 => 'B-',
            $score >= 60 => 'C+',
            $score >= 50 => 'C',
            $score >= 45 => 'D',
            default => 'F',
        };
    }

    private function toRemark(?float $score): ?string
    {
        if ($score === null) {
            return null;
        }

        return $score >= 50 ? 'Pass' : 'Fail';
    }
}
