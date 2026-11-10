<div class="max-w-7xl mx-auto sm:px-6 lg:px-8 pb-8">
    <div class="bg-white p-5 rounded-lg shadow">
        <h3 class="font-semibold mb-3">Aktivitas Terbaru</h3>
        <div class="divide-y">
            @forelse ($activities as $log)
                <div class="py-2 flex justify-between gap-4 text-sm">
                    <span>{{ $log->description }}</span>
                    <span class="text-xs text-gray-400 whitespace-nowrap">{{ $log->created_at->diffForHumans() }}</span>
                </div>
            @empty
                <p class="text-sm text-gray-400">Belum ada aktivitas.</p>
            @endforelse
        </div>
    </div>
</div>