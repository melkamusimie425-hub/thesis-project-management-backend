<?php

namespace App\Http\Controllers;

use App\Models\IndustrialProjectGroup;
use App\Models\Notice;
use App\Models\Proposal;
use App\Models\Schedule;
use App\Models\Thesis;
use App\Models\User;
use Illuminate\Http\Request;

class DashboardController extends Controller
{
    /**
     * Aggregated stats tailored to the requesting user's role.
     */
    public function stats(Request $request)
    {
        $user = $request->user();

        $stats = match ($user->role) {
            'Administrator' => $this->adminStats(),
            'DepartmentHead' => $this->departmentStats(),
            'UGPC', 'IPC' => $this->ugpcStats(),
            'GPC', 'SGC' => $this->gpcStats(),
            'Advisor' => $this->advisorStats($user),
            'Examiner' => $this->examinerStats($user),
            'Student' => $this->studentStats($user),
            'GradStudent' => $this->gradStudentStats($user),
            default => ['total_notices' => Notice::count()],
        };

        $stats['recent_notices'] = Notice::with('sender:id,first_name,middle_name,last_name,role')
            ->where(function ($q) use ($user) {
                $q->where('recipient_role', 'all')
                  ->orWhere('recipient_role', $user->role)
                  ->orWhere('recipient_role', $user->program);
            })
            ->latest()
            ->limit(5)
            ->get();

        return response()->json($stats);
    }

    private function adminStats(): array
    {
        return [
            'users' => User::count(),
            'students' => User::whereIn('role', ['Student', 'GradStudent'])->count(),
            'instructors' => User::whereIn('role', ['Advisor', 'Examiner'])->count(),
            'project_groups' => IndustrialProjectGroup::count(),
            'theses' => Thesis::count(),
            'proposals' => Proposal::count(),
        ];
    }

    private function departmentStats(): array
    {
        return [
            'project_groups' => IndustrialProjectGroup::count(),
            'theses' => Thesis::count(),
            'proposals' => Proposal::count(),
            'completed_projects' => IndustrialProjectGroup::where('status', 'completed')->count(),
            'completed_theses' => Thesis::whereIn('status', ['passed', 'completed'])->count(),
        ];
    }

    private function ugpcStats(): array
    {
        return [
            'project_groups' => IndustrialProjectGroup::count(),
            'pending_advisor' => IndustrialProjectGroup::whereNull('advisor_id')->count(),
            'scheduled' => Schedule::where('work_type', 'IndustrialProjectGroup')->count(),
            'titles_submitted' => \App\Models\ProjectTitle::where('status', 'pending')->count(),
        ];
    }

    private function gpcStats(): array
    {
        return [
            'proposals' => Proposal::count(),
            'pending_advisor' => Proposal::whereNull('advisor_id')->count(),
            'theses' => Thesis::count(),
            'scheduled_proposals' => Schedule::where('work_type', 'Proposal')->count(),
            'scheduled_theses' => Schedule::where('work_type', 'Thesis')->count(),
        ];
    }

    private function advisorStats($user): array
    {
        $groups = IndustrialProjectGroup::where('advisor_id', $user->id)->count();
        $theses = Thesis::where('advisor_id', $user->id)->count();
        $proposals = Proposal::where('advisor_id', $user->id)->count();

        return [
            'advising_groups' => $groups,
            'advising_theses' => $theses,
            'advising_proposals' => $proposals,
            'upcoming_defenses' => Schedule::where('chair_id', $user->id)
                ->orWhere('examiner_one_id', $user->id)
                ->orWhere('examiner_two_id', $user->id)
                ->where('status', 'scheduled')->count(),
        ];
    }

    private function examinerStats($user): array
    {
        return [
            'assigned_defenses' => Schedule::where(function ($q) use ($user) {
                $q->where('examiner_one_id', $user->id)
                  ->orWhere('examiner_two_id', $user->id)
                  ->orWhere('chair_id', $user->id);
            })->where('status', 'scheduled')->count(),
            'completed_defenses' => Schedule::where(function ($q) use ($user) {
                $q->where('examiner_one_id', $user->id)
                  ->orWhere('examiner_two_id', $user->id)
                  ->orWhere('chair_id', $user->id);
            })->where('status', 'completed')->count(),
        ];
    }

    private function studentStats($user): array
    {
        $group = IndustrialProjectGroup::whereHas('members', fn ($q) => $q->where('users.id', $user->id))->first();

        return [
            'has_group' => (bool) $group,
            'group_status' => $group?->status,
            'group_title' => $group?->approved_title,
            'advisor' => $group?->advisor?->full_name,
            'defense_schedule' => $group ? Schedule::where('work_type', 'IndustrialProjectGroup')
                ->where('work_id', $group->id)->where('status', 'scheduled')->first() : null,
        ];
    }

    private function gradStudentStats($user): array
    {
        $proposal = Proposal::where('student_id', $user->id)->latest()->first();
        $thesis = Thesis::where('student_id', $user->id)->latest()->first();

        return [
            'proposal_status' => $proposal?->status,
            'thesis_status' => $thesis?->status,
            'advisor' => ($thesis?->advisor ?? $proposal?->advisor)?->full_name,
            'defense_schedule' => Schedule::where('work_type', 'Thesis')
                ->where('work_id', $thesis?->id ?? 0)->where('status', 'scheduled')->first(),
        ];
    }
}
