<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>DevFlow</title>

    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=inter:400,500,600,700" rel="stylesheet" />

    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
@php
    // Data untuk sidebar: project milik user (dibuat / diikuti)
    $navAll = auth()->user()->projects()->withCount('tasks')->latest()->get();
    $navProjects = $navAll->take(8);
    $currentProjectId = request()->routeIs('projects.show') ? request()->route('project')?->id : null;

    // Warna penanda prioritas project
    $dot = [
        'high'   => 'bg-[#F26D6D]',
        'medium' => 'bg-[#FFD23F]',
        'low'    => 'bg-[#4FC3CF]',
    ];

    $navItem = fn ($active) => 'flex items-center gap-3 rounded-lg px-3 py-2 text-sm transition '
        . ($active ? 'bg-[#E3E8E1] font-semibold' : 'text-[#0F3D3A]/80 hover:bg-[#E9EDE7]');
@endphp
<body class="min-h-screen bg-[#FCFCFB] text-[#0F3D3A] antialiased"
      style="font-family: 'Inter', ui-sans-serif, system-ui, sans-serif;">

<div x-data="{ sidebar: false }" class="min-h-screen">
    <div class="flex min-h-screen w-full">

        {{-- Overlay (HP) --}}
        <div x-show="sidebar" x-cloak @click="sidebar = false" class="fixed inset-0 z-30 bg-black/30 lg:hidden"></div>

        {{-- ===== SIDEBAR ===== --}}
        <aside :class="sidebar ? 'translate-x-0' : '-translate-x-[120%]'"
               class="fixed inset-y-0 left-0 z-40 flex w-[17rem] flex-col bg-[#F3F5F2] p-4 shadow-xl transition-transform duration-200
                      lg:sticky lg:top-0 lg:h-screen lg:w-64 lg:shrink-0 lg:self-start lg:translate-x-0 lg:border-r lg:border-[#E6EAE4] lg:shadow-none">

            <div class="flex items-center justify-between px-1 pb-5 pt-1">
                <a href="{{ route('dashboard') }}" class="flex items-center gap-2.5">
                    <svg class="h-7 w-7 text-[#0F3D3A]" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-linecap="round" aria-hidden="true">
                        @for ($i = 0; $i < 28; $i++)
                            <line x1="12" y1="{{ $i % 2 ? 4.5 : 2 }}" x2="12" y2="{{ $i % 2 ? 8.5 : 9 }}"
                                  stroke-width="1" transform="rotate({{ $i * (360 / 28) }} 12 12)" />
                        @endfor
                    </svg>
                    <span class="text-[17px] font-semibold tracking-tight">DevFlow</span>
                </a>
                <button type="button" @click="sidebar = false" class="rounded-md p-1 text-[#0F3D3A]/60 hover:bg-[#E3E8E1] lg:hidden" aria-label="Tutup menu">
                    <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/></svg>
                </button>
            </div>

            {{-- Menu utama --}}
            <p class="px-3 pb-1.5 text-[10px] font-semibold tracking-wider text-[#0F3D3A]/50">MENU</p>
            <nav class="space-y-0.5">
                <a href="{{ route('dashboard') }}" class="{{ $navItem(request()->routeIs('dashboard')) }}">
                    <svg class="h-4 w-4 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8"><path stroke-linecap="round" stroke-linejoin="round" d="M4 5a1 1 0 011-1h5a1 1 0 011 1v5a1 1 0 01-1 1H5a1 1 0 01-1-1V5zm9 0a1 1 0 011-1h5a1 1 0 011 1v3a1 1 0 01-1 1h-5a1 1 0 01-1-1V5zM4 15a1 1 0 011-1h5a1 1 0 011 1v4a1 1 0 01-1 1H5a1 1 0 01-1-1v-4zm9-3a1 1 0 011-1h5a1 1 0 011 1v7a1 1 0 01-1 1h-5a1 1 0 01-1-1v-7z"/></svg>
                    <span class="flex-1">Dashboard</span>
                </a>
                <a href="{{ route('projects.index') }}" class="{{ $navItem(request()->routeIs('projects.index') || request()->routeIs('projects.create') || request()->routeIs('projects.edit')) }}">
                    <svg class="h-4 w-4 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8"><path stroke-linecap="round" stroke-linejoin="round" d="M3 7a2 2 0 012-2h4l2 2h8a2 2 0 012 2v8a2 2 0 01-2 2H5a2 2 0 01-2-2V7z"/></svg>
                    <span class="flex-1">Projects</span>
                    <span class="rounded bg-white px-1.5 text-[11px] font-medium text-[#0F3D3A]/70">{{ $navAll->count() }}</span>
                </a>
            </nav>

            {{-- Daftar project --}}
            <p class="mt-6 px-3 pb-1.5 text-[10px] font-semibold tracking-wider text-[#0F3D3A]/50">PROJECTS</p>
            <nav class="min-h-0 flex-1 space-y-0.5 overflow-y-auto">
                @forelse ($navProjects as $p)
                    <a href="{{ route('projects.show', $p) }}" class="{{ $navItem($currentProjectId === $p->id) }} justify-between">
                        <span class="flex min-w-0 items-center gap-3">
                            <span class="h-3 w-3 shrink-0 rounded-[3px] {{ $dot[$p->priority] ?? 'bg-gray-300' }}"></span>
                            <span class="truncate">{{ $p->name }}</span>
                        </span>
                        <span class="rounded bg-white px-1.5 text-[11px] font-medium text-[#0F3D3A]/70">{{ $p->tasks_count }}</span>
                    </a>
                @empty
                    <p class="px-3 text-xs text-[#0F3D3A]/50">Belum ada project.</p>
                @endforelse

                <a href="{{ route('projects.create') }}" class="{{ $navItem(request()->routeIs('projects.create')) }}">
                    <svg class="h-4 w-4 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M12 5v14M5 12h14"/></svg>
                    <span>Project baru</span>
                </a>
            </nav>

            {{-- Bawah: profil + keluar --}}
            <div class="mt-4 space-y-0.5 border-t border-[#E0E5DE] pt-3">
                <div class="px-3 pb-2">
                    <p class="truncate text-sm font-semibold">{{ auth()->user()->name }}</p>
                    <p class="truncate text-xs text-[#0F3D3A]/60">{{ auth()->user()->email }}</p>
                </div>
                <a href="{{ route('profile.edit') }}" class="{{ $navItem(request()->routeIs('profile.*')) }}">
                    <svg class="h-4 w-4 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8"><path stroke-linecap="round" stroke-linejoin="round" d="M5 7h9m4 0h1M5 17h1m4 0h9M14 4v6m-4 4v6"/></svg>
                    <span>Profile</span>
                </a>
                <form method="POST" action="{{ route('logout') }}">
                    @csrf
                    <button type="submit" class="{{ $navItem(false) }} w-full">
                        <svg class="h-4 w-4 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8"><path stroke-linecap="round" stroke-linejoin="round" d="M15 12H4m0 0l3-3m-3 3l3 3M10 5V4a1 1 0 011-1h7a1 1 0 011 1v16a1 1 0 01-1 1h-7a1 1 0 01-1-1v-1"/></svg>
                        <span>Sign out</span>
                    </button>
                </form>
            </div>
        </aside>

        {{-- ===== KONTEN ===== --}}
        <div class="flex min-w-0 flex-1 flex-col">

            {{-- Topbar (HP) --}}
            <div class="flex items-center gap-3 px-5 pt-5 lg:hidden">
                <button type="button" @click="sidebar = true" class="rounded-lg bg-[#F3F5F2] p-2 text-[#0F3D3A]" aria-label="Buka menu">
                    <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M4 6h16M4 12h16M4 18h16"/></svg>
                </button>
                <span class="text-base font-semibold">DevFlow</span>
            </div>

            @isset($header)
                <header class="px-6 pt-6 lg:px-10 lg:pt-9">
                    {{ $header }}
                </header>
            @endisset

            <main class="min-w-0 flex-1">
                {{ $slot }}
            </main>
        </div>
    </div>
</div>

</body>
</html>