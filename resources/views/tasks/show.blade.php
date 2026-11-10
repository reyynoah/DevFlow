@php
    $statusLabel = ['todo' => 'To Do', 'in_progress' => 'In Progress', 'review' => 'Review', 'done' => 'Done'];
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
        <div class="bg-white p-5 rounded-lg shadow">
            <h3 class="font-semibold mb-3">Komentar ({{ $task->comments->count() }})</h3>

            <form action="{{ route('comments.store', $task) }}" method="POST" class="mb-4">
                @csrf
                <textarea name="body" rows="2" required placeholder="Tulis komentar..."
                          class="w-full border-gray-300 rounded-md text-sm">{{ old('body') }}</textarea>
                @error('body') <p class="text-red-600 text-sm">{{ $message }}</p> @enderror
                <button class="mt-2 px-4 py-2 bg-indigo-600 text-white rounded-md text-sm">Kirim</button>
            </form>

            <div class="divide-y">
                @forelse ($task->comments as $comment)
                    <div class="py-3">
                        <div class="flex justify-between text-xs text-gray-500">
                            <span>
                                <span class="font-semibold text-gray-700">{{ $comment->user->name }}</span>
                                · {{ $comment->created_at->diffForHumans() }}
                            </span>
                            @if ($comment->user_id === auth()->id() || $isManager)
                                <form action="{{ route('comments.destroy', $comment) }}" method="POST"
                                      onsubmit="return confirm('Hapus komentar ini?')">
                                    @csrf @method('DELETE')
                                    <button class="text-red-600">Hapus</button>
                                </form>
                            @endif
                        </div>
                        <p class="text-sm mt-1 whitespace-pre-line">{{ $comment->body }}</p>
                    </div>
                @empty
                    <p class="text-sm text-gray-400">Belum ada komentar.</p>
                @endforelse
            </div>
        </div>
    </div>
</x-app-layout>