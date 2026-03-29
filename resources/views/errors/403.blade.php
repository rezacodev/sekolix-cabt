<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>403 — Akses Ditolak</title>
    <style>
        *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }
        body {
            font-family: ui-sans-serif, system-ui, -apple-system, sans-serif;
            background: #f8fafc;
            color: #1e293b;
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 1.5rem;
        }
        .card {
            background: #fff;
            border-radius: 1.5rem;
            box-shadow: 0 4px 6px -1px rgb(0 0 0 / .07), 0 2px 4px -2px rgb(0 0 0 / .05);
            padding: 3rem 2rem;
            text-align: center;
            max-width: 28rem;
            width: 100%;
        }
        .icon-wrap {
            width: 5rem; height: 5rem;
            border-radius: 9999px;
            background: #fef2f2;
            display: flex; align-items: center; justify-content: center;
            margin: 0 auto 1.5rem;
        }
        .icon-wrap svg { width: 2.5rem; height: 2.5rem; color: #ef4444; }
        .code {
            font-size: 1rem; font-weight: 700;
            color: #ef4444;
            letter-spacing: .15em;
            text-transform: uppercase;
            margin-bottom: .5rem;
        }
        h1 { font-size: 1.5rem; font-weight: 800; margin-bottom: .75rem; }
        p  { color: #64748b; font-size: .9375rem; line-height: 1.6; margin-bottom: 1.75rem; }
        .btn {
            display: inline-flex; align-items: center; gap: .5rem;
            background: #4f46e5; color: #fff;
            padding: .65rem 1.5rem;
            border-radius: .75rem;
            font-weight: 600; font-size: .875rem;
            text-decoration: none;
            transition: background .15s;
        }
        .btn:hover { background: #4338ca; }
        .btn svg { width: 1rem; height: 1rem; }
    </style>
</head>
<body>
    <div class="card">
        <div class="icon-wrap">
            <svg fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round"
                    d="M12 9v3.75m-9.303 3.376c-.866 1.5.217 3.374 1.948 3.374h14.71c1.73 0 2.813-1.874 1.948-3.374L13.949 3.378c-.866-1.5-3.032-1.5-3.898 0L2.697 16.126zM12 15.75h.007v.008H12v-.008z"/>
            </svg>
        </div>
        <p class="code">Error 403</p>
        <h1>Akses Ditolak</h1>
        <p>Anda tidak memiliki izin untuk mengakses halaman ini. Pastikan Anda login dengan akun yang tepat.</p>
        <a href="{{ url()->previous() !== url()->current() ? url()->previous() : '/' }}" class="btn">
            <svg fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" d="M15 19l-7-7 7-7"/>
            </svg>
            Kembali
        </a>
    </div>
</body>
</html>
