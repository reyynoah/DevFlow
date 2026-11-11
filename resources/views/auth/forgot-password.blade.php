@php
    $input = 'block w-full rounded-md border border-[#CBD5CC] bg-white px-3.5 py-2.5 text-sm text-[#0F3D3A] shadow-sm placeholder:text-[#0F3D3A]/40 focus:border-[#46633F] focus:outline-none focus:ring-2 focus:ring-[#46633F]/20';
    $label = 'mb-1.5 block text-sm font-medium';
    $button = 'w-full rounded-md bg-[#46633F] px-4 py-2.5 text-sm font-medium text-white transition hover:bg-[#3A5334] focus:outline-none focus:ring-2 focus:ring-[#46633F] focus:ring-offset-2 focus:ring-offset-[#F8FAF8]';
    $link = 'font-medium text-[#0F3D3A] hover:underline';
@endphp
<x-guest-layout>
    <div class="mb-9 text-center">
        <h1 class="text-3xl font-medium tracking-tight">Forgot password?</h1>
        <p class="mt-1.5 text-sm text-[#0F3D3A]/70">Enter your email and we'll send you a link to reset it.</p>
    </div>

    @if (session('status'))
        <div class="mb-5 text-center text-sm font-medium text-green-700">{{ session('status') }}</div>
    @endif

    <form method="POST" action="{{ route('password.email') }}" class="space-y-5">
        @csrf

        <div>
            <label for="email" class="{{ $label }}">Email</label>
            <input id="email" type="email" name="email" value="{{ old('email') }}" placeholder="Enter your email"
                   required autofocus class="{{ $input }}">
            @error('email')
                <p class="mt-1.5 text-sm text-red-600">{{ $message }}</p>
            @enderror
        </div>

        <button type="submit" class="{{ $button }}">Send reset link</button>
    </form>

    <p class="mt-7 text-center text-sm text-[#0F3D3A]/80">
        <a href="{{ route('login') }}" class="{{ $link }}">Back to sign in</a>
    </p>
</x-guest-layout>