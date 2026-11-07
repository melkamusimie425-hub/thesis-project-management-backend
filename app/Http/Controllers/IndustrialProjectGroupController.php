<?php

namespace App\Http\Controllers;

use App\Models\AcademicYear;
use App\Models\IndustrialProjectGroup;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

class IndustrialProjectGroupController extends Controller
{
    /**
     * List groups. Advisors see only their groups; students see their group.
     */
    public function index(Request $request)
    {
        $user = $request->user();
        $query = IndustrialProjectGroup::with([
            'members:id,first_name,middle_name,last_name,id_number,email,role',
            'advisor:id,first_name,middle_name,last_name,role',
            'academicYear:id,year,semester',
        ])->latest();

        if ($user->role === 'Advisor') {
            $query->where('advisor_id', $user->id);
        } elseif ($user->role === 'Student') {
            $query->whereHas('members', fn ($q) => $q->where('users.id', $user->id));
        }

        if ($status = $request->get('status')) {
            $query->where('status', $status);
        }

        return response()->json(['groups' => $query->paginate(20)]);
    }

    /**
     * Form an industrial project group (SUC-08). BR-01: max five members.
     */
    public function store(Request $request)
    {
        $data = $request->validate([
            'name' => ['nullable', 'string', 'max:120'],
            'members' => ['required', 'array', 'min:1', 'max:5'],
            'members.*.student_id' => ['required', 'exists:users,id'],
            'members.*.is_leader' => ['boolean'],
            'members.*.agreed' => ['boolean'],
        ]);

        $activeYear = AcademicYear::where('is_active', true)->first();
        if (! $activeYear) {
            return response()->json(['message' => 'No active academic year is set.'], 422);
        }

        // BR-02: each student may belong to only one group.
        $studentIds = array_column($data['members'], 'student_id');
        $existing = DB::table('group_members')
            ->whereIn('student_id', $studentIds)
            ->pluck('student_id');

        if ($existing->isNotEmpty()) {
            return response()->json([
                'message' => 'Some students already belong to a project group (BR-02).',
                'conflicts' => $existing,
            ], 422);
        }

        $group = DB::transaction(function () use ($data, $request, $activeYear) {
            $group = IndustrialProjectGroup::create([
                'name' => $data['name'] ?? 'IP Group',
                'academic_year_id' => $activeYear->id,
                'created_by' => $request->user()->id,
                'status' => 'pending',
            ]);

            foreach ($data['members'] as $member) {
                $group->members()->attach($member['student_id'], [
                    'is_leader' => $member['is_leader'] ?? false,
                    'agreed' => $member['agreed'] ?? true,
                ]);
            }

            return $group;
        });

        return response()->json([
            'message' => 'Industrial project group formed successfully.',
            'group' => $group->load('members', 'academicYear'),
        ], 201);
    }

    public function show(IndustrialProjectGroup $group)
    {
        return response()->json([
            'group' => $group->load(['members', 'advisor', 'titles', 'academicYear']),
        ]);
    }

    /**
     * Assign a project advisor (SUC-03 / EUC-03). UGPC only.
     */
    public function assignAdvisor(Request $request, IndustrialProjectGroup $group)
    {
        $data = $request->validate([
            'advisor_id' => ['required', 'exists:users,id'],
        ]);

        $advisor = User::findOrFail($data['advisor_id']);
        if (! in_array($advisor->role, ['Advisor', 'Examiner', 'UGPC', 'GPC', 'SGC', 'IPC', 'DepartmentHead'])) {
            return response()->json(['message' => 'The selected user cannot act as an advisor.'], 422);
        }

        $group->update([
            'advisor_id' => $data['advisor_id'],
            'status' => $group->status === 'title_approved' ? 'advising' : $group->status,
        ]);

        return response()->json([
            'message' => 'Project advisor assigned successfully.',
            'group' => $group->load('advisor'),
        ]);
    }
}
