<?php

namespace App\Http\Controllers;

use App\Models\ActivityLog;
use App\Models\Project;
use App\Models\Task;
use App\Models\User;
use Illuminate\Http\Request;

class ProjectMemberController extends Controller
{
    public function store(Request $request, Project $project)
    {
        $this->authorizeManager($project);

        $request->validate([
            'email' => 'required|email|exists:users,email',
            'role'  => 'required|in:manager,member',
        ], [
            'email.exists' => 'Email ini belum terdaftar di DevFlow.',
        ]);

        $user = User::where('email', $request->email)->first();

        if ($project->members()->where('user_id', $user->id)->exists()) {
            return back()->withErrors(['email' => 'User ini sudah menjadi anggota project.']);
        }

        $project->members()->attach($user->id, ['role' => $request->role]);

        ActivityLog::record($project->id, auth()->user()->name . ' menambahkan ' . $user->name . ' sebagai ' . $request->role);

        return back()->with('success', $user->name . ' berhasil ditambahkan ke project.');
    }

    public function update(Request $request, Project $project, User $user)
    {
        $this->authorizeManager($project);
        abort_if($user->id === $project->created_by, 403, 'Role pembuat project tidak bisa diubah.');

        $request->validate(['role' => 'required|in:manager,member']);
        $project->members()->updateExistingPivot($user->id, ['role' => $request->role]);

        ActivityLog::record($project->id, auth()->user()->name . ' mengubah role ' . $user->name . ' menjadi ' . $request->role);

        return back()->with('success', 'Role anggota berhasil diubah.');
    }

    public function destroy(Project $project, User $user)
    {
        $this->authorizeManager($project);
        abort_if($user->id === $project->created_by, 403, 'Pembuat project tidak bisa dikeluarkan.');

        if (! $project->members()->where('user_id', $user->id)->exists()) {
            return back()->with('success', 'User ini sudah tidak menjadi anggota project.');
        }

        $project->members()->detach($user->id);

        Task::where('project_id', $project->id)
            ->where('assigned_to', $user->id)
            ->update(['assigned_to' => null]);

        ActivityLog::record($project->id, auth()->user()->name . ' mengeluarkan ' . $user->name . ' dari project');

        return back()->with('success', 'Anggota berhasil dikeluarkan dari project.');
    }

    private function authorizeManager(Project $project)
    {
        abort_unless(
            $project->members()->where('user_id', auth()->id())->wherePivot('role', 'manager')->exists(),
            403
        );
    }
}