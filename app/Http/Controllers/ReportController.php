<?php

namespace App\Http\Controllers;

use App\Models\AcademicYear;
use App\Models\IndustrialProjectGroup;
use App\Models\Thesis;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ReportController extends Controller
{
    /**
     * Generate timely reports (EUC-23 / SUC-15). Department Head only.
     */
    public function index(Request $request)
    {
        $data = $request->validate([
            'type' => ['nullable', 'in:thesis,project,summary'],
            'academic_year_id' => ['nullable', 'exists:academic_years,id'],
        ]);

        $type = $data['type'] ?? 'summary';

        if ($type === 'thesis') {
            return response()->json(['report' => $this->thesisReport($data['academic_year_id'] ?? null)]);
        }

        if ($type === 'project') {
            return response()->json(['report' => $this->projectReport($data['academic_year_id'] ?? null)]);
        }

        return response()->json(['report' => $this->summaryReport($data['academic_year_id'] ?? null)]);
    }

    private function thesisReport(?int $yearId)
    {
        $query = Thesis::with(['student:id,first_name,middle_name,last_name,id_number', 'academicYear:id,year,semester']);
        if ($yearId) {
            $query->where('academic_year_id', $yearId);
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

    private function projectReport(?int $yearId)
    {
        $query = IndustrialProjectGroup::with([
            'members:id,first_name,middle_name,last_name,id_number',
            'advisor:id,first_name,middle_name,last_name',
            'academicYear:id,year,semester',
        ]);
        if ($yearId) {
            $query->where('academic_year_id', $yearId);
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

    private function summaryReport(?int $yearId)
    {
        $year = $yearId ? AcademicYear::find($yearId) : AcademicYear::where('is_active', true)->first();

        $groups = IndustrialProjectGroup::when($yearId, fn ($q) => $q->where('academic_year_id', $yearId))->count();
        $theses = Thesis::when($yearId, fn ($q) => $q->where('academic_year_id', $yearId))->count();
        $students = User::whereIn('role', ['Student', 'GradStudent'])->where('is_active', true)->count();
        $instructors = User::whereIn('role', ['Advisor', 'Examiner'])->where('is_active', true)->count();

        return [
            'type' => 'summary',
            'academic_year' => $year?->year.' - '.$year?->semester,
            'counts' => [
                'project_groups' => $groups,
                'theses' => $theses,
                'students' => $students,
                'instructors' => $instructors,
            ],
        ];
    }
}
