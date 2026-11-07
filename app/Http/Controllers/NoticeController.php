<?php

namespace App\Http\Controllers;

use App\Models\Notice;
use Illuminate\Http\Request;

class NoticeController extends Controller
{
    /**
     * List announcements. Filtered by recipient role for the requesting user.
     */
    public function index(Request $request)
    {
        $user = $request->user();

        $notices = Notice::with('sender:id,first_name,middle_name,last_name,role')
            ->where(function ($q) use ($user) {
                $q->where('recipient_role', 'all')
                  ->orWhere('recipient_role', $user->role)
                  ->orWhere('recipient_role', $user->program);
            })
            ->latest()
            ->get();

        return response()->json(['notices' => $notices]);
    }

    /**
     * Send an announcement (SUC-04 / EUC-06). Coordinators only.
     */
    public function store(Request $request)
    {
        $data = $request->validate([
            'recipient_role' => ['required', 'string'],
            'subject' => ['required', 'string', 'max:200'],
            'message' => ['required', 'string'],
            'is_pinned' => ['boolean'],
        ]);

        $notice = Notice::create(array_merge($data, [
            'sender_id' => $request->user()->id,
        ]));

        return response()->json([
            'message' => 'Announcement sent successfully.',
            'notice' => $notice->load('sender:id,first_name,middle_name,last_name,role'),
        ], 201);
    }

    public function destroy(Notice $notice)
    {
        $notice->delete();

        return response()->json(['message' => 'Announcement deleted.']);
    }
}
