@php $p = $project ?? null; @endphp

<div class="space-y-4">
    <div>
        <label class="block text-sm font-medium">Nama Project</label>
        <input type="text" name="name" value="{{ old('name', $p->name ?? '') }}"
               class="mt-1 w-full border-gray-300 rounded-md" required>
        @error('name') <p class="text-red-600 text-sm">{{ $message }}</p> @enderror
    </div>

    <div>
        <label class="block text-sm font-medium">Deskripsi</label>
        <textarea name="description" rows="3"
                  class="mt-1 w-full border-gray-300 rounded-md">{{ old('description', $p->description ?? '') }}</textarea>
    </div>

    <div class="grid grid-cols-2 gap-4">
        <div>
            <label class="block text-sm font-medium">Kategori</label>
            <input type="text" name="category" value="{{ old('category', $p->category ?? '') }}"
                   class="mt-1 w-full border-gray-300 rounded-md" placeholder="Web, Mobile, dll">
        </div>
        <div>
            <label class="block text-sm font-medium">Prioritas</label>
            <select name="priority" class="mt-1 w-full border-gray-300 rounded-md">
                @foreach (['low' => 'Low', 'medium' => 'Medium', 'high' => 'High'] as $val => $label)
                    <option value="{{ $val }}" @selected(old('priority', $p->priority ?? 'medium') === $val)>{{ $label }}</option>
                @endforeach
            </select>
        </div>
    </div>

    <div class="grid grid-cols-2 gap-4">
        <div>
            <label class="block text-sm font-medium">Tanggal Mulai</label>
            <input type="date" name="start_date" value="{{ old('start_date', $p->start_date ?? '') }}"
                   class="mt-1 w-full border-gray-300 rounded-md">
        </div>
        <div>
            <label class="block text-sm font-medium">Deadline</label>
            <input type="date" name="deadline" value="{{ old('deadline', $p->deadline ?? '') }}"
                   class="mt-1 w-full border-gray-300 rounded-md">
            @error('deadline') <p class="text-red-600 text-sm">{{ $message }}</p> @enderror
        </div>
    </div>
</div>