@php
    $statuses = [
        'todo'        => 'To Do',
        'in_progress' => 'In Progress',
        'review'      => 'Review',
        'done'        => 'Done',
    ];
    $tasks = $project->tasks->groupBy('status');
    $isManager = $project->members->firstWhere('id', auth()->id())?->pivot->role === 'manager';
    $priorityColor = [
        'low'    => 'bg-green-100 text-green-700',
        'medium' => 'bg-yellow-100 text-yellow-700',
        'high'   => 'bg-red-100 text-red-700',
    ];
@endphp

<x-app-layout>
    <x-slot name="header">
        <div class="flex justify-between items-center">
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">{{ $project->name }}</h2>
            <a href="{{ route('projects.index') }}" class="text-sm text-gray-600">&larr; Kembali</a>
        </div>
    </x-slot>

    <div class="py-8 max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-6">

        @if (session('success'))
            <div class="p-3 bg-green-100 text-green-800 rounded">{{ session('success') }}</div>
        @endif

        {{-- Info + progres --}}
        <div class="bg-white p-5 rounded-lg shadow">
            <p class="text-gray-700">{{ $project->description ?: 'Tidak ada deskripsi.' }}</p>
            <p class="text-xs text-gray-500 mt-2">Deadline: {{ $project->deadline ?? '-' }}</p>
            <div class="mt-3">
                <div class="w-full bg-gray-200 rounded h-3">
                    <div id="progress-bar" class="bg-indigo-600 h-3 rounded transition-all"
                         style="width: {{ $project->progress }}%"></div>
                </div>
                <p class="text-sm mt-1"><span id="progress-text">{{ $project->progress }}</span>% selesai</p>
            </div>
        </div>

        {{-- Tim --}}
        <div class="bg-white p-5 rounded-lg shadow">
            <h3 class="font-semibold mb-3">Anggota Tim ({{ $project->members->count() }})</h3>

            @if ($isManager)
                <form action="{{ route('members.store', $project) }}" method="POST" class="flex flex-wrap gap-2 mb-1">
                    @csrf
                    <input type="email" name="email" value="{{ old('email') }}" required
                           placeholder="Email anggota (harus sudah register)"
                           class="flex-1 min-w-[220px] border-gray-300 rounded-md text-sm">
                    <select name="role" class="border-gray-300 rounded-md text-sm">
                        <option value="member">Member</option>
                        <option value="manager">Manager</option>
                    </select>
                    <button class="px-4 py-2 bg-indigo-600 text-white rounded-md text-sm">Tambah</button>
                </form>
                @error('email') <p class="text-red-600 text-sm mb-2">{{ $message }}</p> @enderror
                @error('role') <p class="text-red-600 text-sm mb-2">{{ $message }}</p> @enderror
            @endif

            <div class="divide-y mt-3">
                @foreach ($project->members as $member)
                    @php
                        $memberTasks = $project->tasks->where('assigned_to', $member->id);
                        $isCreator = $member->id === $project->created_by;
                    @endphp
                    <div class="py-3" x-data="{ open: false }">
                        <div class="flex flex-wrap items-center justify-between gap-2">
                            <div>
                                <p class="font-medium text-sm">
                                    {{ $member->name }}
                                    @if ($member->id === auth()->id()) <span class="text-xs text-gray-400">(kamu)</span> @endif
                                </p>
                                <p class="text-xs text-gray-500">{{ $member->email }}</p>
                            </div>

                            <div class="flex items-center gap-3 text-xs">
                                <button type="button" @click="open = !open" class="text-indigo-600">
                                    {{ $memberTasks->count() }} task ({{ $memberTasks->where('status', 'done')->count() }} selesai)
                                </button>

                                @if ($isManager && !$isCreator)
                                    <form action="{{ route('members.update', [$project, $member]) }}" method="POST">
                                        @csrf @method('PATCH')
                                        <select name="role" onchange="this.form.submit()" class="border-gray-300 rounded-md text-xs py-1">
                                            <option value="member" @selected($member->pivot->role === 'member')>Member</option>
                                            <option value="manager" @selected($member->pivot->role === 'manager')>Manager</option>
                                        </select>
                                    </form>
                                    <form action="{{ route('members.destroy', [$project, $member]) }}" method="POST"
                                          onsubmit="return confirm('Keluarkan anggota ini?')">
                                        @csrf @method('DELETE')
                                        <button class="text-red-600">Keluarkan</button>
                                    </form>
                                @else
                                    <span class="px-2 py-1 bg-gray-100 rounded uppercase text-[10px]">{{ $member->pivot->role }}</span>
                                @endif
                            </div>
                        </div>

                        <ul x-show="open" x-cloak class="mt-2 ml-2 text-xs text-gray-600 list-disc list-inside">
                            @forelse ($memberTasks as $t)
                                <li>{{ $t->title }} <span class="text-gray-400">({{ str_replace('_', ' ', $t->status) }})</span></li>
                            @empty
                                <li class="list-none text-gray-400">Belum ada task.</li>
                            @endforelse
                        </ul>
                    </div>
                @endforeach
            </div>
        </div>

        {{-- Form tambah task --}}
        <div class="bg-white p-5 rounded-lg shadow" x-data="{ open: {{ $errors->has('title') ? 'true' : 'false' }} }">
            <button @click="open = !open" class="px-4 py-2 bg-indigo-600 text-white rounded-md text-sm">
                + Task Baru
            </button>

            <form x-show="open" x-cloak action="{{ route('tasks.store', $project) }}" method="POST" class="mt-4 space-y-4">
                @csrf
                <div>
                    <label class="block text-sm font-medium">Judul Task</label>
                    <input type="text" name="title" value="{{ old('title') }}"
                           class="mt-1 w-full border-gray-300 rounded-md" required>
                    @error('title') <p class="text-red-600 text-sm">{{ $message }}</p> @enderror
                </div>
                <div>
                    <label class="block text-sm font-medium">Deskripsi</label>
                    <textarea name="description" rows="2"
                              class="mt-1 w-full border-gray-300 rounded-md">{{ old('description') }}</textarea>
                </div>
                <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                    <div>
                        <label class="block text-sm font-medium">Prioritas</label>
                        <select name="priority" class="mt-1 w-full border-gray-300 rounded-md">
                            <option value="low">Low</option>
                            <option value="medium" selected>Medium</option>
                            <option value="high">High</option>
                        </select>
                    </div>
                    <div>
                        <label class="block text-sm font-medium">Deadline</label>
                        <input type="date" name="deadline" value="{{ old('deadline') }}"
                               class="mt-1 w-full border-gray-300 rounded-md">
                    </div>
                    <div>
                        <label class="block text-sm font-medium">Ditugaskan ke</label>
                        <select name="assigned_to" class="mt-1 w-full border-gray-300 rounded-md">
                            <option value="">- Belum ditugaskan -</option>
                            @foreach ($project->members as $member)
                                <option value="{{ $member->id }}">{{ $member->name }}</option>
                            @endforeach
                        </select>
                    </div>
                </div>
                <button class="px-4 py-2 bg-indigo-600 text-white rounded-md text-sm">Simpan Task</button>
            </form>
        </div>

        {{-- Kanban board --}}
        <div class="grid gap-4 md:grid-cols-2 lg:grid-cols-4">
            @foreach ($statuses as $key => $label)
                <div class="bg-gray-100 rounded-lg p-3">
                    <h3 class="font-semibold text-sm mb-3 flex justify-between">
                        <span>{{ $label }}</span>
                        <span class="column-count text-gray-500" data-status="{{ $key }}">
                            {{ ($tasks[$key] ?? collect())->count() }}
                        </span>
                    </h3>

                    <div class="kanban-list min-h-[120px] space-y-3" data-status="{{ $key }}">
                        @foreach ($tasks[$key] ?? [] as $task)
                            <div class="bg-white p-3 rounded shadow cursor-move" data-id="{{ $task->id }}">
                                <div class="flex justify-between items-start gap-2">
                                    <a href="{{ route('tasks.show', $task) }}" class="font-medium text-sm hover:text-indigo-600">
                                        {{ $task->title }}
                                        @if ($task->comments_count > 0)
                                            <span class="text-xs text-gray-400">💬 {{ $task->comments_count }}</span>
                                        @endif
                                    </a>
                                    <span class="text-[10px] px-2 py-0.5 rounded uppercase {{ $priorityColor[$task->priority] }}">
                                        {{ $task->priority }}
                                    </span>
                                </div>

                                @if ($task->description)
                                    <p class="text-xs text-gray-500 mt-1">{{ Str::limit($task->description, 60) }}</p>
                                @endif

                                <div class="text-xs text-gray-500 mt-2 space-y-1">
                                    @if ($task->deadline)
                                        <p class="{{ $task->deadline->isPast() && $task->status !== 'done' ? 'text-red-600 font-semibold' : '' }}">
                                            Deadline: {{ $task->deadline->format('d M Y') }}
                                        </p>
                                    @endif
                                    <p>PIC: {{ $task->assignee->name ?? '-' }}</p>
                                </div>

                                <div class="mt-2 flex gap-3 text-xs">
                                    <a href="{{ route('tasks.show', $task) }}" class="text-indigo-600">Komentar</a>
                                    <a href="{{ route('tasks.edit', $task) }}" class="text-yellow-600">Edit</a>
                                    @if ($isManager)
                                        <form action="{{ route('tasks.destroy', $task) }}" method="POST"
                                              onsubmit="return confirm('Hapus task ini?')">
                                            @csrf @method('DELETE')
                                            <button class="text-red-600">Hapus</button>
                                        </form>
                                    @endif
                                </div>
                            </div>
                        @endforeach
                    </div>
                </div>
            @endforeach
        </div>
    </div>

    @include('projects._activity')

    {{-- SortableJS --}}
    <script src="https://cdn.jsdelivr.net/npm/sortablejs@1.15.6/Sortable.min.js"></script>
    <script>
        const statusUrl = "{{ url('tasks') }}";
        const csrf = "{{ csrf_token() }}";

        function updateCounts() {
            document.querySelectorAll('.kanban-list').forEach(list => {
                const counter = document.querySelector('.column-count[data-status="' + list.dataset.status + '"]');
                counter.textContent = list.querySelectorAll('[data-id]').length;
            });
        }

        document.querySelectorAll('.kanban-list').forEach(list => {
            new Sortable(list, {
                group: 'kanban',
                animation: 150,
                ghostClass: 'opacity-40',
                onEnd: function (evt) {
                    const taskId = evt.item.dataset.id;
                    const newStatus = evt.to.dataset.status;

                    if (evt.from === evt.to) return;

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
                        document.getElementById('progress-bar').style.width = data.progress + '%';
                        document.getElementById('progress-text').textContent = data.progress;
                        updateCounts();
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