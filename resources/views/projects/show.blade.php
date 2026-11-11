{{--
    Halaman detail project (/projects/{id}).
    Data dari ProjectController@show: $project (dengan tasks, members) dan $activities.
    Isi halaman: info & progres, papan kanban (drag & drop), tim, dan aktivitas terbaru.
--}}
@php
    // ---- Konfigurasi tampilan ----

    // Kolom kanban: label + warna titik
    $statuses = [
        'todo'        => ['label' => 'To Do',       'dot' => 'bg-[#B8C2B5]'],
        'in_progress' => ['label' => 'In Progress', 'dot' => 'bg-[#4FC3CF]'],
        'review'      => ['label' => 'Review',      'dot' => 'bg-[#FFD23F]'],
        'done'        => ['label' => 'Done',        'dot' => 'bg-[#2E9E6B]'],
    ];

    // Badge prioritas (project & task)
    $priorityStyle = [
        'high'   => ['label' => 'High',   'badge' => 'bg-[#FDECEC] text-[#B93E3E]', 'dot' => 'bg-[#F26D6D]'],
        'medium' => ['label' => 'Medium', 'badge' => 'bg-[#FFF5D1] text-[#8A6D00]', 'dot' => 'bg-[#FFD23F]'],
        'low'    => ['label' => 'Low',    'badge' => 'bg-[#E0F4F6] text-[#23808A]', 'dot' => 'bg-[#4FC3CF]'],
    ];

    // Warna avatar (dipilih dari id user supaya konsisten)
    $avatarPalette = [
        'bg-[#0F3D3A] text-white', 'bg-[#F26D6D] text-white', 'bg-[#4FC3CF] text-[#0F3D3A]',
        'bg-[#FFD23F] text-[#0F3D3A]', 'bg-[#8B7FD9] text-white',
    ];

    // ---- Data turunan (dihitung sekali, tanpa query tambahan) ----
    $tasks     = $project->tasks->groupBy('status');           // task dikelompokkan per status
    $total     = $project->tasks->count();
    $done      = $project->tasks->where('status', 'done')->count();
    $progress  = $total ? (int) round($done / $total * 100) : 0;
    $isManager = $project->members->firstWhere('id', auth()->id())?->pivot->role === 'manager';
    $pr        = $priorityStyle[$project->priority] ?? $priorityStyle['medium'];

    /**
     * Menentukan teks & warna chip deadline.
     * Mengembalikan [teks, kelas warna, apakah terlambat].
     */
    $deadlineChip = function ($date, bool $isDone) {
        $late    = 'bg-[#FDECEC] text-[#B93E3E]';
        $soon    = 'bg-[#FFF5D1] text-[#8A6D00]';
        $neutral = 'bg-[#F3F5F2] text-[#0F3D3A]/70';

        if (! $date) {
            return ['Tanpa deadline', 'bg-[#F3F5F2] text-[#0F3D3A]/55', false];
        }

        $d    = \Carbon\Carbon::parse($date)->startOfDay();
        $days = (int) today()->diffInDays($d, false);   // negatif = sudah lewat

        if ($isDone)       return [$d->format('d M Y'), $neutral, false];
        if ($days < 0)     return ['Telat ' . abs($days) . ' hari', $late, true];
        if ($days === 0)   return ['Hari ini', $soon, false];
        if ($days <= 7)    return [$days . ' hari lagi', $soon, false];

        return [$d->format('d M Y'), $neutral, false];
    };

    [$pdLabel, $pdClass] = $deadlineChip($project->deadline, $total > 0 && $done === $total);
@endphp

<x-app-layout>
    {{-- Header: link kembali, nama project, badge, dan tombol edit (khusus manager) --}}
    <x-slot name="header">
        <a href="{{ route('projects.index') }}" class="inline-flex items-center gap-1.5 text-sm text-[#0F3D3A]/60 transition hover:text-[#0F3D3A]">
            <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M15 19l-7-7 7-7"/></svg>
            Projects
        </a>
        <div class="mt-2 flex flex-wrap items-center justify-between gap-3">
            <div class="flex min-w-0 flex-wrap items-center gap-3">
                <h1 class="truncate text-2xl font-semibold tracking-tight">{{ $project->name }}</h1>
                <span class="inline-flex items-center gap-1.5 rounded-full px-2.5 py-1 text-[11px] font-semibold {{ $pr['badge'] }}">
                    <span class="h-1.5 w-1.5 rounded-full {{ $pr['dot'] }}"></span>{{ $pr['label'] }}
                </span>
                @if ($project->category)
                    <span class="rounded-md bg-[#F3F5F2] px-2 py-1 text-[11px] font-medium text-[#0F3D3A]/70">{{ $project->category }}</span>
                @endif
            </div>
            @if ($isManager)
                <a href="{{ route('projects.edit', $project) }}"
                   class="inline-flex items-center gap-2 rounded-xl border border-[#E0E5DE] bg-white px-3.5 py-2 text-sm font-medium transition hover:bg-[#F3F5F2]">
                    <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8"><path stroke-linecap="round" stroke-linejoin="round" d="M15.232 5.232l3.536 3.536M4 20h4l10.5-10.5a2.5 2.5 0 00-3.536-3.536L4.5 16.5V20z"/></svg>
                    Edit project
                </a>
            @endif
        </div>
    </x-slot>

    {{--
        State Alpine halaman ini:
          taskModal  : modal "Task Baru" terbuka/tertutup (otomatis terbuka lagi kalau validasi task gagal)
          memberForm : form tambah anggota terbuka/tertutup (otomatis terbuka kalau validasi gagal)
    --}}
    <div class="space-y-6 px-6 pb-14 pt-6 lg:px-10"
         x-data="{
            taskModal: {{ $errors->hasAny(['title', 'deadline', 'assigned_to', 'priority']) ? 'true' : 'false' }},
            memberForm: {{ $errors->hasAny(['email', 'role']) ? 'true' : 'false' }}
         }"
         @keydown.escape.window="taskModal = false">

        {{-- Banner pesan sukses --}}
        @if (session('success'))
            <div x-data="{ show: true }" x-show="show" x-transition
                 class="flex items-center justify-between gap-3 rounded-xl border border-[#BFE3D0] bg-[#EAF7F0] px-4 py-3 text-sm text-[#1F6B4A]">
                <span class="flex items-center gap-2">
                    <svg class="h-4 w-4 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.2"><path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/></svg>
                    {{ session('success') }}
                </span>
                <button type="button" @click="show = false" class="rounded p-0.5 hover:bg-white/60" aria-label="Tutup">
                    <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/></svg>
                </button>
            </div>
        @endif

        {{-- ===== Info project & progres ===== --}}
        <section class="grid gap-6 rounded-2xl border border-[#E6EAE4] bg-white p-6 lg:grid-cols-3">
            <div class="lg:col-span-2">
                <h2 class="text-xs font-semibold uppercase tracking-wider text-[#0F3D3A]/45">Deskripsi</h2>
                <p class="mt-2 text-[15px] leading-6 text-[#0F3D3A]/80">{{ $project->description ?: 'Belum ada deskripsi.' }}</p>

                <div class="mt-4 flex flex-wrap items-center gap-2 text-xs">
                    <span class="inline-flex items-center gap-1.5 rounded-full px-2.5 py-1 font-medium {{ $pdClass }}">
                        <svg class="h-3 w-3" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M8 7V3m8 4V3M4 11h16M5 5h14a1 1 0 011 1v13a1 1 0 01-1 1H5a1 1 0 01-1-1V6a1 1 0 011-1z"/></svg>
                        Deadline: {{ $pdLabel }}
                    </span>
                    @if ($project->start_date)
                        <span class="rounded-full bg-[#F3F5F2] px-2.5 py-1 font-medium text-[#0F3D3A]/70">
                            Mulai {{ \Carbon\Carbon::parse($project->start_date)->format('d M Y') }}
                        </span>
                    @endif
                    <span class="rounded-full bg-[#F3F5F2] px-2.5 py-1 font-medium text-[#0F3D3A]/70">
                        {{ $project->members->count() }} anggota
                    </span>
                </div>
            </div>

            {{-- Panel progres (id dipakai JavaScript untuk update setelah drag & drop) --}}
            <div class="rounded-xl bg-[#F6F8F5] p-5">
                <div class="flex items-end justify-between">
                    <span class="text-xs font-semibold uppercase tracking-wider text-[#0F3D3A]/45">Progres</span>
                    <span class="text-3xl font-semibold tracking-tight"><span id="progress-text">{{ $progress }}</span>%</span>
                </div>
                <div class="mt-3 h-2.5 w-full overflow-hidden rounded-full bg-[#E2E8DF]">
                    <div id="progress-bar" class="h-full rounded-full bg-[#0F3D3A] transition-all duration-500" style="width: {{ $progress }}%"></div>
                </div>
                <p class="mt-2.5 text-xs text-[#0F3D3A]/60">
                    <span id="done-count">{{ $done }}</span> dari <span id="total-count">{{ $total }}</span> task selesai
                </p>
            </div>
        </section>

        {{-- ===== Papan kanban ===== --}}
        <section>
            <div class="mb-3 flex flex-wrap items-center justify-between gap-3">
                <div>
                    <h2 class="text-lg font-semibold tracking-tight">Papan Task</h2>
                    <p class="text-xs text-[#0F3D3A]/55">Tarik kartu ke kolom lain untuk mengubah status.</p>
                </div>
                <button type="button" @click="taskModal = true"
                        class="inline-flex items-center gap-2 rounded-xl bg-[#0F3D3A] px-4 py-2.5 text-sm font-medium text-white shadow-sm transition hover:bg-[#0B2E2C]">
                    <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.2"><path stroke-linecap="round" stroke-linejoin="round" d="M12 5v14M5 12h14"/></svg>
                    Task Baru
                </button>
            </div>

            <div class="grid gap-4 md:grid-cols-2 xl:grid-cols-4">
                @foreach ($statuses as $key => $st)
                    <div class="flex flex-col rounded-2xl bg-[#F3F5F2] p-3">
                        {{-- Judul kolom + jumlah task (angka di-update JavaScript) --}}
                        <div class="mb-3 flex items-center justify-between px-1">
                            <h3 class="flex items-center gap-2 text-sm font-semibold">
                                <span class="h-2.5 w-2.5 rounded-full {{ $st['dot'] }}"></span>{{ $st['label'] }}
                            </h3>
                            <span class="column-count rounded-full bg-white px-2 py-0.5 text-xs font-medium text-[#0F3D3A]/70" data-status="{{ $key }}">
                                {{ ($tasks[$key] ?? collect())->count() }}
                            </span>
                        </div>

                        <div class="relative min-h-[140px] flex-1">
                            {{-- Placeholder kolom kosong (disembunyikan JavaScript saat ada kartu) --}}
                            <div class="kanban-empty pointer-events-none absolute inset-0 flex items-center justify-center rounded-xl border-2 border-dashed border-[#D9E0D6] text-xs text-[#0F3D3A]/40
                                        {{ ($tasks[$key] ?? collect())->count() ? 'hidden' : '' }}" data-status="{{ $key }}">
                                Tarik task ke sini
                            </div>

                            {{-- Daftar kartu (area yang bisa di-drag & drop) --}}
                            <div class="kanban-list relative min-h-[140px] space-y-3" data-status="{{ $key }}">
                                @foreach ($tasks[$key] ?? [] as $task)
                                    @php
                                        $tp = $priorityStyle[$task->priority] ?? $priorityStyle['medium'];
                                        [$tdLabel, $tdClass, $tdLate] = $deadlineChip($task->deadline, $task->status === 'done');
                                        $dateLabel = $task->deadline ? $task->deadline->format('d M Y') : '';
                                    @endphp

                                    {{-- data-overdue: dipakai JavaScript untuk mengubah chip deadline saat task dipindah ke Done --}}
                                    <div class="group cursor-grab rounded-xl border border-[#E6EAE4] bg-white p-3.5 shadow-sm transition hover:border-[#C9D3C6] hover:shadow-md active:cursor-grabbing"
                                         data-id="{{ $task->id }}" data-overdue="{{ $tdLate ? 1 : 0 }}">

                                        <div class="flex items-center justify-between gap-2">
                                            <span class="inline-flex items-center gap-1.5 rounded-full px-2 py-0.5 text-[10px] font-semibold {{ $tp['badge'] }}">
                                                <span class="h-1.5 w-1.5 rounded-full {{ $tp['dot'] }}"></span>{{ $tp['label'] }}
                                            </span>
                                            @if ($task->comments_count > 0)
                                                <span class="inline-flex items-center gap-1 text-[11px] text-[#0F3D3A]/50">
                                                    <svg class="h-3.5 w-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8"><path stroke-linecap="round" stroke-linejoin="round" d="M8 10h8M8 14h5m-9 6l2.5-3H19a2 2 0 002-2V6a2 2 0 00-2-2H5a2 2 0 00-2 2v14z"/></svg>
                                                    {{ $task->comments_count }}
                                                </span>
                                            @endif
                                        </div>

                                        <a href="{{ route('tasks.show', $task) }}" class="mt-2 block text-sm font-semibold leading-snug transition hover:text-[#23808A]">
                                            {{ $task->title }}
                                        </a>

                                        @if ($task->description)
                                            <p class="mt-1 line-clamp-2 text-xs leading-5 text-[#0F3D3A]/60">{{ $task->description }}</p>
                                        @endif

                                        <div class="mt-3 flex items-center justify-between gap-2">
                                            @if ($task->deadline)
                                                <span class="deadline-chip inline-flex items-center gap-1 rounded-full px-2 py-0.5 text-[10px] font-medium {{ $tdClass }}"
                                                      data-late="{{ $tdLate ? $tdLabel : '' }}" data-date="{{ $dateLabel }}">
                                                    <svg class="h-3 w-3" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M8 7V3m8 4V3M4 11h16M5 5h14a1 1 0 011 1v13a1 1 0 01-1 1H5a1 1 0 01-1-1V6a1 1 0 011-1z"/></svg>
                                                    <span class="deadline-label">{{ $tdLabel }}</span>
                                                </span>
                                            @else
                                                <span></span>
                                            @endif

                                            {{-- Penanggung jawab (PIC) --}}
                                            @if ($task->assignee)
                                                <span class="flex items-center gap-1.5 text-[11px] text-[#0F3D3A]/65" title="PIC: {{ $task->assignee->name }}">
                                                    <span class="flex h-6 w-6 items-center justify-center rounded-full text-[10px] font-semibold {{ $avatarPalette[$task->assignee->id % count($avatarPalette)] }}">
                                                        {{ mb_strtoupper(mb_substr($task->assignee->name, 0, 1)) }}
                                                    </span>
                                                    <span class="max-w-[5rem] truncate">{{ $task->assignee->name }}</span>
                                                </span>
                                            @else
                                                <span class="text-[11px] text-[#0F3D3A]/40">Belum ada PIC</span>
                                            @endif
                                        </div>

                                        {{-- Aksi kartu: detail, edit, hapus (hapus khusus manager) --}}
                                        <div class="mt-3 flex items-center justify-end gap-0.5 border-t border-[#EEF1EC] pt-2.5">
                                            <a href="{{ route('tasks.show', $task) }}" title="Detail & komentar"
                                               class="rounded-lg p-1.5 text-[#0F3D3A]/45 transition hover:bg-[#F3F5F2] hover:text-[#0F3D3A]">
                                                <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8"><path stroke-linecap="round" stroke-linejoin="round" d="M2.5 12S6 5.5 12 5.5 21.5 12 21.5 12 18 18.5 12 18.5 2.5 12 2.5 12z"/><circle cx="12" cy="12" r="2.8"/></svg>
                                            </a>
                                            <a href="{{ route('tasks.edit', $task) }}" title="Edit"
                                               class="rounded-lg p-1.5 text-[#0F3D3A]/45 transition hover:bg-[#F3F5F2] hover:text-[#0F3D3A]">
                                                <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8"><path stroke-linecap="round" stroke-linejoin="round" d="M15.232 5.232l3.536 3.536M4 20h4l10.5-10.5a2.5 2.5 0 00-3.536-3.536L4.5 16.5V20z"/></svg>
                                            </a>
                                            @if ($isManager)
                                                <form action="{{ route('tasks.destroy', $task) }}" method="POST"
                                                      onsubmit="return confirm('Hapus task ini?')">
                                                    @csrf @method('DELETE')
                                                    <button title="Hapus"
                                                            class="rounded-lg p-1.5 text-[#0F3D3A]/45 transition hover:bg-[#FDECEC] hover:text-[#B93E3E]">
                                                        <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8"><path stroke-linecap="round" stroke-linejoin="round" d="M6 7h12m-9 0V5a1 1 0 011-1h4a1 1 0 011 1v2m2 0v12a1 1 0 01-1 1H8a1 1 0 01-1-1V7h10zM10 11v5m4-5v5"/></svg>
                                                    </button>
                                                </form>
                                            @endif
                                        </div>
                                    </div>
                                @endforeach
                            </div>
                        </div>
                    </div>
                @endforeach
            </div>
        </section>

        {{-- ===== Tim & aktivitas ===== --}}
        <div class="grid gap-6 lg:grid-cols-5">

            {{-- Anggota tim --}}
            <section class="rounded-2xl border border-[#E6EAE4] bg-white p-6 lg:col-span-3">
                <div class="flex items-center justify-between gap-3">
                    <h2 class="text-lg font-semibold tracking-tight">
                        Anggota Tim <span class="text-sm font-normal text-[#0F3D3A]/50">({{ $project->members->count() }})</span>
                    </h2>
                    @if ($isManager)
                        <button type="button" @click="memberForm = !memberForm"
                                class="inline-flex items-center gap-1.5 rounded-lg bg-[#F3F5F2] px-3 py-1.5 text-xs font-medium transition hover:bg-[#E9EDE7]">
                            <svg class="h-3.5 w-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.2"><path stroke-linecap="round" stroke-linejoin="round" d="M12 5v14M5 12h14"/></svg>
                            Tambah anggota
                        </button>
                    @endif
                </div>

                {{-- Form tambah anggota (khusus manager) --}}
                @if ($isManager)
                    <form x-show="memberForm" x-cloak x-transition action="{{ route('members.store', $project) }}" method="POST"
                          class="mt-4 flex flex-wrap gap-2 rounded-xl bg-[#F6F8F5] p-3">
                        @csrf
                        <input type="email" name="email" value="{{ old('email') }}" required
                               placeholder="Email anggota (harus sudah register)"
                               class="min-w-[220px] flex-1 rounded-lg border-[#E0E5DE] text-sm focus:border-[#0F3D3A] focus:ring-[#0F3D3A]/20">
                        <select name="role" class="rounded-lg border-[#E0E5DE] text-sm focus:border-[#0F3D3A] focus:ring-[#0F3D3A]/20">
                            <option value="member">Member</option>
                            <option value="manager">Manager</option>
                        </select>
                        <button class="rounded-lg bg-[#0F3D3A] px-4 py-2 text-sm font-medium text-white transition hover:bg-[#0B2E2C]">Tambah</button>
                        @error('email') <p class="w-full text-sm text-[#B93E3E]">{{ $message }}</p> @enderror
                        @error('role') <p class="w-full text-sm text-[#B93E3E]">{{ $message }}</p> @enderror
                    </form>
                @endif

                <div class="mt-4 divide-y divide-[#EEF1EC]">
                    @foreach ($project->members as $member)
                        @php
                            $memberTasks = $project->tasks->where('assigned_to', $member->id);   // task milik anggota ini
                            $isCreator   = $member->id === $project->created_by;
                        @endphp

                        {{-- open: daftar task anggota bisa dibuka/tutup --}}
                        <div class="py-3.5" x-data="{ open: false }">
                            <div class="flex flex-wrap items-center justify-between gap-3">
                                <div class="flex min-w-0 items-center gap-3">
                                    <span class="flex h-9 w-9 shrink-0 items-center justify-center rounded-full text-sm font-semibold {{ $avatarPalette[$member->id % count($avatarPalette)] }}">
                                        {{ mb_strtoupper(mb_substr($member->name, 0, 1)) }}
                                    </span>
                                    <div class="min-w-0">
                                        <p class="truncate text-sm font-semibold">
                                            {{ $member->name }}
                                            @if ($member->id === auth()->id()) <span class="text-xs font-normal text-[#0F3D3A]/45">(kamu)</span> @endif
                                        </p>
                                        <p class="truncate text-xs text-[#0F3D3A]/55">{{ $member->email }}</p>
                                    </div>
                                </div>

                                <div class="flex items-center gap-2 text-xs">
                                    <button type="button" @click="open = !open"
                                            class="rounded-lg px-2.5 py-1.5 font-medium text-[#23808A] transition hover:bg-[#E0F4F6]">
                                        {{ $memberTasks->count() }} task &middot; {{ $memberTasks->where('status', 'done')->count() }} selesai
                                    </button>

                                    {{-- Manager bisa ubah role / keluarkan anggota (kecuali pembuat project) --}}
                                    @if ($isManager && ! $isCreator)
                                        <form action="{{ route('members.update', [$project, $member]) }}" method="POST">
                                            @csrf @method('PATCH')
                                            <select name="role" onchange="this.form.submit()"
                                                    class="rounded-lg border-[#E0E5DE] py-1.5 text-xs focus:border-[#0F3D3A] focus:ring-[#0F3D3A]/20">
                                                <option value="member" @selected($member->pivot->role === 'member')>Member</option>
                                                <option value="manager" @selected($member->pivot->role === 'manager')>Manager</option>
                                            </select>
                                        </form>
                                        <form action="{{ route('members.destroy', [$project, $member]) }}" method="POST"
                                              onsubmit="return confirm('Keluarkan anggota ini?')">
                                            @csrf @method('DELETE')
                                            <button title="Keluarkan"
                                                    class="rounded-lg p-1.5 text-[#0F3D3A]/45 transition hover:bg-[#FDECEC] hover:text-[#B93E3E]">
                                                <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/></svg>
                                            </button>
                                        </form>
                                    @else
                                        <span class="rounded-md bg-[#F3F5F2] px-2 py-1 text-[10px] font-semibold uppercase tracking-wide text-[#0F3D3A]/65">{{ $member->pivot->role }}</span>
                                    @endif
                                </div>
                            </div>

                            {{-- Daftar task anggota --}}
                            <ul x-show="open" x-cloak x-transition class="ml-12 mt-2 space-y-1 text-xs text-[#0F3D3A]/65">
                                @forelse ($memberTasks as $t)
                                    <li class="flex items-center gap-2">
                                        <span class="h-1.5 w-1.5 rounded-full {{ $statuses[$t->status]['dot'] }}"></span>
                                        <a href="{{ route('tasks.show', $t) }}" class="hover:underline">{{ $t->title }}</a>
                                        <span class="text-[#0F3D3A]/40">{{ $statuses[$t->status]['label'] }}</span>
                                    </li>
                                @empty
                                    <li class="text-[#0F3D3A]/40">Belum ada task.</li>
                                @endforelse
                            </ul>
                        </div>
                    @endforeach
                </div>
            </section>

            {{-- Aktivitas terbaru --}}
            <div class="lg:col-span-2">
                @include('projects._activity')
            </div>
        </div>

        {{-- ===== Modal: Task Baru ===== --}}
        <div x-show="taskModal" x-cloak class="fixed inset-0 z-50 flex items-center justify-center p-4" role="dialog" aria-modal="true">
            <div x-show="taskModal" x-transition.opacity @click="taskModal = false" class="absolute inset-0 bg-[#0F3D3A]/40"></div>

            <div x-show="taskModal" x-transition
                 class="relative max-h-[90vh] w-full max-w-lg overflow-y-auto rounded-2xl bg-white p-6 shadow-2xl">
                <div class="flex items-start justify-between">
                    <div>
                        <h3 class="text-lg font-semibold tracking-tight">Task Baru</h3>
                        <p class="text-xs text-[#0F3D3A]/55">Tambahkan task ke project {{ $project->name }}.</p>
                    </div>
                    <button type="button" @click="taskModal = false" class="rounded-lg p-1.5 text-[#0F3D3A]/50 hover:bg-[#F3F5F2]" aria-label="Tutup">
                        <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/></svg>
                    </button>
                </div>

                <form action="{{ route('tasks.store', $project) }}" method="POST" class="mt-5 space-y-4">
                    @csrf
                    <div>
                        <label class="block text-sm font-medium">Judul Task</label>
                        <input type="text" name="title" value="{{ old('title') }}" required
                               class="mt-1.5 w-full rounded-lg border-[#E0E5DE] text-sm focus:border-[#0F3D3A] focus:ring-[#0F3D3A]/20">
                        @error('title') <p class="mt-1 text-sm text-[#B93E3E]">{{ $message }}</p> @enderror
                    </div>
                    <div>
                        <label class="block text-sm font-medium">Deskripsi</label>
                        <textarea name="description" rows="3"
                                  class="mt-1.5 w-full rounded-lg border-[#E0E5DE] text-sm focus:border-[#0F3D3A] focus:ring-[#0F3D3A]/20">{{ old('description') }}</textarea>
                    </div>
                    <div class="grid grid-cols-1 gap-4 sm:grid-cols-3">
                        <div>
                            <label class="block text-sm font-medium">Prioritas</label>
                            <select name="priority" class="mt-1.5 w-full rounded-lg border-[#E0E5DE] text-sm focus:border-[#0F3D3A] focus:ring-[#0F3D3A]/20">
                                <option value="low">Low</option>
                                <option value="medium" selected>Medium</option>
                                <option value="high">High</option>
                            </select>
                        </div>
                        <div>
                            <label class="block text-sm font-medium">Deadline</label>
                            <input type="date" name="deadline" value="{{ old('deadline') }}"
                                   class="mt-1.5 w-full rounded-lg border-[#E0E5DE] text-sm focus:border-[#0F3D3A] focus:ring-[#0F3D3A]/20">
                            @error('deadline') <p class="mt-1 text-sm text-[#B93E3E]">{{ $message }}</p> @enderror
                        </div>
                        <div>
                            <label class="block text-sm font-medium">Ditugaskan ke</label>
                            <select name="assigned_to" class="mt-1.5 w-full rounded-lg border-[#E0E5DE] text-sm focus:border-[#0F3D3A] focus:ring-[#0F3D3A]/20">
                                <option value="">- Belum ditugaskan -</option>
                                @foreach ($project->members as $member)
                                    <option value="{{ $member->id }}" @selected(old('assigned_to') == $member->id)>{{ $member->name }}</option>
                                @endforeach
                            </select>
                            @error('assigned_to') <p class="mt-1 text-sm text-[#B93E3E]">{{ $message }}</p> @enderror
                        </div>
                    </div>
                    <div class="flex justify-end gap-2 pt-2">
                        <button type="button" @click="taskModal = false"
                                class="rounded-lg border border-[#E0E5DE] px-4 py-2 text-sm font-medium transition hover:bg-[#F3F5F2]">Batal</button>
                        <button class="rounded-lg bg-[#0F3D3A] px-4 py-2 text-sm font-medium text-white transition hover:bg-[#0B2E2C]">Simpan Task</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    {{-- SortableJS: library drag & drop untuk papan kanban --}}
    <script src="https://cdn.jsdelivr.net/npm/sortablejs@1.15.6/Sortable.min.js"></script>
    <script>
        const statusUrl = "{{ url('tasks') }}";   // dasar URL: /tasks/{id}/status
        const csrf = "{{ csrf_token() }}";

        // Update angka di judul kolom, placeholder kolom kosong, dan jumlah task selesai
        function updateCounts() {
            let total = 0;
            document.querySelectorAll('.kanban-list').forEach(list => {
                const status = list.dataset.status;
                const n = list.querySelectorAll('[data-id]').length;
                total += n;

                document.querySelector('.column-count[data-status="' + status + '"]').textContent = n;
                document.querySelector('.kanban-empty[data-status="' + status + '"]').classList.toggle('hidden', n > 0);

                if (status === 'done') document.getElementById('done-count').textContent = n;
            });
            document.getElementById('total-count').textContent = total;
        }

        // Chip deadline: merah "Telat X hari" selama belum Done, jadi tanggal biasa setelah Done
        function refreshOverdue(card, status) {
            const chip = card.querySelector('.deadline-chip');
            if (!chip || card.dataset.overdue !== '1') return;

            const late = status !== 'done';
            chip.querySelector('.deadline-label').textContent = late ? chip.dataset.late : chip.dataset.date;
            chip.classList.toggle('bg-[#FDECEC]', late);
            chip.classList.toggle('text-[#B93E3E]', late);
            chip.classList.toggle('bg-[#F3F5F2]', !late);
            chip.classList.toggle('text-[#0F3D3A]/70', !late);
        }

        // Aktifkan drag & drop di tiap kolom
        document.querySelectorAll('.kanban-list').forEach(list => {
            new Sortable(list, {
                group: 'kanban',                       // kartu bisa pindah antar kolom
                animation: 150,
                draggable: '[data-id]',                // hanya kartu task yang bisa ditarik
                filter: 'a, button, form, select',     // klik link/tombol di kartu tetap berfungsi
                preventOnFilter: false,
                delay: 120,                            // di layar sentuh: tahan sebentar supaya scroll tidak terganggu
                delayOnTouchOnly: true,
                ghostClass: 'opacity-40',
                onEnd: function (evt) {
                    const taskId = evt.item.dataset.id;
                    const newStatus = evt.to.dataset.status;

                    updateCounts();
                    if (evt.from === evt.to) return;   // tidak pindah kolom = tidak perlu simpan

                    refreshOverdue(evt.item, newStatus);

                    // Simpan status baru ke server
                    fetch(statusUrl + '/' + taskId + '/status', {
                        method: 'PATCH',
                        headers: {
                            'Content-Type': 'application/json',
                            'Accept': 'application/json',
                            'X-CSRF-TOKEN': csrf
                        },
                        body: JSON.stringify({ status: newStatus })
                    })
                    .then(res => {
                        if (!res.ok) throw new Error('Gagal menyimpan');
                        return res.json();
                    })
                    .then(data => {
                        // Perbarui progress bar dengan angka dari server
                        document.getElementById('progress-bar').style.width = data.progress + '%';
                        document.getElementById('progress-text').textContent = data.progress;
                    })
                    .catch(() => {
                        alert('Gagal memindahkan task, halaman akan dimuat ulang.');
                        location.reload();
                    });
                }
            });
        });
    </script>
</x-app-layout>