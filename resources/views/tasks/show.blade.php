@php
    $statusLabel = ['todo' => 'To Do', 'in_progress' => 'In Progress', 'review' => 'Review', 'done' => 'Done'];

    // Warna avatar (dipilih dari id user supaya konsisten, sama seperti di papan kanban)
    $avatarPalette = [
        'bg-[#0F3D3A] text-white', 'bg-[#F26D6D] text-white', 'bg-[#4FC3CF] text-[#0F3D3A]',
        'bg-[#FFD23F] text-[#0F3D3A]', 'bg-[#8B7FD9] text-white',
    ];
@endphp

<x-app-layout>
    <x-slot name="header">
        <div class="flex justify-between items-center">
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">{{ $task->title }}</h2>
            <a href="{{ route('projects.show', $project) }}" class="text-sm text-gray-600">&larr; Kembali ke {{ $project->name }}</a>
        </div>
    </x-slot>

    <div class="py-8 max-w-4xl mx-auto sm:px-6 lg:px-8 space-y-6">

        @if (session('success'))
            <div class="p-3 bg-green-100 text-green-800 rounded">{{ session('success') }}</div>
        @endif

        {{-- Detail task --}}
        <div class="bg-white p-5 rounded-lg shadow">
            <div class="flex justify-between items-start">
                <div class="flex gap-2 text-xs">
                    <span class="px-2 py-1 bg-indigo-100 text-indigo-700 rounded uppercase">{{ $statusLabel[$task->status] }}</span>
                    <span class="px-2 py-1 bg-gray-100 rounded uppercase">{{ $task->priority }}</span>
                </div>
                <a href="{{ route('tasks.edit', $task) }}" class="text-sm text-yellow-600">Edit</a>
            </div>

            <p class="mt-3 text-gray-700">{{ $task->description ?: 'Tidak ada deskripsi.' }}</p>

            <div class="mt-3 text-sm text-gray-500 space-y-1">
                <p>PIC: {{ $task->assignee->name ?? '-' }}</p>
                <p class="{{ $task->deadline && $task->deadline->isPast() && $task->status !== 'done' ? 'text-red-600 font-semibold' : '' }}">
                    Deadline: {{ $task->deadline?->format('d M Y') ?? '-' }}
                </p>
            </div>
        </div>

        {{-- Komentar --}}
        <section class="rounded-2xl border border-[#E6EAE4] bg-white p-6">
            <div class="flex items-center gap-2.5">
                <h3 class="text-base font-semibold text-[#0F3D3A]">Komentar</h3>
                <span class="rounded-full bg-[#F3F5F2] px-2 py-0.5 text-xs font-medium text-[#0F3D3A]/70">{{ $task->comments->count() }}</span>
            </div>

            {{-- Form komentar baru --}}
            <form action="{{ route('comments.store', $task) }}" method="POST" class="mt-5 flex gap-3"
                  x-data="{ body: @js(old('body', '')) }">
                @csrf

                <span class="flex h-9 w-9 shrink-0 items-center justify-center rounded-full text-sm font-semibold {{ $avatarPalette[auth()->id() % count($avatarPalette)] }}">
                    {{ mb_strtoupper(mb_substr(auth()->user()->name, 0, 1)) }}
                </span>

                <div class="min-w-0 flex-1">
                    <div class="rounded-xl border border-[#E0E5DE] bg-white transition focus-within:border-[#0F3D3A] focus-within:ring-2 focus-within:ring-[#0F3D3A]/15">
                        <label for="comment-body" class="sr-only">Tulis komentar</label>
                        <textarea id="comment-body" name="body" rows="3" required maxlength="1000"
                                  x-model="body"
                                  @input="$el.style.height = 'auto'; $el.style.height = $el.scrollHeight + 'px'"
                                  @keydown.ctrl.enter.prevent="body.trim() && $root.requestSubmit()"
                                  @keydown.meta.enter.prevent="body.trim() && $root.requestSubmit()"
                                  placeholder="Tulis catatan atau pertanyaan untuk task ini..."
                                  class="block max-h-60 w-full resize-none rounded-xl border-0 bg-transparent px-4 pb-1 pt-3 text-sm leading-relaxed text-[#0F3D3A] placeholder:text-[#0F3D3A]/40 focus:border-0 focus:ring-0"></textarea>

                        <div class="flex items-center gap-3 px-3 pb-2.5 pt-1">
                            <span class="hidden text-xs text-[#0F3D3A]/40 sm:inline">Ctrl + Enter untuk kirim</span>
                            <div class="ml-auto flex items-center gap-3">
                                <span class="text-xs tabular-nums"
                                      :class="body.length > 900 ? 'text-[#B93E3E]' : 'text-[#0F3D3A]/40'"
                                      x-text="body.length + ' / 1000'">0 / 1000</span>
                                <button type="submit" :disabled="body.trim().length === 0"
                                        class="rounded-lg bg-[#0F3D3A] px-4 py-1.5 text-sm font-medium text-white transition hover:bg-[#0B2E2C] focus:outline-none focus-visible:ring-2 focus-visible:ring-[#0F3D3A] focus-visible:ring-offset-2 disabled:cursor-not-allowed disabled:opacity-40">
                                    Kirim komentar
                                </button>
                            </div>
                        </div>
                    </div>

                    @error('body')
                        <p class="mt-1.5 text-sm text-[#B93E3E]">{{ $message }}</p>
                    @enderror
                </div>
            </form>

            {{-- Daftar komentar (terbaru di atas) --}}
            <ul class="mt-6 divide-y divide-[#E6EAE4] border-t border-[#E6EAE4]">
                @forelse ($task->comments as $comment)
                    @php
                        $mine = $comment->user_id === auth()->id();
                        $at   = $comment->created_at->copy()->timezone('Asia/Jakarta')->locale('id');
                    @endphp
                    <li class="flex gap-3 py-4">
                        <span class="flex h-9 w-9 shrink-0 items-center justify-center rounded-full text-sm font-semibold {{ $avatarPalette[$comment->user->id % count($avatarPalette)] }}">
                            {{ mb_strtoupper(mb_substr($comment->user->name, 0, 1)) }}
                        </span>

                        <div class="min-w-0 flex-1">
                            <div class="flex flex-wrap items-center gap-x-2 gap-y-0.5">
                                <span class="text-sm font-semibold text-[#0F3D3A]">{{ $comment->user->name }}</span>
                                @if ($mine)
                                    <span class="rounded bg-[#F3F5F2] px-1.5 py-0.5 text-[11px] font-medium text-[#0F3D3A]/70">Kamu</span>
                                @endif
                                <time datetime="{{ $comment->created_at->toIso8601String() }}"
                                      title="{{ $at->translatedFormat('d M Y, H.i') }} WIB"
                                      class="text-xs text-[#0F3D3A]/50">{{ $at->diffForHumans() }}</time>
                            </div>
                            <p class="mt-1 whitespace-pre-line break-words text-sm leading-relaxed text-[#0F3D3A]/90">{{ $comment->body }}</p>
                        </div>

                        @if ($mine || $isManager)
                            <form action="{{ route('comments.destroy', $comment) }}" method="POST"
                                  onsubmit="return confirm('Hapus komentar ini?')">
                                @csrf @method('DELETE')
                                <button type="submit" aria-label="Hapus komentar" title="Hapus komentar"
                                        class="rounded-md p-1.5 text-[#0F3D3A]/40 transition hover:bg-[#FDECEC] hover:text-[#B93E3E] focus:outline-none focus-visible:ring-2 focus-visible:ring-[#B93E3E]">
                                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75" stroke-linecap="round" stroke-linejoin="round" class="h-4 w-4" aria-hidden="true">
                                        <path d="M4 7h16"/><path d="M10 11v6M14 11v6"/><path d="M6 7l1 12a1 1 0 0 0 1 1h8a1 1 0 0 0 1-1l1-12"/><path d="M9 7V4h6v3"/>
                                    </svg>
                                </button>
                            </form>
                        @endif
                    </li>
                @empty
                    <li class="flex flex-col items-center py-10 text-center">
                        <span class="flex h-11 w-11 items-center justify-center rounded-full bg-[#F3F5F2] text-[#0F3D3A]/60">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75" stroke-linecap="round" stroke-linejoin="round" class="h-5 w-5" aria-hidden="true">
                                <path d="M21 12a8 8 0 0 1-11.6 7.1L4 20l1-4.2A8 8 0 1 1 21 12z"/>
                            </svg>
                        </span>
                        <p class="mt-3 text-sm font-medium text-[#0F3D3A]">Belum ada komentar</p>
                        <p class="mt-0.5 text-sm text-[#0F3D3A]/60">Tulis catatan atau pertanyaan soal task ini di kolom di atas.</p>
                    </li>
                @endforelse
            </ul>
        </section>
    </div>
</x-app-layout>