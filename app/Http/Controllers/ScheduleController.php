<?php

namespace App\Http\Controllers;

use App\Models\AcademicYear;
use App\Models\Schedule;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class ScheduleController extends Controller
{
    /**
     * View defense schedules (SUC-07 / EUC-13). Filtered by the requesting
     * user's role — students see only their own, examiners/advisors their own.
     */
    public function index(Request $request)
    {
        $user = $request->user();
        $query = Schedule::with([
            'academicYear:id,year,semester',
            'work',
            'examinerOne:id,first_name,middle_name,last_name',
            'examinerTwo:id,first_name,middle_name,last_name',
            'chair:id,first_name,middle_name,last_name',
        ])->latest('date');

        if ($type = $request->get('type')) {
            $query->where('work_type', $type);
        }

        if ($status = $request->get('status')) {
            $query->where('status', $status);
        }

        // Examiners see schedules they are assigned to.
        if ($user->role === 'Examiner' || $user->role === 'Advisor') {
            $query->where(function ($q) use ($user) {
                $q->where('examiner_one_id', $user->id)
                  ->orWhere('examiner_two_id', $user->id)
                  ->orWhere('chair_id', $user->id);
            });
        }

        return response()->json(['schedules' => $query->paginate(20)]);
    }

    public function show(Schedule $schedule)
    {
        return response()->json(['schedule' => $schedule->load([
            'academicYear', 'work', 'examinerOne', 'examinerTwo', 'chair',
        ])]);
    }

    /**
     * Schedule a defense / proposal review (SUC-05 / SUC-13 / EUC-04 / EUC-16 / EUC-18).
     * BR-07 & BR-10 & BR-14: two examiners must be assigned.
     */
    public function store(Request $request)
    {
        $data = $request->validate([
            'work_type' => ['required', Rule::in(['IndustrialProjectGroup', 'Proposal', 'Thesis'])],
            'work_id' => ['required', 'integer'],
            'date' => ['required', 'date'],
            'time' => ['required', 'date_format:H:i'],
            'place' => ['required', 'string', 'max:120'],
            'room' => ['nullable', 'string', 'max:60'],
            'examiner_one_id' => ['required', 'exists:users,id', 'different:examiner_two_id'],
            'examiner_two_id' => ['required', 'exists:users,id'],
            'chair_id' => ['nullable', 'exists:users,id'],
        ]);

        $activeYear = AcademicYear::where('is_active', true)->first();
        if (! $activeYear) {
            return response()->json(['message' => 'No active academic year is set.'], 422);
        }

        $schedule = Schedule::create([
            'work_type' => $data['work_type'],
            'work_id' => $data['work_id'],
            'academic_year_id' => $activeYear->id,
            'coordinator_id' => $request->user()->id,
            'date' => $data['date'],
            'time' => $data['time'],
            'place' => $data['place'],
            'room' => $data['room'] ?? null,
            'examiner_one_id' => $data['examiner_one_id'],
            'examiner_two_id' => $data['examiner_two_id'],
            'chair_id' => $data['chair_id'] ?? null,
            'status' => 'scheduled',
        ]);

        // Update the parent work's status.
        $this->markWorkScheduled($data['work_type'], $data['work_id']);

        return response()->json([
            'message' => 'Defense scheduled successfully.',
            'schedule' => $schedule->load(['academicYear', 'examinerOne', 'examinerTwo']),
        ], 201);
    }

    public function update(Request $request, Schedule $schedule)
    {
        $data = $request->validate([
            'date' => ['sometimes', 'date'],
            'time' => ['sometimes', 'date_format:H:i'],
            'place' => ['sometimes', 'string', 'max:120'],
            'room' => ['sometimes', 'nullable', 'string', 'max:60'],
            'examiner_one_id' => ['sometimes', 'exists:users,id'],
            'examiner_two_id' => ['sometimes', 'exists:users,id'],
            'chair_id' => ['sometimes', 'nullable', 'exists:users,id'],
            'status' => ['sometimes', Rule::in(['scheduled', 'completed', 'cancelled'])],
        ]);

        $schedule->update($data);

        return response()->json(['message' => 'Schedule updated.', 'schedule' => $schedule->fresh()]);
    }

    public function destroy(Schedule $schedule)
    {
        $schedule->delete();

        return response()->json(['message' => 'Schedule cancelled.']);
    }

    private function markWorkScheduled(string $workType, int $workId): void
    {
        $model = match ($workType) {
            'IndustrialProjectGroup' => \App\Models\IndustrialProjectGroup::class,
            'Proposal' => \App\Models\Proposal::class,
            'Thesis' => \App\Models\Thesis::class,
        };

        $model::where('id', $workId)->update(['status' => 'scheduled']);
    }
}
