<?php

namespace App\Http\Controllers;

use App\Models\AcademicYear;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class AcademicYearController extends Controller
{
    public function index()
    {
        return response()->json(['academic_years' => AcademicYear::orderByDesc('id')->get()]);
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'year' => ['required', 'string', 'max:20'],
            'semester' => ['required', 'string', 'max:20'],
            'is_active' => ['boolean'],
        ]);

        if ($request->boolean('is_active')) {
            AcademicYear::query()->update(['is_active' => false]);
        }

        $year = AcademicYear::create($data);

        return response()->json(['message' => 'Academic year created.', 'academic_year' => $year], 201);
    }

    public function update(Request $request, AcademicYear $academicYear)
    {
        $data = $request->validate([
            'year' => ['sometimes', 'string', 'max:20'],
            'semester' => ['sometimes', 'string', 'max:20'],
            'is_active' => ['sometimes', 'boolean'],
        ]);

        if (! empty($data['is_active'])) {
            AcademicYear::where('id', '!=', $academicYear->id)->update(['is_active' => false]);
        }

        $academicYear->update($data);

        return response()->json(['message' => 'Academic year updated.', 'academic_year' => $academicYear]);
    }

    public function destroy(AcademicYear $academicYear)
    {
        $academicYear->delete();

        return response()->json(['message' => 'Academic year deleted.']);
    }
}
