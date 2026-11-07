<?php

namespace App\Http\Controllers;

use App\Models\AcademicYear;
use App\Models\Proposal;
use App\Models\Thesis;
use App\Models\User;
use Illuminate\Http\Request;

class ThesisController extends Controller
{
    public function index(Request $request)
    {
        $user = $request->user();
        $query = Thesis::with(['student:id,first_name,middle_name,last_name,id_number', 'advisor:id,first_name,middle_name,last_name', 'academicYear:id,year,semester'])
            ->latest();

        if ($user->role === 'GradStudent') {
            $query->where('student_id', $user->id);
        } elseif ($user->role === 'Advisor') {
            $query->where('advisor_id', $user->id);
        }

        if ($status = $request->get('status')) {
            $query->where('status', $status);
        }

        return response()->json(['theses' => $query->paginate(20)]);
    }

    public function show(Thesis $thesis)
    {
        return response()->json(['thesis' => $thesis->load(['student', 'advisor', 'academicYear', 'documents'])]);
    }

    /**
     * Create / register a thesis from an accepted proposal (EUC-20).
     * Graduate student only.
     */
    public function store(Request $request)
    {
        $data = $request->validate([
            'proposal_id' => ['nullable', 'exists:proposals,id'],
            'title' => ['required', 'string', 'max:255'],
            'abstract' => ['nullable', 'string'],
        ]);

        $activeYear = AcademicYear::where('is_active', true)->first();
        if (! $activeYear) {
            return response()->json(['message' => 'No active academic year is set.'], 422);
        }

        $existing = Thesis::where('student_id', $request->user()->id)
            ->whereIn('status', ['writing', 'submitted', 'scheduled', 'defended'])
            ->exists();

        if ($existing) {
            return response()->json(['message' => 'You already have an active thesis (BR-09).'], 422);
        }

        $advisorId = null;
        if (! empty($data['proposal_id'])) {
            $proposal = Proposal::findOrFail($data['proposal_id']);
            $advisorId = $proposal->advisor_id;
        }

        $thesis = Thesis::create([
            'student_id' => $request->user()->id,
            'proposal_id' => $data['proposal_id'] ?? null,
            'advisor_id' => $advisorId,
            'academic_year_id' => $activeYear->id,
            'title' => $data['title'],
            'abstract' => $data['abstract'] ?? null,
            'status' => 'writing',
        ]);

        return response()->json([
            'message' => 'Thesis registered successfully.',
            'thesis' => $thesis,
        ], 201);
    }

    /**
     * Mark a thesis as submitted (final draft upload handled by DocumentController).
     */
    public function submit(Request $request, Thesis $thesis)
    {
        $this->authorizeOwnership($request->user(), $thesis);

        $thesis->update(['status' => 'submitted']);

        return response()->json(['message' => 'Thesis submitted for examination.', 'thesis' => $thesis->fresh()]);
    }

    /**
     * Assign / change a thesis advisor (SUC-12).
     */
    public function assignAdvisor(Request $request, Thesis $thesis)
    {
        $data = $request->validate([
            'advisor_id' => ['required', 'exists:users,id'],
        ]);

        $advisor = User::findOrFail($data['advisor_id']);
        if (! in_array($advisor->role, ['Advisor', 'Examiner', 'UGPC', 'GPC', 'SGC', 'IPC', 'DepartmentHead'])) {
            return response()->json(['message' => 'The selected user cannot act as an advisor.'], 422);
        }

        $thesis->update(['advisor_id' => $data['advisor_id']]);

        return response()->json([
            'message' => 'Thesis advisor assigned successfully.',
            'thesis' => $thesis->load('advisor'),
        ]);
    }

    private function authorizeOwnership($user, Thesis $thesis): void
    {
        if ($user->role === 'GradStudent' && $thesis->student_id !== $user->id) {
            abort(403, 'You can only act on your own thesis.');
        }
    }
}
