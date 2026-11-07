<?php

namespace App\Http\Controllers;

use App\Models\IndustrialProjectGroup;
use App\Models\ProjectTitle;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class ProjectTitleController extends Controller
{
    /**
     * List titles for a group.
     */
    public function index(Request $request)
    {
        $titles = ProjectTitle::with('group.members:id,first_name,middle_name,last_name,id_number')
            ->when($request->get('group_id'), fn ($q, $id) => $q->where('group_id', $id))
            ->when($request->get('status'), fn ($q, $s) => $q->where('status', $s))
            ->latest()
            ->get();

        return response()->json(['titles' => $titles]);
    }

    /**
     * Submit industrial project titles (SUC-09 / EUC-07). BR-03: max three titles.
     */
    public function store(Request $request)
    {
        $data = $request->validate([
            'group_id' => ['required', 'exists:industrial_project_groups,id'],
            'titles' => ['required', 'array', 'min:1', 'max:3'],
            'titles.*' => ['required', 'string', 'max:255', 'distinct'],
        ]);

        $group = IndustrialProjectGroup::with('members')->findOrFail($data['group_id']);

        // Ensure the requesting student is a member of the group.
        if ($request->user()->role === 'Student'
            && ! $group->members->contains('id', $request->user()->id)) {
            return response()->json(['message' => 'You are not a member of this group.'], 403);
        }

        // Replace any existing pending titles for this group.
        ProjectTitle::where('group_id', $group->id)->where('status', 'pending')->delete();

        foreach ($data['titles'] as $index => $title) {
            ProjectTitle::create([
                'group_id' => $group->id,
                'title' => $title,
                'title_order' => $index + 1,
                'status' => 'pending',
            ]);
        }

        $group->update(['status' => 'title_submitted']);

        return response()->json([
            'message' => 'Project titles submitted successfully.',
            'titles' => $group->fresh()->titles,
        ], 201);
    }

    /**
     * Review and approve/reject a title (EUC-01 / SUC-02). IPC only.
     */
    public function update(Request $request, ProjectTitle $title)
    {
        $data = $request->validate([
            'status' => ['required', Rule::in(['approved', 'rejected'])],
            'reviewer_comment' => ['nullable', 'string'],
        ]);

        $title->update($data);

        if ($data['status'] === 'approved') {
            // Make this the approved title; reject siblings.
            $title->group->titles()
                ->where('id', '!=', $title->id)
                ->update(['status' => 'rejected']);

            $title->group->update([
                'approved_title' => $title->title,
                'status' => 'title_approved',
            ]);
        }

        return response()->json([
            'message' => 'Title '.$data['status'].' successfully.',
            'title' => $title->fresh(),
        ]);
    }

    public function destroy(ProjectTitle $title)
    {
        $title->delete();

        return response()->json(['message' => 'Title removed.']);
    }
}
