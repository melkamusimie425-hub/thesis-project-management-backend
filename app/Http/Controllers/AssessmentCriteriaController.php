<?php

namespace App\Http\Controllers;

use App\Models\AssessmentCriteria;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class AssessmentCriteriaController extends Controller
{
    public function index(Request $request)
    {
        $query = AssessmentCriteria::where('is_active', true)->orderBy('applies_to')->orderBy('category');

        if ($applies = $request->get('applies_to')) {
            $query->where('applies_to', $applies);
        }

        return response()->json(['criteria' => $query->get()]);
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:120'],
            'category' => ['required', 'string', 'max:80'],
            'applies_to' => ['required', Rule::in(['ip_one', 'ip_two', 'proposal', 'thesis'])],
            'weight' => ['required', 'numeric', 'min:0', 'max:100'],
            'is_active' => ['boolean'],
        ]);

        $criteria = AssessmentCriteria::create($data);

        return response()->json(['message' => 'Criteria created.', 'criteria' => $criteria], 201);
    }

    public function update(Request $request, AssessmentCriteria $assessmentCriteria)
    {
        $data = $request->validate([
            'name' => ['sometimes', 'string', 'max:120'],
            'category' => ['sometimes', 'string', 'max:80'],
            'applies_to' => ['sometimes', Rule::in(['ip_one', 'ip_two', 'proposal', 'thesis'])],
            'weight' => ['sometimes', 'numeric', 'min:0', 'max:100'],
            'is_active' => ['sometimes', 'boolean'],
        ]);

        $assessmentCriteria->update($data);

        return response()->json(['message' => 'Criteria updated.', 'criteria' => $assessmentCriteria->fresh()]);
    }

    public function destroy(AssessmentCriteria $assessmentCriteria)
    {
        $assessmentCriteria->delete();

        return response()->json(['message' => 'Criteria removed.']);
    }
}
