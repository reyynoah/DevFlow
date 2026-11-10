<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">Edit Task</h2>
    </x-slot>

    <div class="py-8 max-w-2xl mx-auto sm:px-6 lg:px-8">
        <form action="{{ route('tasks.update', $task) }}" method="POST" class="bg-white p-6 rounded-lg shadow space-y-4">
            @csrf @method('PUT')

            <div>
                <label class="block text-sm font-medium">Judul Task</label>
                <input type="text" name="title" value="{{ old('title', $task->title) }}"
                       class="mt-1 w-full border-gray-300 rounded-md" required>
                @error('title') <p class="text-red-600 text-sm">{{ $message }}</p> @enderror
            </div>

            <div>
                <label class="block text-sm font-medium">Deskripsi</label>
                <textarea name="description" rows="3"
                          class="mt-1 w-full border-gray-300 rounded-md">{{ old('description', $task->description) }}</textarea>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                <div>
                    <label class="block text-sm font-medium">Prioritas</label>
                    <select name="priority" class="mt-1 w-full border-gray-300 rounded-md">
                        @foreach (['low' => 'Low', 'medium' => 'Medium', 'high' => 'High'] as $val => $label)
                            <option value="{{ $val }}" @selected(old('priority', $task->priority) === $val)>{{ $label }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label class="block text-sm font-medium">Deadline</label>
                    <input type="date" name="deadline"
                           value="{{ old('deadline', $task->deadline?->format('Y-m-d')) }}"
                           class="mt-1 w-full border-gray-300 rounded-md">
                </div>
                <div>
                    <label class="block text-sm font-medium">Ditugaskan ke</label>
                    <select name="assigned_to" class="mt-1 w-full border-gray-300 rounded-md">
                        <option value="">- Belum ditugaskan -</option>
                        @foreach ($members as $member)
                            <option value="{{ $member->id }}" @selected(old('assigned_to', $task->assigned_to) == $member->id)>
                                {{ $member->name }}
                            </option>
                        @endforeach
                    </select>
                </div>
            </div>

            <div class="flex gap-3">
                <button class="px-4 py-2 bg-indigo-600 text-white rounded-md">Update</button>
                <a href="{{ route('projects.show', $project) }}" class="px-4 py-2 text-gray-600">Batal</a>
            </div>
        </form>
    </div>
</x-app-layout>