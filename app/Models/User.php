<?php

namespace App\Models;

use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

#[Fillable([
    'first_name', 'middle_name', 'last_name', 'gender', 'id_number',
    'email', 'phone', 'program', 'role', 'salutation', 'password', 'is_active', 'avatar_path',
])]
#[Hidden(['password', 'remember_token'])]
class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, Notifiable, HasApiTokens;

    /**
     * The role hierarchy mapping. Each role may inherit the capabilities of
     * the roles listed below it so the role middleware can grant access.
     */
    public function hasRole(string|array $roles): bool
    {
        $roles = (array) $roles;

        return in_array($this->role, $roles, true);
    }

    public function getFullNameAttribute(): string
    {
        return trim("{$this->first_name} {$this->middle_name} {$this->last_name}");
    }

    public function getAvatarUrlAttribute(): ?string
    {
        if (! $this->avatar_path) {
            return null;
        }

        // Serve through our own API endpoint — no symlink needed on Windows.
        return url("/api/avatar/{$this->id}");
    }

    public function initials(): string
    {
        $first = mb_substr($this->first_name ?? '', 0, 1);
        $last = mb_substr($this->last_name ?? '', 0, 1);

        return strtoupper($first.$last);
    }

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'is_active' => 'boolean',
        ];
    }

    // ---- Relationships ------------------------------------------------------

    /** Groups this student belongs to (BR-02: only one). */
    public function projectGroups(): BelongsToMany
    {
        return $this->belongsToMany(IndustrialProjectGroup::class, 'group_members', 'student_id', 'group_id')
            ->withPivot(['is_leader', 'agreed'])
            ->withTimestamps();
    }

    public function advisingGroups(): HasMany
    {
        return $this->hasMany(IndustrialProjectGroup::class, 'advisor_id');
    }

    public function proposals(): HasMany
    {
        return $this->hasMany(Proposal::class, 'student_id');
    }

    public function theses(): HasMany
    {
        return $this->hasMany(Thesis::class, 'student_id');
    }

    public function uploadedDocuments(): HasMany
    {
        return $this->hasMany(Document::class, 'uploaded_by');
    }

    public function noticesSent(): HasMany
    {
        return $this->hasMany(Notice::class, 'sender_id');
    }
}
