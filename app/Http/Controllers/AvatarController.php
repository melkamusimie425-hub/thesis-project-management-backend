<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Throwable;

class AvatarController extends Controller
{
    /**
     * Upload profile photo.
     * Accepts three formats:
     *  1. multipart/form-data  (standard browser upload)
     *  2. JSON  { "data": "data:image/png;base64,..." }  (fallback)
     *  3. raw binary body with Content-Type: image/*     (fallback 2)
     */
    public function upload(Request $request)
    {
        try {
            $user = $request->user();

            // ── Method 1: standard multipart ──────────────────────────
            if ($request->hasFile('avatar')) {
                $file = $request->file('avatar');
                if ($file && $file->isValid()) {
                    $ext      = strtolower($file->getClientOriginalExtension()) ?: 'jpg';
                    $contents = file_get_contents($file->getRealPath());
                    if ($contents && $this->isImage($contents)) {
                        return $this->save($user, $contents, $ext);
                    }
                }
            }

            // ── Method 2: base64 JSON body ────────────────────────────
            $raw = $request->getContent();
            if ($raw) {
                $json = @json_decode($raw, true);
                if (isset($json['data']) && is_string($json['data'])) {
                    if (preg_match('/^data:image\/(\w+);base64,(.+)$/s', $json['data'], $m)) {
                        $ext      = strtolower($m[1] === 'jpeg' ? 'jpg' : $m[1]);
                        $contents = base64_decode($m[2], true);
                        if ($contents && $this->isImage($contents)) {
                            return $this->save($user, $contents, $ext);
                        }
                    }
                }

                // ── Method 3: raw binary body ─────────────────────────
                $ct = $request->header('Content-Type', '');
                if (str_starts_with($ct, 'image/')) {
                    if ($this->isImage($raw)) {
                        $ext = match (true) {
                            str_contains($ct, 'png')  => 'png',
                            str_contains($ct, 'gif')  => 'gif',
                            str_contains($ct, 'webp') => 'webp',
                            default                   => 'jpg',
                        };
                        return $this->save($user, $raw, $ext);
                    }
                }
            }

            return response()->json([
                'message' => 'Please select a valid image file (JPG, PNG, GIF or WEBP).',
                'errors'  => ['avatar' => ['No valid image received.']],
            ], 422);

        } catch (Throwable $e) {
            return response()->json([
                'message' => 'Upload failed: ' . $e->getMessage(),
                'errors'  => ['avatar' => [$e->getMessage()]],
            ], 500);
        }
    }

    /**
     * Serve the avatar image directly without a storage symlink.
     * Public endpoint — no auth needed so <img src="..."> works in browsers.
     */
    public function serve(User $user)
    {
        try {
            if (! $user->avatar_path) {
                return response('', 404);
            }

            // Try the direct path first (new portable location: storage/app/avatars/)
            if (Storage::disk('local')->exists($user->avatar_path)) {
                $contents = Storage::disk('local')->get($user->avatar_path);
                $mime     = $this->detectMime($contents);
                return response($contents, 200)
                    ->header('Content-Type', $mime)
                    ->header('Cache-Control', 'public, max-age=86400')
                    ->header('Content-Disposition', 'inline');
            }

            // Fallback: try private/ prefix (old location)
            $privatePath = 'private/' . $user->avatar_path;
            if (Storage::disk('local')->exists($privatePath)) {
                $contents = Storage::disk('local')->get($privatePath);
                $mime     = $this->detectMime($contents);
                return response($contents, 200)
                    ->header('Content-Type', $mime)
                    ->header('Cache-Control', 'public, max-age=86400')
                    ->header('Content-Disposition', 'inline');
            }

            return response('', 404);

        } catch (Throwable $e) {
            return response('', 500);
        }
    }

    /**
     * Remove profile photo.
     */
    public function remove(Request $request)
    {
        try {
            $user = $request->user();

            if ($user->avatar_path) {
                // Remove from new location
                if (Storage::disk('local')->exists($user->avatar_path)) {
                    Storage::disk('local')->delete($user->avatar_path);
                }
                // Remove from old private/ location if it exists
                $privatePath = 'private/' . $user->avatar_path;
                if (Storage::disk('local')->exists($privatePath)) {
                    Storage::disk('local')->delete($privatePath);
                }
            }

            $user->update(['avatar_path' => null]);

            return response()->json(['message' => 'Profile photo removed.']);

        } catch (Throwable $e) {
            return response()->json(['message' => 'Could not remove photo.'], 500);
        }
    }

    // ── Private helpers ───────────────────────────────────────────────

    private function save(User $user, string $contents, string $ext): \Illuminate\Http\JsonResponse
    {
        // Delete old avatar from both possible locations
        if ($user->avatar_path) {
            if (Storage::disk('local')->exists($user->avatar_path)) {
                Storage::disk('local')->delete($user->avatar_path);
            }
            // Also try the private path for backwards compatibility
            $privatePath = 'private/' . $user->avatar_path;
            if (Storage::disk('local')->exists($privatePath)) {
                Storage::disk('local')->delete($privatePath);
            }
        }

        // Store in storage/app/avatars/{user_id}/photo.{ext}
        // This folder is tracked by Git so photos survive PC transfers
        $path = "avatars/{$user->id}/photo.{$ext}";
        Storage::disk('local')->put($path, $contents);
        $user->update(['avatar_path' => $path]);

        return response()->json([
            'message'    => 'Profile photo updated successfully.',
            'avatar_url' => url("/api/avatar/{$user->id}") . '?t=' . time(),
        ]);
    }

    /** Check magic bytes to confirm it is a real image. */
    private function isImage(string $data): bool
    {
        if (strlen($data) < 4) {
            return false;
        }
        $b = $data;
        // JPEG
        if ("\xFF\xD8\xFF" === substr($b, 0, 3)) return true;
        // PNG
        if ("\x89PNG" === substr($b, 0, 4)) return true;
        // GIF
        if ('GIF8' === substr($b, 0, 4)) return true;
        // WEBP
        if ('RIFF' === substr($b, 0, 4) && strlen($b) >= 12 && 'WEBP' === substr($b, 8, 4)) return true;
        return false;
    }

    /** Detect MIME type from magic bytes. */
    private function detectMime(string $data): string
    {
        if (strlen($data) < 4) return 'application/octet-stream';
        if ("\xFF\xD8\xFF" === substr($data, 0, 3)) return 'image/jpeg';
        if ("\x89PNG"      === substr($data, 0, 4)) return 'image/png';
        if ('GIF8'         === substr($data, 0, 4)) return 'image/gif';
        if ('RIFF'         === substr($data, 0, 4)) return 'image/webp';
        return 'image/jpeg';
    }
}
