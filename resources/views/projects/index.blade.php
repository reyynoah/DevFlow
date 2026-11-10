<x-app-layout>
    <x-slot name="header">
        <div class="flex justify-between items-center">
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">Projects</h2>
            <a href="{{ route('projects.create') }}"
               class="px-4 py-2 bg-indigo-600 text-white rounded-md text-sm hover:bg-indigo-700">
                + Project Baru
            </a>
        </div>
    </x-slot>

    <div class="py-8 max-w-7xl mx-auto sm:px-6 lg:px-8">
        @if (session('success'))
            <div class="mb-4 p-3 bg-green-100 text-green-800 rounded">{{ session('success') }}</div>
        @endif

        <div class="grid gap-4 md:grid-cols-2 lg:grid-cols-3">
            @forelse ($projects as $project)
                <div class="bg-white p-5 rounded-lg shadow">
                    <div class="flex justify-between items-start">
                        <h3 class="font-semibold text-lg">{{ $project->name }}</h3>
                        <span class="text-xs px-2 py-1 rounded bg-gray-100 uppercase">{{ $project->priority }}</span>
                    </div>
                    <p class="text-sm text-gray-600 mt-1">{{ Str::limit($project->description, 80) }}</p>
                    <p class="text-xs text-gray-500 mt-2">
                        Deadline: {{ $project->deadline ?? '-' }} · {{ $project->tasks_count }} task
                    </p>

                    <div class="mt-3">
                        <div class="w-full bg-gray-200 rounded h-2">
                            <div class="bg-indigo-600 h-2 rounded" style="width: {{ $project->progress }}%"></div>
                        </div>
                        <p class="text-xs mt-1">{{ $project->progress }}% selesai</p>
                    </div>

                    <div class="mt-4 flex gap-3 text-sm">
                        <a href="{{ route('projects.show', $project) }}" class="text-indigo-600">Buka</a>
                        @if ($project->pivot->role === 'manager')
                            <a href="{{ route('projects.edit', $project) }}" class="text-yellow-600">Edit</a>
                            <form action="{{ route('projects.destroy', $project) }}" method="POST"
                                  onsubmit="return confirm('Hapus project ini?')">
                                @csrf @method('DELETE')
                                <button class="text-red-600">Hapus</button>
                            </form>
                        @endif
                    </div>
                </div>
            @empty
                <p class="text-gray-500">Belum ada project. Buat yang pertama!</p>
            @endforelse
        </div>
    </div>
</x-app-layout>