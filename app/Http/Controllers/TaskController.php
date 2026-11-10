<?php

namespace App\Http\Controllers;

use App\Models\ActivityLog;
use App\Models\Project;
use App\Models\Task;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class TaskController extends Controller
{
    private const STATUS_LABEL = [
        'todo'        => 'To Do',
        'in_progress' => 'In Progress',
        'review'      => 'Review',
        'done'        => 'Done',
    ];

    private function rules(Project $project): array
    {
        return [
            'title'       => 'required|string|max:255',
            'description' => 'nullable|string',
            'priority'    => 'required|in:low,medium,high',
            'deadline'    => 'nullable|date',
            'assigned_to' => [
                'nullable',
                Rule::exists('project_members', 'user_id')->where('project_id', $project->id),
            ],
        ];
    }

    public function store(Request $request, Project $project)
    {
        $this->authorizeMember($project);

        $data = $request->validate($this->rules($project));
        $task = $project->tasks()->create($data);

        ActivityLog::record($project->id, auth()->user()->name . ' membuat task "' . $task->title . '"');

        return redirect()->route('projects.show', $project)->with('success', 'Task berhasil dibuat.');
    }

    public function show(Task $task)
    {
        $project = $task->project;
        $this->authorizeMember($project);

        $task->load(['assignee', 'comments.user']);
        $isManager = $project->members()->where('user_id', auth()->id())->wherePivot('role', 'manager')->exists();

        return view('tasks.show', compact('task', 'project', 'isManager'));
    }

    public function edit(Task $task)
    {
        $project = $task->project;
        $this->authorizeMember($project);

        $members = $project->members;
        return view('tasks.edit', compact('task', 'project', 'members'));
    }

    public function update(Request $request, Task $task)
    {
        $project = $task->project;
        $this->authorizeMember($project);

        $data = $request->validate($this->rules($project));
        $task->update($data);

        ActivityLog::record($project->id, auth()->user()->name . ' memperbarui task "' . $task->title . '"');

        return redirect()->route('projects.show', $project)->with('success', 'Task berhasil diperbarui.');
    }

    public function destroy(Task $task)
    {
        $project = $task->project;
        $this->authorizeManager($project);

        $title = $task->title;
        $task->delete();

        ActivityLog::record($project->id, auth()->user()->name . ' menghapus task "' . $title . '"');

        return redirect()->route('projects.show', $project)->with('success', 'Task berhasil dihapus.');
    }

    public function updateStatus(Request $request, Task $task)
    {
        $project = $task->project;
        $this->authorizeMember($project);

        $request->validate([
            'status' => 'required|in:todo,in_progress,review,done',
        ]);

        $old = $task->status;
        $task->update(['status' => $request->status]);

        if ($old !== $task->status) {
            ActivityLog::record(
                $project->id,
                auth()->user()->name . ' memindahkan task "' . $task->title . '" dari '
                . self::STATUS_LABEL[$old] . ' ke ' . self::STATUS_LABEL[$task->status]
            );
        }

        return response()->json([
            'success'  => true,
            'progress' => $project->progress,
        ]);
    }

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