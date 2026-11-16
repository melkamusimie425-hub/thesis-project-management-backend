<?php

namespace App\Http\Controllers;

use App\Models\AcademicYear;
use App\Models\FinalGrade;
use App\Models\IndustrialProjectGroup;
use App\Models\Thesis;
use App\Models\User;
use Illuminate\Http\Request;

class ReportController extends Controller
{
    /**
     * Generate timely reports (EUC-23 / SUC-15). Department Head only.
     */
    public function index(Request $request)
    {
        $data = $request->validate([
            'type' => ['nullable', 'in:thesis,project,summary,grades'],
            'academic_year_id' => ['nullable', 'exists:academic_years,id'],
            'search' => ['nullable', 'string', 'max:120'],
        ]);

        $type = $data['type'] ?? 'summary';

        if ($type === 'thesis') {
            return response()->json(['report' => $this->thesisReport($data['academic_year_id'] ?? null, $data['search'] ?? null)]);
        }

        if ($type === 'project') {
            return response()->json(['report' => $this->projectReport($data['academic_year_id'] ?? null, $data['search'] ?? null)]);
        }

        if ($type === 'grades') {
            return response()->json(['report' => $this->gradesReport($data['academic_year_id'] ?? null, $data['search'] ?? null)]);
        }

        return response()->json(['report' => $this->summaryReport($data['academic_year_id'] ?? null)]);
    }

    private function thesisReport(?int $yearId, ?string $search = null)
    {
        $query = Thesis::with(['student:id,first_name,middle_name,last_name,id_number', 'academicYear:id,year,semester']);
        if ($yearId) {
            $query->where('academic_year_id', $yearId);
        }
        if ($search) {
            $query->where(function ($q) use ($search) {
                $q->where('title', 'like', "%{$search}%")
                    ->orWhereHas('student', fn ($sq) => $sq->where('first_name', 'like', "%{$search}%")
                        ->orWhere('last_name', 'like', "%{$search}%")
                        ->orWhere('id_number', 'like', "%{$search}%"));
            });
        }

        $theses = $query->latest()->get();

        return [
            'type' => 'thesis',
            'count' => $theses->count(),
            'by_status' => $theses->groupBy('status')->map->count(),
            'items' => $theses->map(fn ($t) => [
                'id' => $t->id,
                'title' => $t->title,
                'student' => $t->student?->full_name,
                'id_number' => $t->student?->id_number,
                'status' => $t->status,
                'academic_year' => $t->academicYear?->year.' - '.$t->academicYear?->semester,
            ]),
        ];
    }

    private function projectReport(?int $yearId, ?string $search = null)
    {
        $query = IndustrialProjectGroup::with([
            'members:id,first_name,middle_name,last_name,id_number',
            'advisor:id,first_name,middle_name,last_name',
            'academicYear:id,year,semester',
        ]);
        if ($yearId) {
            $query->where('academic_year_id', $yearId);
        }
        if ($search) {
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('approved_title', 'like', "%{$search}%");
            });
        }

        $groups = $query->latest()->get();

        return [
            'type' => 'project',
            'count' => $groups->count(),
            'by_status' => $groups->groupBy('status')->map->count(),
            'items' => $groups->map(fn ($g) => [
                'id' => $g->id,
                'title' => $g->approved_title,
                'members' => $g->members->map(fn ($m) => $m->full_name.' ('.$m->id_number.')'),
                'advisor' => $g->advisor?->full_name,
                'status' => $g->status,
                'academic_year' => $g->academicYear?->year.' - '.$g->academicYear?->semester,
            ]),
        ];
    }

    private function gradesReport(?int $yearId, ?string $search = null)
    {
        $query = FinalGrade::with([
            'student:id,first_name,middle_name,last_name,id_number',
            'academicYear:id,year,semester',
        ]);
        if ($yearId) {
            $query->where('academic_year_id', $yearId);
        }
        if ($search) {
            $query->whereHas('student', fn ($sq) => $sq->where('first_name', 'like', "%{$search}%")
                ->orWhere('last_name', 'like', "%{$search}%")
                ->orWhere('id_number', 'like', "%{$search}%"));
        }

        $grades = $query->latest()->get();

        return [
            'type' => 'grades',
            'count' => $grades->count(),
            'items' => $grades->map(fn ($g) => [
                'student' => $g->student?->full_name,
                'id_number' => $g->student?->id_number,
                'work_type' => $g->work_type,
                'advisor_score' => $g->advisor_score,
                'exam1_score' => $g->examiner_one_score,
                'exam2_score' => $g->examiner_two_score,
                'final_score' => $g->final_score,
                'letter_grade' => $g->letter_grade,
                'remark' => $g->remark,
                'academic_year' => $g->academicYear?->year.' - '.$g->academicYear?->semester,
            ]),
        ];
    }

    private function summaryReport(?int $yearId)
    {
        $year = $yearId ? AcademicYear::find($yearId) : AcademicYear::where('is_active', true)->first();

        $groups = IndustrialProjectGroup::when($yearId, fn ($q) => $q->where('academic_year_id', $yearId))->count();
        $theses = Thesis::when($yearId, fn ($q) => $q->where('academic_year_id', $yearId))->count();
        $students = User::whereIn('role', ['Student', 'GradStudent'])->where('is_active', true)->count();
        $instructors = User::whereIn('role', ['Advisor', 'Examiner'])->where('is_active', true)->count();
        $graded = FinalGrade::when($yearId, fn ($q) => $q->where('academic_year_id', $yearId))->count();
        $passed = FinalGrade::when($yearId, fn ($q) => $q->where('academic_year_id', $yearId))->where('remark', 'Pass')->count();

        return [
            'type' => 'summary',
            'academic_year' => $year?->year.' - '.$year?->semester,
            'counts' => [
                'project_groups' => $groups,
                'theses' => $theses,
                'students' => $students,
                'instructors' => $instructors,
                'graded_students' => $graded,
                'passed' => $passed,
            ],
        ];
    }
}
