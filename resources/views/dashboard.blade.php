@php
    $statuses = [
        ['label' => 'To Do',       'color' => '#C5CFC3'],
        ['label' => 'In Progress', 'color' => '#7DB4E6'],
        ['label' => 'Review',      'color' => '#FFD23F'],
        ['label' => 'Done',        'color' => '#5E8C55'],
    ];
    $statusLabel = [
        'todo' => 'To Do', 'in_progress' => 'In Progress', 'review' => 'Review', 'done' => 'Done',
    ];
    $statusChip = [
        'todo'        => 'bg-[#ECEFEB] text-[#0F3D3A]/80',
        'in_progress' => 'bg-[#E3F0FB] text-[#2B6CA3]',
        'review'      => 'bg-[#FFF4C7] text-[#8A6A00]',
        'done'        => 'bg-[#E1F0DE] text-[#3C6B34]',
    ];
    $prio = [
        'high'   => 'border-[#F26D6D]',
        'medium' => 'border-[#FFD23F]',
        'low'    => 'border-[#4FC3CF]',
    ];
    $total = $stats['tasks_total'];
    $pct   = $total > 0 ? round($stats['tasks_done'] / $total * 100) : 0;

    // Sapaan sesuai jam (WIB) + ringkasan singkat
    $now        = now('Asia/Jakarta')->locale('id');
    $hour       = $now->hour;
    $sapaan     = $hour < 11 ? 'Selamat pagi' : ($hour < 15 ? 'Selamat siang' : ($hour < 18 ? 'Selamat sore' : 'Selamat malam'));
    $firstName  = \Illuminate\Support\Str::before(auth()->user()->name, ' ');
    $overdueCnt = $overdueTasks->count();
    $myCnt      = $myTasks->count();
    $mySuffix   = $myCnt > 0 ? ', '.$myCnt.($myCnt >= 8 ? '+' : '').' di antaranya ditugaskan ke kamu' : '';
@endphp

<x-app-layout>
    <div class="flex flex-col xl:min-h-screen xl:flex-row">

        {{-- ================= TENGAH ================= --}}
        <section class="min-w-0 flex-1 px-6 py-6 lg:px-10 lg:py-9">

            {{-- Judul --}}
            <p class="text-sm text-[#0F3D3A]/60">{{ $now->translatedFormat('l, j F Y') }}</p>
            <div class="mt-1 flex items-center gap-4">
                <h1 class="text-4xl font-bold tracking-tight">Dashboard</h1>
                <span class="rounded-lg border border-[#E3E8E1] bg-white px-3 py-1 text-xl font-medium shadow-sm"
                      title="Task belum selesai">{{ $stats['tasks_pending'] }}</span>
            </div>

            <div class="mt-4 flex items-start gap-3 rounded-xl bg-[#F4F6F3] px-4 py-3.5">
                <span class="mt-0.5 flex h-8 w-8 shrink-0 items-center justify-center rounded-full
                             {{ $overdueCnt > 0 ? 'bg-red-50 text-red-600' : 'bg-[#E1F0DE] text-[#3C6B34]' }}">
                    @if ($overdueCnt > 0)
                        <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M12 8v4m0 4h.01M10.3 4.3L2.6 18a2 2 0 001.7 3h15.4a2 2 0 001.7-3L13.7 4.3a2 2 0 00-3.4 0z"/></svg>
                    @else
                        <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M12 3v2m0 14v2m9-9h-2M5 12H3m14.95-6.95l-1.4 1.4M7.45 16.55l-1.4 1.4m0-11.9l1.4 1.4m9.1 9.1l1.4 1.4M16 12a4 4 0 11-8 0 4 4 0 018 0z"/></svg>
                    @endif
                </span>
                <div class="text-sm leading-relaxed">
                    <p class="font-semibold">{{ $sapaan }}, {{ $firstName }}!</p>
                    <p class="text-[#0F3D3A]/70">
                        @if ($total === 0)
                            Belum ada task. Buat project dan mulai tambahkan task pertamamu.
                        @elseif ($overdueCnt > 0)
                            Ada <span class="font-semibold text-red-600">{{ $overdueCnt }} task</span> yang melewati deadline, yuk segera dicek.
                        @elseif ($stats['tasks_pending'] === 0)
                            Semua task sudah selesai. Kerja bagus!
                        @else
                            Masih ada {{ $stats['tasks_pending'] }} task belum selesai di {{ $stats['projects_active'] }} project aktif{{ $mySuffix }}.
                        @endif
                    </p>
                </div>
            </div>

            {{-- Kartu statistik --}}
            <div class="mt-7 grid grid-cols-2 gap-3 lg:grid-cols-4">
                @foreach ([
                    ['Project Aktif',        $stats['projects_active'], 'bg-[#7DB4E6]'],
                    ['Project Selesai',      $stats['projects_done'],   'bg-[#5E8C55]'],
                    ['Task Selesai',         $stats['tasks_done'],      'bg-[#4FC3CF]'],
                    ['Task Belum Selesai',   $stats['tasks_pending'],   'bg-[#FFD23F]'],
                ] as [$label, $value, $color])
                    <div class="rounded-xl bg-[#F4F6F3] p-4">
                        <div class="flex items-center gap-2 text-xs text-[#0F3D3A]/70">
                            <span class="h-2.5 w-2.5 rounded-[3px] {{ $color }}"></span>
                            {{ $label }}
                        </div>
                        <p class="mt-2 text-3xl font-bold tracking-tight">{{ $value }}</p>
                    </div>
                @endforeach
            </div>

            {{-- Task saya --}}
            <div class="mt-9">
                <h2 class="text-lg font-semibold">Task Saya</h2>
                <div class="mt-2 border-t border-[#ECEFEA]">
                    @forelse ($myTasks as $task)
                        <a href="{{ route('tasks.show', $task) }}"
                           class="group flex items-start gap-3 border-b border-[#ECEFEA] px-1 py-3.5 transition hover:bg-[#F6F8F5]">
                            <span class="mt-0.5 h-4 w-4 shrink-0 rounded-[5px] border-2 {{ $prio[$task->priority] ?? 'border-[#CBD5CC]' }}"></span>
                            <div class="min-w-0 flex-1">
                                <p class="truncate text-sm font-medium">{{ $task->title }}</p>
                                <div class="mt-1.5 flex flex-wrap items-center gap-2 text-[11px]">
                                    <span class="rounded px-1.5 py-0.5 {{ $statusChip[$task->status] ?? '' }}">{{ $statusLabel[$task->status] ?? $task->status }}</span>
                                    <span class="flex items-center gap-1 rounded bg-[#ECEFEB] px-1.5 py-0.5 text-[#0F3D3A]/80">
                                        <svg class="h-3 w-3" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M8 7V3m8 4V3M4 11h16M5 5h14a1 1 0 011 1v13a1 1 0 01-1 1H5a1 1 0 01-1-1V6a1 1 0 011-1z"/></svg>
                                        {{ $task->deadline?->format('d M Y') ?? '-' }}
                                    </span>
                                    <span class="rounded bg-[#ECEFEB] px-1.5 py-0.5 text-[#0F3D3A]/80">{{ $task->project->name }}</span>
                                </div>
                            </div>
                            <svg class="mt-0.5 h-4 w-4 shrink-0 text-[#0F3D3A]/40 transition group-hover:translate-x-0.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M9 5l7 7-7 7"/></svg>
                        </a>
                    @empty
                        <div class="flex items-center gap-4 border-b border-[#ECEFEA] px-1 py-5">
                            <span class="flex h-10 w-10 shrink-0 items-center justify-center rounded-full bg-[#F1F3F0] text-[#0F3D3A]/50">
                                <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8"><path stroke-linecap="round" stroke-linejoin="round" d="M4 13h4l2 3h4l2-3h4M5 5h14l2 8v5a1 1 0 01-1 1H4a1 1 0 01-1-1v-5l2-8z"/></svg>
                            </span>
                            <div>
                                <p class="text-sm font-semibold">Belum ada task untukmu</p>
                                <p class="mt-0.5 text-sm text-[#0F3D3A]/60">Task yang ditugaskan ke kamu akan muncul di sini.</p>
                            </div>
                        </div>
                    @endforelse
                </div>
            </div>

            {{-- Task terlambat --}}
            <div class="mt-9">
                <div class="flex items-center gap-3">
                    <h2 class="text-lg font-semibold">Task Terlambat</h2>
                    <span class="rounded-full px-2.5 py-0.5 text-xs font-semibold {{ $overdueTasks->count() > 0 ? 'bg-red-50 text-red-600' : 'bg-[#E1F0DE] text-[#3C6B34]' }}">
                        {{ $overdueTasks->count() }}
                    </span>
                </div>

                @if ($overdueTasks->isEmpty())
                    <div class="mt-3 flex items-center gap-4 rounded-xl bg-[#F1F7EF] px-5 py-5">
                        <span class="flex h-11 w-11 shrink-0 items-center justify-center rounded-full bg-[#DDEBD8] text-[#3C6B34]">
                            <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.2"><path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/></svg>
                        </span>
                        <div>
                            <p class="text-sm font-semibold">Semua task masih aman</p>
                            <p class="mt-0.5 text-sm text-[#0F3D3A]/60">Tidak ada task yang melewati deadline. Pertahankan!</p>
                        </div>
                    </div>
                @else
                    <div class="mt-2 border-t border-[#ECEFEA]">
                        @foreach ($overdueTasks as $task)
                            <a href="{{ route('tasks.show', $task) }}"
                               class="group flex items-start gap-3 border-b border-[#ECEFEA] px-1 py-3.5 transition hover:bg-[#FFF8F7]">
                                <span class="mt-0.5 h-4 w-4 shrink-0 rounded-[5px] border-2 {{ $prio[$task->priority] ?? 'border-[#CBD5CC]' }}"></span>
                                <div class="min-w-0 flex-1">
                                    <p class="truncate text-sm font-medium">{{ $task->title }}</p>
                                    <div class="mt-1.5 flex flex-wrap items-center gap-2 text-[11px]">
                                        <span class="flex items-center gap-1 rounded bg-red-50 px-1.5 py-0.5 font-medium text-red-600">
                                            <svg class="h-3 w-3" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M8 7V3m8 4V3M4 11h16M5 5h14a1 1 0 011 1v13a1 1 0 01-1 1H5a1 1 0 01-1-1V6a1 1 0 011-1z"/></svg>
                                            {{ $task->deadline->format('d M Y') }}
                                        </span>
                                        <span class="rounded bg-[#ECEFEB] px-1.5 py-0.5 text-[#0F3D3A]/80">{{ $task->project->name }}</span>
                                        <span class="rounded bg-[#ECEFEB] px-1.5 py-0.5 text-[#0F3D3A]/80">PIC: {{ $task->assignee->name ?? '-' }}</span>
                                    </div>
                                </div>
                                <svg class="mt-0.5 h-4 w-4 shrink-0 text-[#0F3D3A]/40 transition group-hover:translate-x-0.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M9 5l7 7-7 7"/></svg>
                            </a>
                        @endforeach
                    </div>
                @endif
            </div>
        </section>

        {{-- ================= KANAN: chart + progres ================= --}}
        <aside class="bg-[#F4F6F3] p-6 xl:w-[22rem] xl:shrink-0 xl:border-l xl:border-[#E6EAE4] xl:px-7 xl:py-9">

            <h2 class="text-xl font-bold tracking-tight">Task per Status</h2>

            <div class="relative mx-auto mt-6 w-full max-w-[14rem]">
                @if ($total > 0)
                    <canvas id="statusChart"></canvas>
                @else
                    <div class="aspect-square w-full rounded-full border-[16px] border-dashed border-[#D9E0D7]"></div>
                @endif
                <div class="pointer-events-none absolute inset-0 flex flex-col items-center justify-center">
                    <span class="text-4xl font-bold tracking-tight">{{ $pct }}%</span>
                    <span class="mt-0.5 text-xs text-[#0F3D3A]/60">selesai</span>
                </div>
            </div>

            <p class="mt-4 text-center text-xs text-[#0F3D3A]/60">
                {{ $total > 0 ? $stats['tasks_done'].' dari '.$total.' task sudah selesai' : 'Belum ada task di project kamu' }}
            </p>

            <ul class="mt-5 space-y-1">
                @foreach ($statuses as $i => $s)
                    @php $count = $chartData[$i]; $share = $total > 0 ? round($count / $total * 100) : 0; @endphp
                    <li class="flex items-center justify-between rounded-lg px-2 py-1.5 text-sm">
                        <span class="flex items-center gap-2.5">
                            <span class="h-3 w-3 rounded-[4px]" style="background-color: {{ $s['color'] }}"></span>
                            {{ $s['label'] }}
                        </span>
                        <span class="flex items-center gap-2.5">
                            <span class="text-xs text-[#0F3D3A]/50">{{ $share }}%</span>
                            <span class="min-w-[1.75rem] rounded-md bg-white px-1.5 py-0.5 text-center text-xs font-semibold shadow-sm">{{ $count }}</span>
                        </span>
                    </li>
                @endforeach
            </ul>

            <h2 class="mt-9 text-xl font-bold tracking-tight">Progres Project</h2>
            <div class="mt-4 space-y-5">
                @forelse ($projects as $project)
                    <div>
                        <div class="flex items-center justify-between gap-3 text-sm">
                            <a href="{{ route('projects.show', $project) }}" class="truncate font-medium hover:underline">{{ $project->name }}</a>
                            <span class="text-xs font-medium text-[#0F3D3A]/70">{{ $project->progress }}%</span>
                        </div>
                        <div class="mt-1.5 h-2 w-full overflow-hidden rounded-full bg-[#E1E7DF]">
                            <div class="h-2 rounded-full bg-[#46633F]" style="width: {{ $project->progress }}%"></div>
                        </div>
                    </div>
                @empty
                    <p class="text-sm text-[#0F3D3A]/50">Belum ada project.</p>
                @endforelse
            </div>
        </aside>
    </div>

    @if ($stats['tasks_total'] > 0)
        <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.7/dist/chart.umd.min.js"></script>
        <script>
            const statusData = @json($chartData);
            const activeSegments = statusData.filter(v => v > 0).length;

            new Chart(document.getElementById('statusChart'), {
                type: 'doughnut',
                data: {
                    labels: @json(array_column($statuses, 'label')),
                    datasets: [{
                        data: statusData,
                        backgroundColor: @json(array_column($statuses, 'color')),
                        borderWidth: 0,
                        spacing: activeSegments > 1 ? 3 : 0,
                        borderRadius: activeSegments > 1 ? 8 : 0,
                        hoverOffset: 4
                    }]
                },
                options: {
                    cutout: '74%',
                    plugins: { legend: { display: false } }
                }
            });
        </script>
    @endif
</x-app-layout>