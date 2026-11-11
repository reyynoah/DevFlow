{{--
    Halaman daftar project (/projects).
    Data datang dari ProjectController@index: $projects, lengkap dengan
    tasks_count, done_tasks_count, members_count, dan daftar members.
    Pencarian & filter dikerjakan di browser oleh Alpine.js (tanpa reload).
--}}
@php
    // ---- Siapkan data tiap project (tanpa query tambahan) ----

    // Gaya badge prioritas: label, warna badge, dan warna titik
    $priorityStyle = [
        'high'   => ['label' => 'High',   'badge' => 'bg-[#FDECEC] text-[#B93E3E]', 'dot' => 'bg-[#F26D6D]'],
        'medium' => ['label' => 'Medium', 'badge' => 'bg-[#FFF5D1] text-[#8A6D00]', 'dot' => 'bg-[#FFD23F]'],
        'low'    => ['label' => 'Low',    'badge' => 'bg-[#E0F4F6] text-[#23808A]', 'dot' => 'bg-[#4FC3CF]'],
    ];

    // Warna avatar anggota tim (dipilih berdasarkan id user supaya warnanya konsisten)
    $avatarPalette = [
        'bg-[#0F3D3A] text-white', 'bg-[#F26D6D] text-white', 'bg-[#4FC3CF] text-[#0F3D3A]',
        'bg-[#FFD23F] text-[#0F3D3A]', 'bg-[#8B7FD9] text-white',
    ];

    // Hitung progres, deadline, dan status (done/late/active) tiap project
    $cards = $projects->map(function ($p) {
        // Angka task diambil dari withCount() di controller, bukan query baru
        $total    = $p->tasks_count;
        $done     = $p->done_tasks_count;

        // Deadline diubah ke Carbon; $daysLeft negatif artinya sudah lewat
        $deadline = $p->deadline ? \Carbon\Carbon::parse($p->deadline)->startOfDay() : null;
        $daysLeft = $deadline ? (int) today()->diffInDays($deadline, false) : null;

        // Selesai = ada task dan semuanya done. Terlambat = belum selesai tapi deadline lewat
        $isDone   = $total > 0 && $done === $total;
        $isLate   = ! $isDone && $daysLeft !== null && $daysLeft < 0;

        return (object) [
            'model'    => $p,
            'total'    => $total,
            'done'     => $done,
            'progress' => $total ? (int) round($done / $total * 100) : 0,
            'deadline' => $deadline,
            'daysLeft' => $daysLeft,
            'status'   => $isDone ? 'done' : ($isLate ? 'late' : 'active'),
        ];
    });

    // Jumlah project per status, dipakai kartu ringkasan & chip filter
    $count = [
        'all'    => $cards->count(),
        'active' => $cards->where('status', '!=', 'done')->count(),
        'done'   => $cards->where('status', 'done')->count(),
        'late'   => $cards->where('status', 'late')->count(),
    ];

    // Data ringkas untuk Alpine (urutannya harus sama dengan urutan kartu):
    //  n = teks pencarian, s = status, a = apakah masih berjalan (belum selesai)
    $items = $cards->map(fn ($c) => [
        'n' => mb_strtolower($c->model->name . ' ' . $c->model->category),
        's' => $c->status === 'late' ? 'late' : ($c->status === 'done' ? 'done' : 'active'),
        'a' => $c->status !== 'done',
    ])->values();
@endphp

<x-app-layout>
    {{-- Header halaman: judul, subjudul, dan tombol tambah project --}}
    <x-slot name="header">
        <div class="flex flex-wrap items-end justify-between gap-4">
            <div>
                <h1 class="text-2xl font-semibold tracking-tight">Projects</h1>
                <p class="mt-1 text-sm text-[#0F3D3A]/60">
                    {{ $count['all'] }} project &middot; {{ $count['active'] }} sedang berjalan
                </p>
            </div>
            <a href="{{ route('projects.create') }}"
               class="inline-flex items-center gap-2 rounded-xl bg-[#0F3D3A] px-4 py-2.5 text-sm font-medium text-white shadow-sm transition hover:bg-[#0B2E2C] focus:outline-none focus:ring-2 focus:ring-[#0F3D3A]/30 focus:ring-offset-2">
                <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.2"><path stroke-linecap="round" stroke-linejoin="round" d="M12 5v14M5 12h14"/></svg>
                Project Baru
            </a>
        </div>
    </x-slot>

    {{--
        State Alpine untuk pencarian & filter:
          q     : kata kunci pencarian
          f     : filter aktif (all / active / done / late)
          items : data ringkas tiap project (dari $items)
          ok()  : true kalau project cocok dengan filter + pencarian
          shown : jumlah project yang sedang tampil (untuk pesan "tidak ada hasil")
    --}}
    <div class="px-6 pb-14 pt-6 lg:px-10"
         x-data="{
            q: '',
            f: 'all',
            items: @js($items),
            ok(i) {
                const byStatus = this.f === 'all' || (this.f === 'active' ? i.a : this.f === i.s);
                return byStatus && i.n.includes(this.q.trim().toLowerCase());
            },
            get shown() { return this.items.filter(i => this.ok(i)).length; }
         }">

        {{-- Banner pesan sukses (setelah buat/edit/hapus), bisa ditutup --}}
        @if (session('success'))
            <div x-data="{ show: true }" x-show="show" x-transition
                 class="mb-5 flex items-center justify-between gap-3 rounded-xl border border-[#BFE3D0] bg-[#EAF7F0] px-4 py-3 text-sm text-[#1F6B4A]">
                <span class="flex items-center gap-2">
                    <svg class="h-4 w-4 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.2"><path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/></svg>
                    {{ session('success') }}
                </span>
                <button type="button" @click="show = false" class="rounded p-0.5 hover:bg-white/60" aria-label="Tutup">
                    <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/></svg>
                </button>
            </div>
        @endif

        {{-- Kalau sudah ada project: tampilkan ringkasan, filter, dan kartu --}}
        @if ($cards->isNotEmpty())
            {{-- ===== Ringkasan: 4 kartu angka di atas daftar ===== --}}
            <div class="mb-6 grid grid-cols-2 gap-3 lg:grid-cols-4">
                @foreach ([
                    ['Total project', $count['all'],    'bg-[#0F3D3A]'],
                    ['Berjalan',      $count['active'], 'bg-[#4FC3CF]'],
                    ['Selesai',       $count['done'],   'bg-[#2E9E6B]'],
                    ['Terlambat',     $count['late'],   'bg-[#F26D6D]'],
                ] as [$label, $value, $color])
                    <div class="rounded-2xl border border-[#E6EAE4] bg-white px-4 py-3.5">
                        <div class="flex items-center gap-2 text-xs font-medium text-[#0F3D3A]/60">
                            <span class="h-2 w-2 rounded-full {{ $color }}"></span>{{ $label }}
                        </div>
                        <p class="mt-1.5 text-2xl font-semibold tracking-tight">{{ $value }}</p>
                    </div>
                @endforeach
            </div>

            {{-- ===== Cari & filter ===== --}}
            <div class="mb-5 flex flex-wrap items-center justify-between gap-3">
                <div class="relative w-full sm:w-72">
                    <svg class="pointer-events-none absolute left-3 top-1/2 h-4 w-4 -translate-y-1/2 text-[#0F3D3A]/40" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M21 21l-4.35-4.35M17 10.5a6.5 6.5 0 11-13 0 6.5 6.5 0 0113 0z"/></svg>
                    <input type="search" x-model="q" placeholder="Cari project..."
                           class="w-full rounded-xl border-[#E0E5DE] bg-white py-2.5 pl-9 pr-3 text-sm placeholder:text-[#0F3D3A]/40 focus:border-[#0F3D3A] focus:ring-[#0F3D3A]/20">
                </div>

                <div class="flex flex-wrap gap-1.5">
                    @foreach ([['all', 'Semua'], ['active', 'Berjalan'], ['done', 'Selesai'], ['late', 'Terlambat']] as [$key, $label])
                        <button type="button" @click="f = '{{ $key }}'"
                                :class="f === '{{ $key }}' ? 'bg-[#0F3D3A] text-white border-[#0F3D3A]' : 'bg-white text-[#0F3D3A]/70 border-[#E0E5DE] hover:bg-[#F3F5F2]'"
                                class="rounded-full border px-3.5 py-1.5 text-xs font-medium transition">
                            {{ $label }}
                            <span class="ml-1 opacity-60">{{ $count[$key] }}</span>
                        </button>
                    @endforeach
                </div>
            </div>

            {{-- ===== Kartu project (satu kartu per project) ===== --}}
            <div class="grid gap-5 sm:grid-cols-2 xl:grid-cols-3">
                @foreach ($cards as $c)
                    @php
                        // Ambil gaya prioritas & warna progress bar sesuai status
                        $project = $c->model;
                        $pr      = $priorityStyle[$project->priority] ?? $priorityStyle['medium'];
                        $barColor = match ($c->status) {
                            'done' => 'bg-[#2E9E6B]',
                            'late' => 'bg-[#F26D6D]',
                            default => 'bg-[#0F3D3A]',
                        };

                        // Tentukan teks & warna chip deadline:
                        // selesai / tanpa deadline / telat / hari ini / <= 7 hari / tanggal biasa
                        if ($c->status === 'done') {
                            $dl = ['Selesai', 'bg-[#EAF7F0] text-[#1F6B4A]'];
                        } elseif ($c->daysLeft === null) {
                            $dl = ['Tanpa deadline', 'bg-[#F3F5F2] text-[#0F3D3A]/60'];
                        } elseif ($c->daysLeft < 0) {
                            $dl = ['Telat ' . abs($c->daysLeft) . ' hari', 'bg-[#FDECEC] text-[#B93E3E]'];
                        } elseif ($c->daysLeft === 0) {
                            $dl = ['Deadline hari ini', 'bg-[#FFF5D1] text-[#8A6D00]'];
                        } elseif ($c->daysLeft <= 7) {
                            $dl = [$c->daysLeft . ' hari lagi', 'bg-[#FFF5D1] text-[#8A6D00]'];
                        } else {
                            $dl = [$c->deadline->format('d M Y'), 'bg-[#F3F5F2] text-[#0F3D3A]/70'];
                        }
                    @endphp

                    {{-- x-show: kartu disembunyikan kalau tidak cocok dengan pencarian/filter --}}
                    <article x-show="ok(items[{{ $loop->index }}])" x-transition.opacity
                             class="group relative flex flex-col rounded-2xl border border-[#E6EAE4] bg-white p-5 transition duration-200 hover:-translate-y-0.5 hover:border-[#C9D3C6] hover:shadow-[0_12px_28px_-14px_rgba(15,61,58,0.28)]">

                        {{-- Atas: kategori + prioritas --}}
                        <div class="flex items-center justify-between gap-2">
                            @if ($project->category)
                                <span class="truncate rounded-md bg-[#F3F5F2] px-2 py-1 text-[11px] font-medium text-[#0F3D3A]/70">{{ $project->category }}</span>
                            @else
                                <span></span>
                            @endif
                            <span class="inline-flex shrink-0 items-center gap-1.5 rounded-full px-2.5 py-1 text-[11px] font-semibold {{ $pr['badge'] }}">
                                <span class="h-1.5 w-1.5 rounded-full {{ $pr['dot'] }}"></span>{{ $pr['label'] }}
                            </span>
                        </div>

                        {{-- Judul + deskripsi (seluruh kartu bisa diklik) --}}
                        <h3 class="mt-3 text-lg font-semibold leading-snug tracking-tight">
                            <a href="{{ route('projects.show', $project) }}" class="after:absolute after:inset-0 after:content-[''] focus:outline-none">
                                {{ $project->name }}
                            </a>
                        </h3>
                        <p class="mt-1.5 line-clamp-2 min-h-[2.5rem] text-sm leading-5 text-[#0F3D3A]/65">
                            {{ $project->description ?: 'Belum ada deskripsi.' }}
                        </p>

                        {{-- Progres --}}
                        <div class="mt-5">
                            <div class="mb-1.5 flex items-center justify-between text-xs">
                                <span class="font-medium text-[#0F3D3A]/70">{{ $c->done }}/{{ $c->total }} task</span>
                                <span class="font-semibold">{{ $c->progress }}%</span>
                            </div>
                            <div class="h-2 w-full overflow-hidden rounded-full bg-[#E9EDE7]">
                                <div class="h-full rounded-full transition-all duration-500 {{ $barColor }}" style="width: {{ $c->progress }}%"></div>
                            </div>
                        </div>

                        {{-- Footer: avatar tim, chip deadline, dan tombol aksi (khusus manager) --}}
                        <div class="mt-5 flex items-center justify-between gap-3 border-t border-[#EEF1EC] pt-4">
                            <div class="flex items-center">
                                <div class="flex -space-x-2">
                                    @foreach ($project->members->take(3) as $m)
                                        <span title="{{ $m->name }}"
                                              class="flex h-7 w-7 items-center justify-center rounded-full text-[10px] font-semibold ring-2 ring-white {{ $avatarPalette[$m->id % count($avatarPalette)] }}">
                                            {{ mb_strtoupper(mb_substr($m->name, 0, 1)) }}
                                        </span>
                                    @endforeach
                                    @if ($project->members_count > 3)
                                        <span class="flex h-7 w-7 items-center justify-center rounded-full bg-[#F3F5F2] text-[10px] font-semibold text-[#0F3D3A]/70 ring-2 ring-white">
                                            +{{ $project->members_count - 3 }}
                                        </span>
                                    @endif
                                </div>
                            </div>

                            <span class="inline-flex items-center gap-1.5 rounded-full px-2.5 py-1 text-[11px] font-medium {{ $dl[1] }}">
                                <svg class="h-3 w-3" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M8 7V3m8 4V3M4 11h16M5 5h14a1 1 0 011 1v13a1 1 0 01-1 1H5a1 1 0 01-1-1V6a1 1 0 011-1z"/></svg>
                                {{ $dl[0] }}
                            </span>

                            {{-- Edit & hapus hanya untuk manager; z-10 supaya tetap bisa diklik di atas link kartu --}}
                            @if ($project->pivot->role === 'manager')
                                <div class="relative z-10 flex items-center gap-0.5">
                                    <a href="{{ route('projects.edit', $project) }}" title="Edit"
                                       class="rounded-lg p-1.5 text-[#0F3D3A]/50 transition hover:bg-[#F3F5F2] hover:text-[#0F3D3A]">
                                        <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8"><path stroke-linecap="round" stroke-linejoin="round" d="M15.232 5.232l3.536 3.536M4 20h4l10.5-10.5a2.5 2.5 0 00-3.536-3.536L4.5 16.5V20z"/></svg>
                                    </a>
                                    <form action="{{ route('projects.destroy', $project) }}" method="POST"
                                          onsubmit="return confirm('Hapus project ini? Semua task di dalamnya ikut terhapus.')">
                                        @csrf @method('DELETE')
                                        <button title="Hapus"
                                                class="rounded-lg p-1.5 text-[#0F3D3A]/50 transition hover:bg-[#FDECEC] hover:text-[#B93E3E]">
                                            <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8"><path stroke-linecap="round" stroke-linejoin="round" d="M6 7h12m-9 0V5a1 1 0 011-1h4a1 1 0 011 1v2m2 0v12a1 1 0 01-1 1H8a1 1 0 01-1-1V7h10zM10 11v5m4-5v5"/></svg>
                                        </button>
                                    </form>
                                </div>
                            @endif
                        </div>
                    </article>
                @endforeach
            </div>

            {{-- Hasil pencarian kosong --}}
            <div x-show="shown === 0" x-cloak class="rounded-2xl border border-dashed border-[#D5DCD2] bg-white px-6 py-14 text-center">
                <p class="text-base font-semibold">Tidak ada project yang cocok</p>
                <p class="mt-1 text-sm text-[#0F3D3A]/60">Coba kata kunci lain atau ganti filter.</p>
                <button type="button" @click="q = ''; f = 'all'"
                        class="mt-4 rounded-lg bg-[#F3F5F2] px-3.5 py-2 text-sm font-medium transition hover:bg-[#E9EDE7]">
                    Reset filter
                </button>
            </div>
        @else
            {{-- ===== Kosong ===== --}}
            <div class="mx-auto mt-10 max-w-md rounded-2xl border border-dashed border-[#D5DCD2] bg-white px-8 py-14 text-center">
                <div class="mx-auto flex h-14 w-14 items-center justify-center rounded-2xl bg-[#F3F5F2] text-[#0F3D3A]">
                    <svg class="h-7 w-7" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.6"><path stroke-linecap="round" stroke-linejoin="round" d="M3 7a2 2 0 012-2h4l2 2h8a2 2 0 012 2v8a2 2 0 01-2 2H5a2 2 0 01-2-2V7z"/></svg>
                </div>
                <h2 class="mt-5 text-lg font-semibold">Belum ada project</h2>
                <p class="mt-1.5 text-sm text-[#0F3D3A]/60">Buat project pertama, lalu undang tim dan mulai bagi tugas di papan kanban.</p>
                <a href="{{ route('projects.create') }}"
                   class="mt-6 inline-flex items-center gap-2 rounded-xl bg-[#0F3D3A] px-4 py-2.5 text-sm font-medium text-white transition hover:bg-[#0B2E2C]">
                    + Buat project
                </a>
            </div>
        @endif
    </div>
</x-app-layout>