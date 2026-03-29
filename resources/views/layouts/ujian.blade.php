<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>{{ $title ?? 'Ujian — '.config('app.name') }}</title>
    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=figtree:400,500,600&display=swap" rel="stylesheet" />
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    @stack('styles')
    <style>
        /* Disable text selection during exam */
        body { user-select: none; -webkit-user-select: none; }
        [x-cloak] { display: none !important; }

        /* Soal content: allow selection only inside answer inputs, not question text */
        .exam-soal-text { user-select: none; -webkit-user-select: none; }

        /* Anti-print: hide everything when printing to prevent screenshot via print dialog */
        @media print {
            body { visibility: hidden !important; }
            body::before {
                visibility: visible !important;
                position: fixed;
                inset: 0;
                display: flex;
                align-items: center;
                justify-content: center;
                font-size: 2rem;
                font-weight: bold;
                color: #1e293b;
                background: #ffffff;
                content: "Mencetak halaman ujian tidak diizinkan.";
            }
        }
    </style>
</head>
<body class="font-sans antialiased bg-slate-900 text-white overflow-hidden">
    {{ $slot }}

    @stack('scripts')
</body>
</html>
