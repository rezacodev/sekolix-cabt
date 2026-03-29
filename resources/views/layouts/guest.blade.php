<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>{{ config('app.name', 'CAT Sekolix') }}</title>
    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=figtree:400,500,600,700&display=swap" rel="stylesheet" />
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="font-sans antialiased">
    <div class="min-h-screen flex">
        {{-- Sisi kiri: branding --}}
        <div class="hidden lg:flex lg:w-1/2 bg-gradient-to-br from-indigo-700 via-indigo-600 to-violet-700 flex-col justify-between p-12">
            <div>
                <div class="flex items-center gap-3">
                    <div class="w-10 h-10 bg-white/20 rounded-xl flex items-center justify-center">
                        <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                        </svg>
                    </div>
                    <span class="text-white font-bold text-xl">{{ config('app.name') }}</span>
                </div>
            </div>

            <div>
                <h1 class="text-4xl font-bold text-white leading-snug mb-4">
                    Selamat datang di<br>Platform Ujian Digital
                </h1>
                <p class="text-indigo-200 text-lg leading-relaxed">
                    Sistem Computer Assisted Test yang aman, adil, dan efisien untuk evaluasi belajar peserta didik.
                </p>

                <div class="mt-10 grid grid-cols-3 gap-6">
                    <div class="bg-white/10 rounded-xl p-4 text-center">
                        <p class="text-2xl font-bold text-white">6</p>
                        <p class="text-indigo-200 text-sm mt-1">Paket Ujian</p>
                    </div>
                    <div class="bg-white/10 rounded-xl p-4 text-center">
                        <p class="text-2xl font-bold text-white">6+</p>
                        <p class="text-indigo-200 text-sm mt-1">Tipe Soal</p>
                    </div>
                    <div class="bg-white/10 rounded-xl p-4 text-center">
                        <p class="text-2xl font-bold text-white">100%</p>
                        <p class="text-indigo-200 text-sm mt-1">Berbasis Web</p>
                    </div>
                </div>
            </div>

            <div class="text-indigo-300 text-sm">
                &copy; {{ date('Y') }} {{ config('app.name') }}. All rights reserved.
            </div>
        </div>

        {{-- Sisi kanan: form --}}
        <div class="flex-1 flex flex-col justify-center px-6 py-12 sm:px-12 lg:px-16 bg-white">
            <div class="mx-auto w-full max-w-md">
                {{-- Mobile logo --}}
                <div class="lg:hidden flex items-center gap-3 mb-8">
                    <div class="w-9 h-9 bg-indigo-600 rounded-lg flex items-center justify-center">
                        <svg class="w-5 h-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                        </svg>
                    </div>
                    <span class="font-bold text-gray-900 text-lg">{{ config('app.name') }}</span>
                </div>

                {{ $slot }}
            </div>
        </div>
    </div>
</body>
</html>
