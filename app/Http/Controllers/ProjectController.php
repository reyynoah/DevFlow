<?php

namespace App\Http\Controllers;

use App\Models\Project;
use Illuminate\Http\Request;

class ProjectController extends Controller
{
    // Daftar project milik user (yang dibuat atau yang diikuti)
    public function index()
    {
        $projects = auth()->user()->projects()->withCount('tasks')->latest()->get();
        return view('projects.index', compact('projects'));
    }

    public function create()
    {
        return view('projects.create');
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'name'        => 'required|string|max:255',
            'description' => 'nullable|string',
            'category'    => 'nullable|string|max:100',
            'priority'    => 'required|in:low,medium,high',
            'start_date'  => 'nullable|date',
            'deadline'    => 'nullable|date|after_or_equal:start_date',
        ]);

        $data['created_by'] = auth()->id();
        $project = Project::create($data);

        // Pembuat otomatis jadi manager
        $project->members()->attach(auth()->id(), ['role' => 'manager']);

        return redirect()->route('projects.index')->with('success', 'Project berhasil dibuat.');
    }

    public function show(Project $project)
    {
        $this->authorizeMember($project);
        $project->load(['tasks.assignee', 'members']);
        return view('projects.show', compact('project'));
    }

    public function edit(Project $project)
    {
        $this->authorizeManager($project);
        return view('projects.edit', compact('project'));
    }

    public function update(Request $request, Project $project)
    {
        $this->authorizeManager($project);

        $data = $request->validate([
            'name'        => 'required|string|max:255',
            'description' => 'nullable|string',
            'category'    => 'nullable|string|max:100',
            'priority'    => 'required|in:low,medium,high',
            'start_date'  => 'nullable|date',
            'deadline'    => 'nullable|date|after_or_equal:start_date',
        ]);

        $project->update($data);

        return redirect()->route('projects.index')->with('success', 'Project berhasil diperbarui.');
    }

    public function destroy(Project $project)
    {
        $this->authorizeManager($project);
        $project->delete();

        return redirect()->route('projects.index')->with('success', 'Project berhasil dihapus.');
    }

    // Helper hak akses sederhana
    private function authorizeMember(Project $project)
    {
        abort_unless($project->members()->where('user_id', auth()->id())->exists(), 403);
    }

    private function authorizeManager(Project $project)
    {
        abort_unless(
            $project->members()->where('user_id', auth()->id())->wherePivot('role', 'manager')->exists(),
            403
        );
    }
}