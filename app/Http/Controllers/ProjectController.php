<?php

namespace App\Http\Controllers;

use App\Models\ActivityLog;
use App\Models\Project;
use Illuminate\Http\Request;

class ProjectController extends Controller
{
    /**
     * Halaman daftar project milik user (yang dibuat atau yang diikuti).
     *
     * Semua angka yang dibutuhkan kartu dihitung sekaligus lewat withCount()
     * supaya view tidak perlu query ulang untuk tiap project (hindari N+1):
     *  - tasks_count       : total task di project
     *  - done_tasks_count  : jumlah task berstatus "done"
     *  - members_count     : jumlah anggota tim
     * Daftar anggota (id & nama saja) di-load sekali untuk avatar di kartu.
     */
    public function index()
    {
        $projects = auth()->user()->projects()
            ->withCount([
                'tasks',
                'tasks as done_tasks_count' => fn ($q) => $q->where('status', 'done'),
                'members',
            ])
            ->with('members:id,name')
            ->latest()
            ->get();

        return view('projects.index', compact('projects'));
    }

    /**
     * Menampilkan form untuk membuat project baru.
     */
    public function create()
    {
        return view('projects.create');
    }

    /**
     * Menyimpan project baru ke database.
     * Pembuat project otomatis menjadi manager dan aktivitasnya dicatat di log.
     */
    public function store(Request $request)
    {
        // Validasi input form
        $data = $request->validate([
            'name'        => 'required|string|max:255',
            'description' => 'nullable|string',
            'category'    => 'nullable|string|max:100',
            'priority'    => 'required|in:low,medium,high',
            'start_date'  => 'nullable|date',
            'deadline'    => 'nullable|date|after_or_equal:start_date',
        ]);

        // Simpan project, tandai siapa pembuatnya
        $data['created_by'] = auth()->id();
        $project = Project::create($data);

        // Pembuat otomatis jadi manager
        $project->members()->attach(auth()->id(), ['role' => 'manager']);
        ActivityLog::record($project->id, auth()->user()->name . ' membuat project ini');

        return redirect()->route('projects.index')->with('success', 'Project berhasil dibuat.');
    }

    /**
     * Halaman detail project: info, anggota tim, papan kanban, dan log aktivitas.
     * Hanya anggota project yang boleh membuka.
     */
    public function show(Project $project)
    {
        $this->authorizeMember($project);

        // Load task (beserta jumlah komentar & penanggung jawab) dan anggota sekaligus
        $project->load([
            'tasks' => fn ($q) => $q->withCount('comments')->with('assignee'),
            'members',
        ]);

        // 10 aktivitas terbaru untuk panel aktivitas
        $activities = $project->activities()->with('user')->limit(10)->get();

        return view('projects.show', compact('project', 'activities'));
    }

    /**
     * Menampilkan form edit project. Khusus manager.
     */
    public function edit(Project $project)
    {
        $this->authorizeManager($project);

        return view('projects.edit', compact('project'));
    }

    /**
     * Menyimpan perubahan project. Khusus manager.
     */
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

    /**
     * Menghapus project beserta isinya (task, komentar, anggota, log ikut terhapus
     * karena foreign key memakai cascadeOnDelete). Khusus manager.
     */
    public function destroy(Project $project)
    {
        $this->authorizeManager($project);
        $project->delete();

        return redirect()->route('projects.index')->with('success', 'Project berhasil dihapus.');
    }

    // ------------------------------------------------------------------
    // Helper hak akses sederhana
    // ------------------------------------------------------------------

    /**
     * Pastikan user yang login adalah anggota project (manager atau member).
     * Kalau bukan, tampilkan error 403.
     */
    private function authorizeMember(Project $project)
    {
        abort_unless($project->members()->where('user_id', auth()->id())->exists(), 403);
    }

    /**
     * Pastikan user yang login adalah manager project.
     * Kalau bukan, tampilkan error 403.
     */
    private function authorizeManager(Project $project)
    {
        abort_unless(
            $project->members()->where('user_id', auth()->id())->wherePivot('role', 'manager')->exists(),
            403
        );
    }
}