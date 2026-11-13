<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;

class UserController extends Controller
{
    public function index(Request $request)
    {
        $query = User::query()->latest();

        if ($search = $request->get('search')) {
            $query->where(function ($q) use ($search) {
                $q->where('first_name', 'like', "%{$search}%")
                    ->orWhere('last_name', 'like', "%{$search}%")
                    ->orWhere('email', 'like', "%{$search}%")
                    ->orWhere('id_number', 'like', "%{$search}%");
            });
        }

        if ($role = $request->get('role')) {
            $query->where('role', $role);
        }

        $paginated = $query->paginate(50);

        // Append avatar_url and full_name to every user in the result
        $paginated->getCollection()->transform(fn ($u) => $this->appendExtras($u));

        return response()->json($paginated);
    }

    public function show(User $user)
    {
        return response()->json(['user' => $this->appendExtras($user)]);
    }

    /**
     * Create a new user account (SUC-16).
     */
    public function store(Request $request)
    {
        $data = $request->validate([
            'first_name' => ['required', 'string', 'max:80'],
            'middle_name' => ['nullable', 'string', 'max:80'],
            'last_name' => ['required', 'string', 'max:80'],
            'gender' => ['nullable', Rule::in(['Male', 'Female'])],
            'id_number' => ['required', 'string', 'unique:users,id_number'],
            'email' => ['required', 'email', 'unique:users,email'],
            'phone' => ['nullable', 'string', 'max:30'],
            'program' => ['nullable', Rule::in(['Undergraduate', 'Graduate', 'Staff'])],
            'role' => ['required', Rule::in([
                'Administrator', 'DepartmentHead', 'UGPC', 'GPC', 'IPC', 'SGC',
                'Student', 'GradStudent', 'Advisor', 'Examiner', 'Guest',
            ])],
            'salutation' => ['nullable', 'string', 'max:30'],
            'password' => ['required', 'string', 'min:6', 'confirmed'],
            'is_active' => ['boolean'],
        ]);

        $user = User::create(Arr::except($data, ['password_confirmation']));

        return response()->json([
            'message' => 'User account created successfully.',
            'user' => $this->appendExtras($user),
        ], 201);
    }

    /**
     * Update an existing user account (SUC-18).
     */
    public function update(Request $request, User $user)
    {
        $data = $request->validate([
            'first_name' => ['sometimes', 'string', 'max:80'],
            'middle_name' => ['sometimes', 'nullable', 'string', 'max:80'],
            'last_name' => ['sometimes', 'string', 'max:80'],
            'gender' => ['sometimes', 'nullable', Rule::in(['Male', 'Female'])],
            'id_number' => ['sometimes', 'string', Rule::unique('users', 'id_number')->ignore($user)],
            'email' => ['sometimes', 'email', Rule::unique('users', 'email')->ignore($user)],
            'phone' => ['sometimes', 'nullable', 'string', 'max:30'],
            'program' => ['sometimes', Rule::in(['Undergraduate', 'Graduate', 'Staff'])],
            'role' => ['sometimes', Rule::in([
                'Administrator', 'DepartmentHead', 'UGPC', 'GPC', 'IPC', 'SGC',
                'Student', 'GradStudent', 'Advisor', 'Examiner', 'Guest',
            ])],
            'salutation' => ['sometimes', 'nullable', 'string', 'max:30'],
            'password' => ['sometimes', 'nullable', 'string', 'min:6', 'confirmed'],
            'is_active' => ['sometimes', 'boolean'],
        ]);

        if (! empty($data['password'])) {
            $user->password = $data['password'];
            unset($data['password']);
        }

        $user->fill(Arr::except($data, ['password_confirmation']))->save();

        return response()->json([
            'message' => 'User account updated successfully.',
            'user' => $this->appendExtras($user->fresh()),
        ]);
    }

    /**
     * Admin uploads/changes a specific user's profile photo.
     */
    public function uploadAvatar(Request $request, User $user)
    {
        $raw = $request->getContent();

        if ($raw) {
            $json = @json_decode($raw, true);
            if (isset($json['data']) && is_string($json['data'])) {
                if (preg_match('/^data:image\/(\w+);base64,(.+)$/s', $json['data'], $m)) {
                    $ext = strtolower($m[1] === 'jpeg' ? 'jpg' : $m[1]);
                    $contents = base64_decode($m[2], true);

                    if ($contents && strlen($contents) >= 4) {
                        // Delete old avatar
                        if ($user->avatar_path && Storage::disk('local')->exists($user->avatar_path)) {
                            Storage::disk('local')->delete($user->avatar_path);
                        }

                        $path = "avatars/{$user->id}/photo.{$ext}";
                        Storage::disk('local')->put($path, $contents);
                        $user->update(['avatar_path' => $path]);

                        return response()->json([
                            'message' => 'Profile photo updated successfully.',
                            'avatar_url' => url("/api/avatar/{$user->id}").'?t='.time(),
                        ]);
                    }
                }
            }
        }

        return response()->json(['message' => 'No valid image received.'], 422);
    }

    /**
     * Admin removes a specific user's profile photo.
     */
    public function removeAvatar(User $user)
    {
        if ($user->avatar_path && Storage::disk('local')->exists($user->avatar_path)) {
            Storage::disk('local')->delete($user->avatar_path);
        }
        $user->update(['avatar_path' => null]);

        return response()->json(['message' => 'Profile photo removed.']);
    }

    /**
     * Delete a user account (SUC-19).
     * An administrator cannot delete their own account.
     */
    public function destroy(Request $request, User $user)
    {
        if ($user->id === $request->user()->id) {
            return response()->json([
                'message' => 'You cannot delete your own account.',
            ], 422);
        }

        // Delete avatar too
        if ($user->avatar_path && Storage::disk('local')->exists($user->avatar_path)) {
            Storage::disk('local')->delete($user->avatar_path);
        }
        $user->delete();

        return response()->json(['message' => 'User account deleted successfully.']);
    }

    public function instructors()
    {
        return response()->json([
            'instructors' => User::whereIn('role', [
                'Advisor', 'Examiner', 'UGPC', 'GPC', 'SGC', 'IPC', 'DepartmentHead',
            ])
                ->where('is_active', true)
                ->orderBy('last_name')
                ->get(['id', 'first_name', 'middle_name', 'last_name', 'id_number', 'email', 'role', 'salutation']),
        ]);
    }

    public function students(Request $request)
    {
        $program = $request->get('program');
        $query = User::where('is_active', true)->whereIn('role', ['Student', 'GradStudent']);

        if ($program === 'Undergraduate') {
            $query->where('role', 'Student');
        } elseif ($program === 'Graduate') {
            $query->where('role', 'GradStudent');
        }

        return response()->json([
            'students' => $query->orderBy('last_name')
                ->get(['id', 'first_name', 'middle_name', 'last_name', 'id_number', 'email']),
        ]);
    }

    // ── Helpers ──────────────────────────────────────────────────────

    private function appendExtras(User $user): User
    {
        // Avatar URL with cache-busting version.
        $user->avatar_url = $user->avatar_path
            ? url("/api/avatar/{$user->id}").'?v='.(substr(md5($user->avatar_path), 0, 8))
            : null;

        // full_name and initials are auto-appended via $appends on the model.
        return $user;
    }
}
