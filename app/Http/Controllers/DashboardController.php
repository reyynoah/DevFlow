<?php

namespace App\Http\Controllers;

use App\Models\Task;

class DashboardController extends Controller
{
    public function index()
    {
        $user = auth()->user();
        $projects = $user->projects()->get();
        $projectIds = $projects->pluck('id');

        // Semua task di project yang diikuti user
        $allTasks = Task::whereIn('project_id', $projectIds);

        $statusCounts = (clone $allTasks)
            ->selectRaw('status, COUNT(*) as total')
            ->groupBy('status')
            ->pluck('total', 'status');

        $stats = [
            'projects_total'  => $projects->count(),
            'projects_done'   => $projects->filter(fn($p) => $p->progress == 100 && $p->tasks()->count() > 0)->count(),
            'tasks_total'     => (clone $allTasks)->count(),
            'tasks_done'      => $statusCounts['done'] ?? 0,
        ];
        $stats['projects_active'] = $stats['projects_total'] - $stats['projects_done'];
        $stats['tasks_pending']   = $stats['tasks_total'] - $stats['tasks_done'];

        $chartData = [
            $statusCounts['todo'] ?? 0,
            $statusCounts['in_progress'] ?? 0,
            $statusCounts['review'] ?? 0,
            $statusCounts['done'] ?? 0,
        ];

        // Task terlambat: lewat deadline dan belum done
        $overdueTasks = Task::with(['project', 'assignee'])
            ->whereIn('project_id', $projectIds)
            ->whereNotNull('deadline')
            ->whereDate('deadline', '<', today())
            ->where('status', '!=', 'done')
            ->orderBy('deadline')
            ->get();

        // Task milik user yang belum selesai
        $myTasks = Task::with('project')
            ->where('assigned_to', $user->id)
            ->where('status', '!=', 'done')
            ->orderByRaw('deadline IS NULL, deadline ASC')
            ->limit(8)
            ->get();

        return view('dashboard', compact('stats', 'chartData', 'overdueTasks', 'myTasks', 'projects'));
    }
}