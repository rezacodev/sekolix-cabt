<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>404 — Halaman Tidak Ditemukan</title>
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
            background: #eff6ff;
            display: flex; align-items: center; justify-content: center;
            margin: 0 auto 1.5rem;
        }
        .icon-wrap svg { width: 2.5rem; height: 2.5rem; color: #3b82f6; }
        .code {
            font-size: 1rem; font-weight: 700;
            color: #3b82f6;
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
                    d="M21 21l-5.197-5.197m0 0A7.5 7.5 0 105.196 15.803 7.5 7.5 0 0016.803 15.803z"/>
            </svg>
        </div>
        <p class="code">Error 404</p>
        <h1>Halaman Tidak Ditemukan</h1>
        <p>Halaman yang Anda cari tidak ada atau mungkin sudah dipindahkan. Periksa kembali URL yang Anda masukkan.</p>
        <a href="/" class="btn">
            <svg fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6"/>
            </svg>
            Ke Beranda
        </a>
    </div>
</body>
</html>
