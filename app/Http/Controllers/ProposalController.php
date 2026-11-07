<?php

namespace App\Http\Controllers;

use App\Models\AcademicYear;
use App\Models\Proposal;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class ProposalController extends Controller
{
    public function index(Request $request)
    {
        $user = $request->user();
        $query = Proposal::with(['student:id,first_name,middle_name,last_name,id_number', 'advisor:id,first_name,middle_name,last_name', 'academicYear:id,year,semester'])
            ->latest();

        if ($user->role === 'GradStudent') {
            $query->where('student_id', $user->id);
        } elseif ($user->role === 'Advisor') {
            $query->where('advisor_id', $user->id);
        }

        if ($status = $request->get('status')) {
            $query->where('status', $status);
        }

        return response()->json(['proposals' => $query->paginate(20)]);
    }

    public function show(Proposal $proposal)
    {
        return response()->json(['proposal' => $proposal->load(['student', 'advisor', 'academicYear', 'documents'])]);
    }

    /**
     * Submit a thesis proposal (EUC-19). Graduate student only.
     */
    public function store(Request $request)
    {
        $data = $request->validate([
            'title' => ['required', 'string', 'max:255'],
            'abstract' => ['nullable', 'string'],
        ]);

        $activeYear = AcademicYear::where('is_active', true)->first();
        if (! $activeYear) {
            return response()->json(['message' => 'No active academic year is set.'], 422);
        }

        // BR-09: one student / one thesis.
        $existing = Proposal::where('student_id', $request->user()->id)
            ->whereIn('status', ['submitted', 'scheduled', 'reviewed', 'accepted'])
            ->exists();

        if ($existing) {
            return response()->json(['message' => 'You already have an active proposal (BR-09).'], 422);
        }

        $proposal = Proposal::create([
            'student_id' => $request->user()->id,
            'academic_year_id' => $activeYear->id,
            'title' => $data['title'],
            'abstract' => $data['abstract'] ?? null,
            'status' => 'submitted',
            'submitted_at' => now(),
        ]);

        return response()->json([
            'message' => 'Proposal submitted successfully.',
            'proposal' => $proposal,
        ], 201);
    }

    /**
     * Assign a thesis advisor (EUC-15 / SUC-12). SGC only.
     */
    public function assignAdvisor(Request $request, Proposal $proposal)
    {
        $data = $request->validate([
            'advisor_id' => ['required', 'exists:users,id'],
        ]);

        $advisor = User::findOrFail($data['advisor_id']);
        if (! in_array($advisor->role, ['Advisor', 'Examiner', 'UGPC', 'GPC', 'SGC', 'IPC', 'DepartmentHead'])) {
            return response()->json(['message' => 'The selected user cannot act as an advisor.'], 422);
        }

        $proposal->update(['advisor_id' => $data['advisor_id']]);

        return response()->json([
            'message' => 'Thesis advisor assigned successfully.',
            'proposal' => $proposal->load('advisor'),
        ]);
    }

    /**
     * Record proposal review result (SUC-14 / EUC-17). GPC only.
     */
    public function recordReviewResult(Request $request, Proposal $proposal)
    {
        $data = $request->validate([
            'reviewer_recommendation' => ['required', Rule::in(['Accepted', 'Accepted on condition', 'Rejected'])],
            'modified_title' => ['nullable', 'string', 'max:255'],
            'general_comment' => ['nullable', 'string'],
        ]);

        $status = match ($data['reviewer_recommendation']) {
            'Accepted' => 'accepted',
            'Accepted on condition' => 'major_revision',
            'Rejected' => 'rejected',
        };

        $proposal->update(array_merge($data, ['status' => $status]));

        return response()->json([
            'message' => 'Proposal review result recorded successfully.',
            'proposal' => $proposal->fresh(),
        ]);
    }
}
