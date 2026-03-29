<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ config('app.name', 'Sekolix CABT') }} â€” Masuk</title>
    <meta name="description" content="Platform Computer Assisted Test (CAT) berbasis web untuk sekolah.">
    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=plus-jakarta-sans:400,600,700,800&family=syne:700,800" rel="stylesheet" />
    <style>
        *,
        *::before,
        *::after {
            box-sizing: border-box;
            margin: 0;
            padding: 0;
        }

        :root {
            --bg: #f5f6fa;
            --surface: #ffffff;
            --ink: #0a0f1e;
            --ink-soft: #374151;
            --accent: #4361ee;
            --accent-mid: #7b93fd;
            --muted: #6b7280;
            --border: #e5e7eb;
        }

        html,
        body {
            height: 100%;
            font-family: 'Plus Jakarta Sans', system-ui, sans-serif;
            background: var(--bg);
            color: var(--ink);
            -webkit-font-smoothing: antialiased;
        }

        /* Orbs */
        body::after {
            content: '';
            position: fixed;
            width: 600px;
            height: 600px;
            top: -200px;
            right: -150px;
            border-radius: 50%;
            background: radial-gradient(circle, rgba(67, 97, 238, 0.1) 0%, transparent 70%);
            filter: blur(80px);
            pointer-events: none;
        }

        .orb-2 {
            position: fixed;
            width: 400px;
            height: 400px;
            bottom: -200px;
            left: -150px;
            border-radius: 50%;
            background: radial-gradient(circle, rgba(247, 37, 133, 0.06) 0%, transparent 70%);
            filter: blur(80px);
            pointer-events: none;
        }

        .wrap {
            min-height: 100vh;
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            padding: 2rem 1.5rem;
            position: relative;
            z-index: 1;
        }

        /* Logo */
        .logo {
            display: flex;
            align-items: center;
            gap: 10px;
            margin-bottom: 0.75rem;
            text-decoration: none;
        }

        .logo-mark {
            width: 40px;
            height: 40px;
            border-radius: 12px;
            background: rgba(67, 97, 238, 0.08);
            border: 1px solid rgba(67, 97, 238, 0.15);
            display: flex;
            align-items: center;
            justify-content: center;
            position: relative;
            overflow: hidden;
        }

        .logo-mark::after {
            content: '';
            position: absolute;
            bottom: -5px;
            right: -5px;
            width: 18px;
            height: 18px;
            background: var(--accent);
            border-radius: 4px;
        }

        .logo-mark svg {
            position: relative;
            z-index: 1;
        }

        .logo-text {
            font-family: 'Syne', sans-serif;
            font-size: 1.2rem;
            font-weight: 800;
            color: var(--ink);
            letter-spacing: -0.02em;
        }

        .logo-text span {
            color: var(--accent);
        }

        .tagline {
            font-size: 0.875rem;
            color: var(--muted);
            text-align: center;
            margin-bottom: 3rem;
            letter-spacing: 0.01em;
        }

        /* Cards container */
        .cards {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 1rem;
            width: 100%;
            max-width: 480px;
        }

        @media (max-width: 480px) {
            .cards {
                grid-template-columns: 1fr;
            }
        }

        .card {
            background: var(--surface);
            border: 1px solid var(--border);
            border-radius: 20px;
            padding: 2rem 1.5rem;
            text-decoration: none;
            color: var(--ink);
            transition: box-shadow 0.2s, border-color 0.2s, transform 0.2s;
            box-shadow: 0 1px 3px rgba(0, 0, 0, 0.06), 0 4px 16px rgba(0, 0, 0, 0.04);
            display: flex;
            flex-direction: column;
            align-items: center;
            text-align: center;
            gap: 0.75rem;
            position: relative;
            overflow: hidden;
        }

        .card::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            height: 2px;
            opacity: 0;
            transition: opacity 0.3s;
        }

        .card:hover {
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1), 0 12px 32px rgba(0, 0, 0, 0.06);
            border-color: #d1d5db;
            transform: translateY(-3px);
        }

        .card:hover::before {
            opacity: 1;
        }

        .card-peserta::before {
            background: linear-gradient(90deg, var(--accent), var(--accent-mid));
        }

        .card-admin::before {
            background: linear-gradient(90deg, #f72585, #7209b7);
        }

        .card-icon {
            width: 52px;
            height: 52px;
            border-radius: 16px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.5rem;
            flex-shrink: 0;
        }

        .card-peserta .card-icon {
            background: rgba(67, 97, 238, 0.15);
            border: 1px solid rgba(67, 97, 238, 0.25);
        }

        .card-admin .card-icon {
            background: rgba(247, 37, 133, 0.12);
            border: 1px solid rgba(247, 37, 133, 0.2);
        }

        .card-label {
            font-family: 'Syne', sans-serif;
            font-size: 1rem;
            font-weight: 800;
            letter-spacing: -0.01em;
        }

        .card-desc {
            font-size: 0.8125rem;
            color: var(--muted);
            line-height: 1.5;
        }

        .card-arrow {
            margin-top: 0.5rem;
            font-size: 0.75rem;
            font-weight: 700;
            letter-spacing: 0.04em;
            text-transform: uppercase;
            display: flex;
            align-items: center;
            gap: 5px;
        }

        .card-peserta .card-arrow {
            color: var(--accent);
        }

        .card-admin .card-arrow {
            color: #c2185b;
        }

        .footer-note {
            margin-top: 2.5rem;
            font-size: 0.8125rem;
            color: #9ca3af;
            text-align: center;
        }
    </style>
</head>

<body>
    <div class="orb-2"></div>

    <div class="wrap">
        <a href="/" class="logo">
            <div class="logo-mark">
                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="#4361ee" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
                    <path d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                </svg>
            </div>
            <span class="logo-text">Sekolix <span>CABT</span></span>
        </a>

        <p class="tagline">Silakan pilih portal masuk sesuai peran Anda</p>

        <div class="cards">
            {{-- Peserta --}}
            <a href="{{ route('login') }}" class="card card-peserta">
                <div class="card-icon">
                    <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="#4361ee" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <path d="M22 10v6M2 10l10-5 10 5-10 5z" />
                        <path d="M6 12v5c3 3 9 3 12 0v-5" />
                    </svg>
                </div>
                <div class="card-label">Peserta</div>
                <div class="card-desc">Login untuk mengerjakan ujian yang telah dijadwalkan</div>
                <div class="card-arrow">
                    Masuk
                    <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round">
                        <path d="M5 12h14M12 5l7 7-7 7" />
                    </svg>
                </div>
            </a>

            {{-- Admin / Guru --}}
            <a href="{{ url('/cabt') }}" class="card card-admin">
                <div class="card-icon">
                    <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="#f72585" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <circle cx="12" cy="12" r="3" />
                        <path d="M19.4 15a1.65 1.65 0 00.33 1.82l.06.06a2 2 0 010 2.83 2 2 0 01-2.83 0l-.06-.06a1.65 1.65 0 00-1.82-.33 1.65 1.65 0 00-1 1.51V21a2 2 0 01-4 0v-.09A1.65 1.65 0 009 19.4a1.65 1.65 0 00-1.82.33l-.06.06a2 2 0 01-2.83-2.83l.06-.06A1.65 1.65 0 004.68 15a1.65 1.65 0 00-1.51-1H3a2 2 0 010-4h.09A1.65 1.65 0 004.6 9a1.65 1.65 0 00-.33-1.82l-.06-.06a2 2 0 012.83-2.83l.06.06A1.65 1.65 0 009 4.68a1.65 1.65 0 001-1.51V3a2 2 0 014 0v.09a1.65 1.65 0 001 1.51 1.65 1.65 0 001.82-.33l.06-.06a2 2 0 012.83 2.83l-.06.06A1.65 1.65 0 0019.4 9a1.65 1.65 0 001.51 1H21a2 2 0 010 4h-.09a1.65 1.65 0 00-1.51 1z" />
                    </svg>
                </div>
                <div class="card-label">Admin / Guru</div>
                <div class="card-desc">Kelola soal, sesi ujian, peserta, dan laporan nilai</div>
                <div class="card-arrow">
                    Masuk
                    <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round">
                        <path d="M5 12h14M12 5l7 7-7 7" />
                    </svg>
                </div>
            </a>
        </div>

        <p class="footer-note">&copy; {{ date('Y') }} {{ config('app.name', 'Sekolix CABT') }}</p>
    </div>
</body>

</html>