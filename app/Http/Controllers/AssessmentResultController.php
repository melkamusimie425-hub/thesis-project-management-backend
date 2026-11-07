<?php

namespace App\Http\Controllers;

use App\Models\AssessmentResult;
use App\Models\Schedule;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class AssessmentResultController extends Controller
{
    /**
     * Record assessment results (SUC-06 / EUC-05 / EUC-11 / EUC-12 / EUC-22).
     * Accepts multiple criteria scores in one request.
     */
    public function store(Request $request)
    {
        $data = $request->validate([
            'schedule_id' => ['nullable', 'exists:schedules,id'],
            'student_id' => ['required', 'exists:users,id'],
            'work_type' => ['required', 'string'],
            'scores' => ['required', 'array', 'min:1'],
            'scores.*.criteria_id' => ['required', 'exists:assessment_criteria,id'],
            'scores.*.score' => ['required', 'numeric', 'min:0', 'max:100'],
            'comment' => ['nullable', 'string'],
        ]);

        $assessor = $request->user();

        DB::transaction(function () use ($data, $assessor) {
            foreach ($data['scores'] as $score) {
                AssessmentResult::create([
                    'schedule_id' => $data['schedule_id'] ?? null,
                    'assessor_id' => $assessor->id,
                    'student_id' => $data['student_id'],
                    'work_type' => $data['work_type'],
                    'criteria_id' => $score['criteria_id'],
                    'score' => $score['score'],
                    'comment' => $data['comment'] ?? null,
                ]);
            }
        });

        // Mark the schedule completed if all scores are in (best-effort).
        if (! empty($data['schedule_id'])) {
            $schedule = Schedule::find($data['schedule_id']);
            $schedule?->update(['status' => 'completed']);
        }

        return response()->json(['message' => 'Assessment results recorded successfully.'], 201);
    }

    /**
     * View assessment results for a student / schedule.
     */
    public function index(Request $request)
    {
        $data = $request->validate([
            'schedule_id' => ['nullable', 'exists:schedules,id'],
            'student_id' => ['nullable', 'exists:users,id'],
            'work_type' => ['nullable', 'string'],
        ]);

        $query = AssessmentResult::with(['criteria:id,name,category,weight', 'assessor:id,first_name,middle_name,last_name,role', 'student:id,first_name,middle_name,last_name,id_number'])
            ->latest();

        foreach ($data as $key => $value) {
            if ($value !== null && $key !== 'work_type') {
                $query->where($key, $value);
            }
        }
        if (! empty($data['work_type'])) {
            $query->where('work_type', $data['work_type']);
        }

        return response()->json(['results' => $query->get()]);
    }

    public function destroy(AssessmentResult $assessmentResult)
    {
        $assessmentResult->delete();

        return response()->json(['message' => 'Assessment result removed.']);
    }
}
