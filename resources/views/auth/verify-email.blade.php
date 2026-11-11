@php
    $input = 'block w-full rounded-md border border-[#CBD5CC] bg-white px-3.5 py-2.5 text-sm text-[#0F3D3A] shadow-sm placeholder:text-[#0F3D3A]/40 focus:border-[#46633F] focus:outline-none focus:ring-2 focus:ring-[#46633F]/20';
    $label = 'mb-1.5 block text-sm font-medium';
    $button = 'w-full rounded-md bg-[#46633F] px-4 py-2.5 text-sm font-medium text-white transition hover:bg-[#3A5334] focus:outline-none focus:ring-2 focus:ring-[#46633F] focus:ring-offset-2 focus:ring-offset-[#F8FAF8]';
    $link = 'font-medium text-[#0F3D3A] hover:underline';
@endphp
<x-guest-layout>
    <div class="mb-9 text-center">
        <h1 class="text-3xl font-medium tracking-tight">Verify your email</h1>
        <p class="mt-1.5 text-sm text-[#0F3D3A]/70">We sent a verification link to your email. Click it to get started. Didn't get it? We can send another.</p>
    </div>

    @if (session('status') == 'verification-link-sent')
        <div class="mb-5 text-center text-sm font-medium text-green-700">
            A new verification link has been sent to your email.
        </div>
    @endif

    <form method="POST" action="{{ route('verification.send') }}">
        @csrf
        <button type="submit" class="{{ $button }}">Resend verification email</button>
    </form>

    <form method="POST" action="{{ route('logout') }}" class="mt-7 text-center text-sm">
        @csrf
        <button type="submit" class="{{ $link }}">Log out</button>
    </form>
</x-guest-layout>