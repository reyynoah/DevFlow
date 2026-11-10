<?php

namespace App\Http\Controllers;

use App\Models\ActivityLog;
use App\Models\Comment;
use App\Models\Task;
use Illuminate\Http\Request;

class CommentController extends Controller
{
    public function store(Request $request, Task $task)
    {
        $project = $task->project;
        abort_unless($project->members()->where('user_id', auth()->id())->exists(), 403);

        $data = $request->validate(['body' => 'required|string|max:1000']);

        $task->comments()->create([
            'user_id' => auth()->id(),
            'body'    => $data['body'],
        ]);

        ActivityLog::record($project->id, auth()->user()->name . ' mengomentari task "' . $task->title . '"');

        return back()->with('success', 'Komentar terkirim.');
    }

    public function destroy(Comment $comment)
    {
        $project = $comment->task->project;

        $isManager = $project->members()->where('user_id', auth()->id())->wherePivot('role', 'manager')->exists();
        abort_unless($comment->user_id === auth()->id() || $isManager, 403);

        $comment->delete();

        return back()->with('success', 'Komentar dihapus.');
    }
}