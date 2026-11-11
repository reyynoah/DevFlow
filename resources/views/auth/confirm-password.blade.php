@php
    $input = 'block w-full rounded-md border border-[#CBD5CC] bg-white px-3.5 py-2.5 text-sm text-[#0F3D3A] shadow-sm placeholder:text-[#0F3D3A]/40 focus:border-[#46633F] focus:outline-none focus:ring-2 focus:ring-[#46633F]/20';
    $label = 'mb-1.5 block text-sm font-medium';
    $button = 'w-full rounded-md bg-[#46633F] px-4 py-2.5 text-sm font-medium text-white transition hover:bg-[#3A5334] focus:outline-none focus:ring-2 focus:ring-[#46633F] focus:ring-offset-2 focus:ring-offset-[#F8FAF8]';
    $link = 'font-medium text-[#0F3D3A] hover:underline';
@endphp
<x-guest-layout>
    <div class="mb-9 text-center">
        <h1 class="text-3xl font-medium tracking-tight">Confirm your password</h1>
        <p class="mt-1.5 text-sm text-[#0F3D3A]/70">This is a secure area. Please confirm your password to continue.</p>
    </div>

    <form method="POST" action="{{ route('password.confirm') }}" class="space-y-5">
        @csrf

        <div>
            <label for="password" class="{{ $label }}">Password</label>
            <input id="password" type="password" name="password" placeholder="••••••••"
                   required autofocus autocomplete="current-password" class="{{ $input }}">
            @error('password')
                <p class="mt-1.5 text-sm text-red-600">{{ $message }}</p>
            @enderror
        </div>

        <button type="submit" class="{{ $button }}">Confirm</button>
    </form>
</x-guest-layout>