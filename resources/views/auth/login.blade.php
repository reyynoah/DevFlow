<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Log in - DevFlow</title>

    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=inter:400,500,600" rel="stylesheet" />

    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="bg-[#F8FAF8] text-[#0F3D3A] antialiased" style="font-family: 'Inter', ui-sans-serif, system-ui, sans-serif;">

    <div class="flex min-h-screen w-full">

        {{-- KIRI: form --}}
        <div class="relative flex w-full flex-col justify-center bg-[#F8FAF8] px-6 py-24 md:w-1/2 md:px-12">

            {{-- Logo --}}
            <a href="{{ url('/') }}" class="absolute left-8 top-8 flex items-center gap-2.5 md:left-10 md:top-9">
                <svg class="h-7 w-7 text-[#0F3D3A]" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-linecap="round" aria-hidden="true">
                    @for ($i = 0; $i < 28; $i++)
                        <line x1="12" y1="{{ $i % 2 ? 4.5 : 2 }}" x2="12" y2="{{ $i % 2 ? 8.5 : 9 }}"
                              stroke-width="1" transform="rotate({{ $i * (360 / 28) }} 12 12)" />
                    @endfor
                </svg>
                <span class="text-[17px] font-semibold tracking-tight">DevFlow</span>
            </a>

            <div class="mx-auto w-full max-w-[22rem]">

                <div class="mb-9 text-center">
                    <h1 class="text-3xl font-medium tracking-tight">Welcome back</h1>
                    <p class="mt-1.5 text-sm text-[#0F3D3A]/70">Please enter your details.</p>
                </div>

                @if (session('status'))
                    <div class="mb-4 text-center text-sm font-medium text-green-700">{{ session('status') }}</div>
                @endif

                <form method="POST" action="{{ route('login') }}" class="space-y-5">
                    @csrf

                    <div>
                        <label for="email" class="mb-1.5 block text-sm font-medium">Email</label>
                        <input id="email" type="email" name="email" value="{{ old('email') }}"
                               placeholder="Enter your email" required autofocus autocomplete="username"
                               class="block w-full rounded-md border border-[#CBD5CC] bg-white px-3.5 py-2.5 text-sm text-[#0F3D3A] shadow-sm placeholder:text-[#0F3D3A]/40 focus:border-[#46633F] focus:outline-none focus:ring-2 focus:ring-[#46633F]/20">
                        @error('email')
                            <p class="mt-1.5 text-sm text-red-600">{{ $message }}</p>
                        @enderror
                    </div>

                    <div>
                        <label for="password" class="mb-1.5 block text-sm font-medium">Password</label>
                        <input id="password" type="password" name="password"
                               placeholder="••••••••" required autocomplete="current-password"
                               class="block w-full rounded-md border border-[#CBD5CC] bg-white px-3.5 py-2.5 text-sm text-[#0F3D3A] shadow-sm placeholder:text-[#0F3D3A]/40 focus:border-[#46633F] focus:outline-none focus:ring-2 focus:ring-[#46633F]/20">
                        @error('password')
                            <p class="mt-1.5 text-sm text-red-600">{{ $message }}</p>
                        @enderror
                    </div>

                    <div class="flex items-center justify-end">

                        @if (Route::has('password.request'))
                            <a href="{{ route('password.request') }}" class="text-sm text-[#0F3D3A]/80 hover:text-[#0F3D3A] hover:underline">
                                Forgot password
                            </a>
                        @endif
                    </div>

                    <button type="submit"
                            class="w-full rounded-md bg-[#46633F] px-4 py-2.5 text-sm font-medium text-white transition hover:bg-[#3A5334] focus:outline-none focus:ring-2 focus:ring-[#46633F] focus:ring-offset-2 focus:ring-offset-[#F8FAF8]">
                        Sign in
                    </button>
                </form>

                <p class="mt-7 text-center text-sm text-[#0F3D3A]/80">
                    Don't have an account?
                    <a href="{{ route('register') }}" class="font-medium text-[#0F3D3A] hover:underline">Sign up</a>
                </p>
            </div>

            <p class="absolute bottom-8 left-8 hidden text-sm text-[#0F3D3A]/70 md:block md:left-10">
                &copy; DevFlow {{ date('Y') }}
            </p>
        </div>

        {{-- KANAN: ilustrasi 3D (SVG inline, tanpa file gambar) --}}
        <div class="relative hidden w-1/2 overflow-hidden bg-[#DCE8D8] md:block" aria-hidden="true">
            <svg class="absolute inset-0 h-full w-full" viewBox="0 0 600 800" preserveAspectRatio="xMidYMid slice" xmlns="http://www.w3.org/2000/svg">
            <defs>
              <linearGradient id="bg" x1="0" y1="0" x2="1" y2="1">
                <stop offset="0" stop-color="#81bc6a"/><stop offset="1" stop-color="#D2DFCE"/>
              </linearGradient>
              <radialGradient id="ball" cx="35%" cy="30%" r="75%">
                <stop offset="0" stop-color="#FFFFFF"/><stop offset=".55" stop-color="#E9F0E6"/><stop offset="1" stop-color="#B9C9B7"/>
              </radialGradient>
              <filter id="blur" x="-20%" y="-20%" width="140%" height="140%"><feGaussianBlur stdDeviation="9"/></filter>
              <filter id="blur2"><feGaussianBlur stdDeviation="3"/></filter>
              <path id="p" d="M-90 760 C20 600 90 700 190 640 C300 575 310 470 230 430 C150 390 170 290 300 262 C420 236 470 160 560 120 C620 94 660 40 720 0"/>
            </defs>
            <rect width="600" height="800" fill="url(#bg)"/>
            <!-- sombra di lantai -->
            <use href="#p" transform="translate(14 34)" fill="none" stroke="#8FA38F" stroke-opacity=".45" stroke-width="84" stroke-linecap="round" filter="url(#blur)"/>
            <!-- sisi dinding luar (ketebalan) -->
            <use href="#p" transform="translate(0 16)" fill="none" stroke="#9DB09D" stroke-width="76" stroke-linecap="round"/>
            <use href="#p" transform="translate(0 8)" fill="none" stroke="#B3C4B1" stroke-width="76" stroke-linecap="round"/>
            <!-- bibir atas dinding -->
            <use href="#p" fill="none" stroke="#E4EDE0" stroke-width="76" stroke-linecap="round"/>
            <!-- alur dalam: dinding dalam lebih gelap -->
            <use href="#p" transform="translate(0 3)" fill="none" stroke="#A9BBA7" stroke-width="52" stroke-linecap="round"/>
            <use href="#p" transform="translate(0 6)" fill="none" stroke="#C3D2C0" stroke-width="46" stroke-linecap="round"/>
            <!-- sambungan segmen -->
            <use href="#p" fill="none" stroke="#9DB09D" stroke-opacity=".7" stroke-width="76" stroke-dasharray="1.5 138" stroke-dashoffset="-60"/>
            <!-- bola -->
            <ellipse cx="282" cy="488" rx="16" ry="6" fill="#7E947F" fill-opacity=".55" filter="url(#blur2)"/>
            <circle cx="277" cy="471" r="15" fill="url(#ball)"/>
            </svg>
        </div>

    </div>
</body>
</html>