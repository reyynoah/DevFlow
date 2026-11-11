{{--
    Kartu "Aktivitas Terbaru" (dipakai di halaman detail project).
    Data: $activities = 10 log terakhir project, sudah di-load beserta user-nya.
--}}
<section class="rounded-2xl border border-[#E6EAE4] bg-white p-6">
    <h2 class="text-lg font-semibold tracking-tight">Aktivitas Terbaru</h2>

    <ol class="mt-4 space-y-4">
        @forelse ($activities as $log)
            <li class="relative flex gap-3">
                {{-- Titik timeline + garis penghubung antar aktivitas --}}
                <div class="relative flex flex-col items-center">
                    <span class="mt-1.5 h-2.5 w-2.5 shrink-0 rounded-full bg-[#4FC3CF] ring-4 ring-[#E0F4F6]"></span>
                    @unless ($loop->last)
                        <span class="mt-1 w-px flex-1 bg-[#E6EAE4]"></span>
                    @endunless
                </div>

                <div class="min-w-0 pb-1">
                    <p class="text-sm leading-5 text-[#0F3D3A]/85">{{ $log->description }}</p>
                    {{-- Waktu relatif dalam bahasa Indonesia, mis. "5 menit yang lalu" --}}
                    <p class="mt-0.5 text-xs text-[#0F3D3A]/45">{{ $log->created_at->locale('id')->diffForHumans() }}</p>
                </div>
            </li>
        @empty
            <li class="text-sm text-[#0F3D3A]/45">Belum ada aktivitas.</li>
        @endforelse
    </ol>
</section>