<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">Dashboard</h2>
    </x-slot>

    <div class="py-8 max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-6">

        {{-- Kartu statistik --}}
        <div class="grid grid-cols-2 lg:grid-cols-4 gap-4">
            <div class="bg-white p-5 rounded-lg shadow">
                <p class="text-xs text-gray-500">Project Aktif</p>
                <p class="text-3xl font-bold text-indigo-600">{{ $stats['projects_active'] }}</p>
            </div>
            <div class="bg-white p-5 rounded-lg shadow">
                <p class="text-xs text-gray-500">Project Selesai</p>
                <p class="text-3xl font-bold text-green-600">{{ $stats['projects_done'] }}</p>
            </div>
            <div class="bg-white p-5 rounded-lg shadow">
                <p class="text-xs text-gray-500">Task Selesai</p>
                <p class="text-3xl font-bold text-green-600">{{ $stats['tasks_done'] }}</p>
            </div>
            <div class="bg-white p-5 rounded-lg shadow">
                <p class="text-xs text-gray-500">Task Belum Selesai</p>
                <p class="text-3xl font-bold text-yellow-600">{{ $stats['tasks_pending'] }}</p>
            </div>
        </div>

        <div class="grid gap-6 lg:grid-cols-3">
            {{-- Chart --}}
            <div class="bg-white p-5 rounded-lg shadow">
                <h3 class="font-semibold mb-3">Task per Status</h3>
                @if ($stats['tasks_total'] > 0)
                    <canvas id="statusChart" height="220"></canvas>
                @else
                    <p class="text-sm text-gray-400">Belum ada task.</p>
                @endif
            </div>

            {{-- Progres project --}}
            <div class="bg-white p-5 rounded-lg shadow lg:col-span-2">
                <h3 class="font-semibold mb-3">Progres Project</h3>
                <div class="space-y-4">
                    @forelse ($projects as $project)
                        <div>
                            <div class="flex justify-between text-sm">
                                <a href="{{ route('projects.show', $project) }}" class="text-indigo-600">{{ $project->name }}</a>
                                <span>{{ $project->progress }}%</span>
                            </div>
                            <div class="w-full bg-gray-200 rounded h-2 mt-1">
                                <div class="bg-indigo-600 h-2 rounded" style="width: {{ $project->progress }}%"></div>
                            </div>
                        </div>
                    @empty
                        <p class="text-sm text-gray-400">Belum ada project.</p>
                    @endforelse
                </div>
            </div>
        </div>

        <div class="grid gap-6 lg:grid-cols-2">
            {{-- Task saya --}}
            <div class="bg-white p-5 rounded-lg shadow">
                <h3 class="font-semibold mb-3">Task Saya</h3>
                <div class="divide-y">
                    @forelse ($myTasks as $task)
                        <div class="py-2 text-sm">
                            <a href="{{ route('projects.show', $task->project_id) }}" class="font-medium">{{ $task->title }}</a>
                            <p class="text-xs text-gray-500">
                                {{ $task->project->name }} · {{ str_replace('_', ' ', $task->status) }}
                                · Deadline: {{ $task->deadline?->format('d M Y') ?? '-' }}
                            </p>
                        </div>
                    @empty
                        <p class="text-sm text-gray-400">Tidak ada task yang ditugaskan ke kamu.</p>
                    @endforelse
                </div>
            </div>

            {{-- Task terlambat --}}
            <div class="bg-white p-5 rounded-lg shadow">
                <h3 class="font-semibold mb-3 text-red-600">Task Terlambat ({{ $overdueTasks->count() }})</h3>
                <div class="divide-y">
                    @forelse ($overdueTasks as $task)
                        <div class="py-2 text-sm">
                            <a href="{{ route('projects.show', $task->project_id) }}" class="font-medium">{{ $task->title }}</a>
                            <p class="text-xs text-gray-500">
                                {{ $task->project->name }} · PIC: {{ $task->assignee->name ?? '-' }}
                            </p>
                            <p class="text-xs text-red-600">Deadline: {{ $task->deadline->format('d M Y') }}</p>
                        </div>
                    @empty
                        <p class="text-sm text-gray-400">Tidak ada task terlambat. 👍</p>
                    @endforelse
                </div>
            </div>
        </div>
    </div>

    @if ($stats['tasks_total'] > 0)
        <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.7/dist/chart.umd.min.js"></script>
        <script>
            new Chart(document.getElementById('statusChart'), {
                type: 'doughnut',
                data: {
                    labels: ['To Do', 'In Progress', 'Review', 'Done'],
                    datasets: [{
                        data: @json($chartData),
                        backgroundColor: ['#9ca3af', '#3b82f6', '#f59e0b', '#22c55e']
                    }]
                },
                options: { plugins: { legend: { position: 'bottom' } } }
            });
        </script>
    @endif
</x-app-layout>