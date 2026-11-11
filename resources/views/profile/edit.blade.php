{{--
    Halaman Profile (/profile) versi simpel.
    Isi: (1) ubah nama & email, (2) ganti password, (3) tautan kecil untuk hapus akun.
    Semua form tetap memakai route bawaan Breeze, jadi controller tidak perlu diubah:
      profile.update  -> ProfileController@update
      password.update -> PasswordController@update
      profile.destroy -> ProfileController@destroy
    Catatan: file di resources/views/profile/partials/* tidak dipakai lagi dan boleh dihapus.
--}}
@php
    // Kelas input yang dipakai berulang supaya semua field seragam
    $input = 'mt-1.5 block w-full rounded-lg border-[#E0E5DE] text-sm focus:border-[#0F3D3A] focus:ring-[#0F3D3A]/20';
    $label = 'block text-sm font-medium';
    $btn   = 'rounded-lg bg-[#0F3D3A] px-4 py-2 text-sm font-medium text-white transition hover:bg-[#0B2E2C] focus:outline-none focus:ring-2 focus:ring-[#0F3D3A]/30 focus:ring-offset-2';
@endphp

<x-app-layout>
    {{-- Kolom konten diletakkan di tengah halaman (mx-auto); judul ikut di dalam kolom supaya sejajar dengan kartu --}}
    <div class="px-6 pb-14 pt-8 lg:px-10 lg:pt-12">
        <div class="mx-auto max-w-xl space-y-5">

            <div class="pb-1">
                <h1 class="text-2xl font-semibold tracking-tight">Profile</h1>
                <p class="mt-1 text-sm text-[#0F3D3A]/60">Kelola akun dan password kamu.</p>
            </div>

            {{-- ===== Kartu 1: informasi akun ===== --}}
            <section class="rounded-2xl border border-[#E6EAE4] bg-white p-6">
                {{-- Avatar inisial + nama & email saat ini --}}
                <div class="mb-5 flex items-center gap-3">
                    <span class="flex h-11 w-11 items-center justify-center rounded-full bg-[#0F3D3A] text-base font-semibold text-white">
                        {{ mb_strtoupper(mb_substr($user->name, 0, 1)) }}
                    </span>
                    <div class="min-w-0">
                        <p class="truncate text-sm font-semibold">{{ $user->name }}</p>
                        <p class="truncate text-xs text-[#0F3D3A]/55">{{ $user->email }}</p>
                    </div>
                </div>

                {{-- Form kecil untuk kirim ulang email verifikasi (hanya dipakai kalau verifikasi email diaktifkan) --}}
                <form id="send-verification" method="post" action="{{ route('verification.send') }}">@csrf</form>

                <form method="post" action="{{ route('profile.update') }}" class="space-y-4">
                    @csrf
                    @method('patch')

                    <div>
                        <label for="name" class="{{ $label }}">Nama</label>
                        <input id="name" name="name" type="text" value="{{ old('name', $user->name) }}" required autocomplete="name" class="{{ $input }}">
                        @error('name') <p class="mt-1 text-sm text-[#B93E3E]">{{ $message }}</p> @enderror
                    </div>

                    <div>
                        <label for="email" class="{{ $label }}">Email</label>
                        <input id="email" name="email" type="email" value="{{ old('email', $user->email) }}" required autocomplete="username" class="{{ $input }}">
                        @error('email') <p class="mt-1 text-sm text-[#B93E3E]">{{ $message }}</p> @enderror

                        {{-- Muncul hanya kalau email belum diverifikasi --}}
                        @if ($user instanceof \Illuminate\Contracts\Auth\MustVerifyEmail && ! $user->hasVerifiedEmail())
                            <p class="mt-2 text-sm text-[#0F3D3A]/70">
                                Email belum diverifikasi.
                                <button form="send-verification" class="font-medium underline hover:text-[#0F3D3A]">Kirim ulang email verifikasi</button>
                            </p>
                            @if (session('status') === 'verification-link-sent')
                                <p class="mt-1 text-sm font-medium text-[#1F6B4A]">Link verifikasi baru sudah dikirim.</p>
                            @endif
                        @endif
                    </div>

                    <div class="flex items-center gap-3 pt-1">
                        <button class="{{ $btn }}">Simpan</button>

                        {{-- Tulisan "Tersimpan" hilang sendiri setelah 2 detik --}}
                        @if (session('status') === 'profile-updated')
                            <span x-data="{ show: true }" x-show="show" x-transition x-init="setTimeout(() => show = false, 2500)"
                                  class="text-sm text-[#1F6B4A]">Tersimpan &#10003;</span>
                        @endif
                    </div>
                </form>
            </section>

            {{-- ===== Kartu 2: ganti password ===== --}}
            <section class="rounded-2xl border border-[#E6EAE4] bg-white p-6">
                <h2 class="text-base font-semibold">Ganti password</h2>

                <form method="post" action="{{ route('password.update') }}" class="mt-4 space-y-4">
                    @csrf
                    @method('put')

                    <div>
                        <label for="current_password" class="{{ $label }}">Password saat ini</label>
                        <input id="current_password" name="current_password" type="password" autocomplete="current-password" class="{{ $input }}">
                        @error('current_password', 'updatePassword') <p class="mt-1 text-sm text-[#B93E3E]">{{ $message }}</p> @enderror
                    </div>

                    <div class="grid gap-4 sm:grid-cols-2">
                        <div>
                            <label for="password" class="{{ $label }}">Password baru</label>
                            <input id="password" name="password" type="password" autocomplete="new-password" class="{{ $input }}">
                            @error('password', 'updatePassword') <p class="mt-1 text-sm text-[#B93E3E]">{{ $message }}</p> @enderror
                        </div>
                        <div>
                            <label for="password_confirmation" class="{{ $label }}">Konfirmasi password</label>
                            <input id="password_confirmation" name="password_confirmation" type="password" autocomplete="new-password" class="{{ $input }}">
                            @error('password_confirmation', 'updatePassword') <p class="mt-1 text-sm text-[#B93E3E]">{{ $message }}</p> @enderror
                        </div>
                    </div>

                    <div class="flex items-center gap-3 pt-1">
                        <button class="{{ $btn }}">Ubah password</button>

                        @if (session('status') === 'password-updated')
                            <span x-data="{ show: true }" x-show="show" x-transition x-init="setTimeout(() => show = false, 2500)"
                                  class="text-sm text-[#1F6B4A]">Tersimpan &#10003;</span>
                        @endif
                    </div>
                </form>
            </section>

            {{-- ===== Hapus akun: tautan kecil, form konfirmasi baru muncul setelah diklik ===== --}}
            <div x-data="{ open: {{ $errors->userDeletion->isNotEmpty() ? 'true' : 'false' }} }" class="px-1">
                <button type="button" x-show="!open" @click="open = true"
                        class="text-sm text-[#0F3D3A]/50 underline-offset-2 transition hover:text-[#B93E3E] hover:underline">
                    Hapus akun
                </button>

                <form x-show="open" x-cloak x-transition method="post" action="{{ route('profile.destroy') }}"
                      class="rounded-2xl border border-[#F3C9C9] bg-[#FDF4F4] p-5">
                    @csrf
                    @method('delete')

                    <p class="text-sm font-semibold text-[#B93E3E]">Hapus akun secara permanen?</p>
                    <p class="mt-1 text-sm text-[#0F3D3A]/65">Semua data akun akan terhapus dan tidak bisa dikembalikan. Masukkan password untuk konfirmasi.</p>

                    <input name="password" type="password" placeholder="Password" autocomplete="current-password"
                           class="mt-3 block w-full rounded-lg border-[#E8C4C4] text-sm focus:border-[#B93E3E] focus:ring-[#B93E3E]/20 sm:w-72">
                    @error('password', 'userDeletion') <p class="mt-1 text-sm text-[#B93E3E]">{{ $message }}</p> @enderror

                    <div class="mt-4 flex gap-2">
                        <button type="button" @click="open = false"
                                class="rounded-lg border border-[#E0E5DE] bg-white px-4 py-2 text-sm font-medium transition hover:bg-[#F3F5F2]">Batal</button>
                        <button class="rounded-lg bg-[#B93E3E] px-4 py-2 text-sm font-medium text-white transition hover:bg-[#9E3232]">Hapus akun</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</x-app-layout>