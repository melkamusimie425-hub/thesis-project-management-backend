<?php

namespace App\Http\Controllers;

use App\Models\Notice;
use Illuminate\Http\Request;

class NoticeController extends Controller
{
    /**
     * List announcements. Filtered by recipient role for the requesting user.
     * Expired notices (expires_at in the past) are automatically excluded.
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
            ->active()
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
            'expires_at' => ['nullable', 'date', 'after_or_equal:today'],
        ]);

        $notice = Notice::create(array_merge($data, [
            'sender_id' => $request->user()->id,
        ]));

        return response()->json([
            'message' => 'Announcement sent successfully.',
            'notice' => $notice->load('sender:id,first_name,middle_name,last_name,role'),
        ], 201);
    }

    /**
     * Delete an announcement. The sender or an Administrator/Coordinator may delete.
     */
    public function destroy(Request $request, Notice $notice)
    {
        $user = $request->user();

        // Only the original sender or an Administrator can delete a notice.
        if ($notice->sender_id !== $user->id
            && ! in_array($user->role, ['Administrator', 'UGPC', 'GPC', 'SGC', 'IPC'], true)) {
            return response()->json([
                'message' => 'You do not have permission to delete this announcement.',
            ], 403);
        }

        $notice->delete();

        return response()->json(['message' => 'Announcement deleted.']);
    }
}
