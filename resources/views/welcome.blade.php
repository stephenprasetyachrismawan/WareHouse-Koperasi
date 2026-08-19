<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="scroll-smooth">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ __('Koperasi Desa Merah Putih') }} - {{ config('app.name', 'KDMP') }}</title>

    <link rel="icon" href="/favicon.ico" sizes="any">
    <link rel="icon" href="/favicon.svg" type="image/svg+xml">
    <link rel="apple-touch-icon" href="/apple-touch-icon.png">

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap"
        rel="stylesheet">

    <style>
        *,
        *::before,
        *::after {
            box-sizing: border-box;
        }

        html,
        body {
            background: #fff;
            overscroll-behavior-y: none;
            margin: 0;
        }

        html {
            -webkit-text-size-adjust: 100%;
        }

        .font-body {
            font-family: 'Inter', system-ui, sans-serif;
        }

        .stat-num {
            font-variant-numeric: tabular-nums;
        }

        /* ══════════════════════════════════════
               SCROLL REVEAL (subtle)
            ══════════════════════════════════════ */
        .reveal {
            transform: translateY(30px);
            transition: all 0.7s cubic-bezier(0.16, 1, 0.3, 1);
        }

        .reveal-enabled .reveal {
            opacity: 0;
        }

        .reveal.active {
            opacity: 1;
            transform: translateY(0);
        }

        .reveal-delay-1 {
            transition-delay: 0.1s;
        }

        .reveal-delay-2 {
            transition-delay: 0.15s;
        }

        .reveal-delay-3 {
            transition-delay: 0.2s;
        }

        .reveal-delay-4 {
            transition-delay: 0.25s;
        }

        /* ══════════════════════════════════════
               SERVICE CARDS
            ══════════════════════════════════════ */
        .svc-card {
            position: relative;
            border-radius: 1rem;
            overflow: hidden;
            background: #111;
            transition: all 0.4s ease;
            box-shadow: 0 4px 20px -4px rgba(0, 0, 0, 0.25);
        }

        .svc-card:hover {
            transform: translateY(-8px);
            box-shadow: 0 24px 48px -12px rgba(0, 0, 0, 0.45);
        }

        .svc-img-wrap {
            position: relative;
            height: 240px;
            overflow: hidden;
        }

        .svc-img {
            width: 100%;
            height: 100%;
            object-fit: cover;
            transition: transform 0.5s ease;
        }

        .svc-card:hover .svc-img {
            transform: scale(1.06);
        }

        .svc-img-wrap::after {
            content: '';
            position: absolute;
            inset: 0;
            background: linear-gradient(to bottom, transparent 50%, rgba(0, 0, 0, 0.6) 100%);
        }

        .svc-num-badge {
            position: absolute;
            top: 14px;
            left: 14px;
            width: 42px;
            height: 42px;
            display: flex;
            align-items: center;
            justify-content: center;
            border-radius: 12px;
            background: rgba(255, 255, 255, 0.15);
            backdrop-filter: blur(10px);
            border: 1px solid rgba(255, 255, 255, 0.2);
            font-size: 15px;
            font-weight: 700;
            color: #fff;
            z-index: 5;
        }

        .svc-content {
            position: relative;
            padding: 20px;
            background: linear-gradient(145deg, #1a1a1a 0%, #111 100%);
        }

        .svc-tag {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            font-size: 11px;
            font-weight: 600;
            letter-spacing: 0.08em;
            text-transform: uppercase;
            color: rgba(255, 255, 255, 0.4);
            margin-bottom: 8px;
        }

        .svc-tag-dot {
            width: 6px;
            height: 6px;
            border-radius: 50%;
            background: #dc2626;
        }

        .svc-title {
            font-size: 1.125rem;
            font-weight: 700;
            color: #fff;
            line-height: 1.3;
            margin: 0;
        }

        .svc-desc {
            margin-top: 6px;
            font-size: 0.8125rem;
            line-height: 1.6;
            color: rgba(255, 255, 255, 0.5);
        }

        .svc-action {
            display: flex;
            align-items: center;
            justify-content: space-between;
            margin-top: 14px;
            padding-top: 14px;
            border-top: 1px solid rgba(255, 255, 255, 0.06);
        }

        .svc-action-text {
            font-size: 12px;
            font-weight: 600;
            color: rgba(255, 255, 255, 0.35);
            letter-spacing: 0.03em;
        }

        .svc-action-arrow {
            width: 30px;
            height: 30px;
            display: flex;
            align-items: center;
            justify-content: center;
            border-radius: 50%;
            background: rgba(255, 255, 255, 0.06);
            color: rgba(255, 255, 255, 0.35);
            transition: all 0.3s ease;
        }

        .svc-card:hover .svc-action-arrow {
            background: #dc2626;
            color: #fff;
        }

        /* ══════════════════════════════════════
               FEATURE CARDS
            ══════════════════════════════════════ */
        .feat-card {
            padding: 1.75rem;
            border-radius: 1rem;
            background: #fafafa;
            border: 1px solid #f0f0f0;
            transition: all 0.35s ease;
        }

        .feat-card:hover {
            background: #fff;
            border-color: transparent;
            box-shadow: 0 16px 32px -8px rgba(0, 0, 0, 0.08);
            transform: translateY(-4px);
        }

        .feat-icon {
            width: 52px;
            height: 52px;
            display: flex;
            align-items: center;
            justify-content: center;
            border-radius: 14px;
            background: linear-gradient(135deg, #dc2626, #b91c1c);
            color: #fff;
            box-shadow: 0 6px 16px -3px rgba(220, 38, 38, 0.3);
        }

        /* ══════════════════════════════════════
               UTILITY
            ══════════════════════════════════════ */
        .glass-card {
            background: rgba(255, 255, 255, 0.08);
            backdrop-filter: blur(12px);
            -webkit-backdrop-filter: blur(12px);
            border: 1px solid rgba(255, 255, 255, 0.12);
        }

        details[open] summary~* {
            animation: fadeIn 0.3s ease-out;
        }

        @keyframes fadeIn {
            from {
                opacity: 0;
                transform: translateY(8px);
            }

            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        .faq-num {
            transition: all 0.25s ease;
        }

        details[open] .faq-num {
            background: #dc2626;
            color: #fff;
        }
    </style>

    @fonts
    @vite(['resources/css/app.css', 'resources/js/app.js', 'resources/js/welcome.js'])
</head>

<body class="bg-white font-body text-neutral-800 antialiased">

    {{-- ════════════════ NAVBAR ════════════════ --}}
    <header class="fixed inset-x-0 top-0 z-50 transition-all duration-500" id="navbar">
        <nav class="mx-auto flex max-w-7xl items-center justify-between px-5 py-5 lg:px-8">
            <a href="#beranda" class="flex items-center gap-2.5">
                <img src="{{ asset('images/logo-kdmp.png') }}" alt="KDMP Logo" class="h-10 w-auto rounded-lg">
            </a>
            <div class="hidden items-center gap-1 rounded-full bg-white/15 px-2 py-1 backdrop-blur-md lg:flex">
                @php $navLinks =
                ['Beranda'=>'#beranda','Layanan'=>'#layanan','Tentang'=>'#tentang','Fitur'=>'#fitur','Testimoni'=>'#testimoni','Kontak'=>'#kontak'];
                @endphp
                @foreach($navLinks as $label => $href)
                <a href="{{ $href }}"
                    class="rounded-full px-5 py-2 text-sm font-medium text-white/90 transition hover:bg-white/20 hover:text-white">{{
                    $label }}</a>
                @endforeach
            </div>
            <div class="flex items-center gap-3">
                @auth
                <a href="{{ route('dashboard') }}"
                    class="rounded-full border border-white/40 px-5 py-2.5 text-sm font-semibold text-white transition hover:bg-white/15">{{
                    __('Buka Dashboard') }}</a>
                <form method="POST" action="{{ route('logout') }}">
                    @csrf
                    <button type="submit"
                        class="rounded-full bg-red-700 px-6 py-2.5 text-sm font-bold text-white shadow-lg shadow-red-800/40 transition hover:bg-red-600 hover:scale-105">{{
                        __('Log out') }}</button>
                </form>
                @else
                <a href="{{ route('login') }}"
                    class="rounded-full border border-white/40 px-5 py-2.5 text-sm font-semibold text-white transition hover:bg-white/15">{{
                    __('Masuk') }}</a>
                @if (Route::has('register'))
                <a href="{{ route('register') }}"
                    class="rounded-full bg-red-700 px-6 py-2.5 text-sm font-bold text-white shadow-lg shadow-red-800/40 transition hover:bg-red-600 hover:scale-105">{{
                    __('Daftar') }}</a>
                @endif
                @endauth
            </div>
        </nav>
    </header>

    {{-- ════════════════ HERO ════════════════ --}}
    <section id="beranda" class="relative overflow-hidden bg-neutral-900" style="min-height:100vh">
        <div class="absolute inset-0 overflow-hidden">
            <video class="size-full object-cover" autoplay muted loop playsinline preload="auto" poster="/favicon.svg">
                <source src="{{ asset('videos/video-kdmp.mp4') }}" type="video/mp4">
            </video>
            <div class="absolute inset-0 bg-gradient-to-r from-neutral-950/95 via-neutral-950/70 to-neutral-950/40">
            </div>
            <div class="absolute inset-0 bg-gradient-to-t from-neutral-950/60 via-transparent to-transparent"></div>
        </div>

        <div class="relative mx-auto max-w-7xl px-5 sm:px-8 lg:px-12">
            <div class="flex min-h-screen flex-col justify-center pb-24 pt-32 lg:flex-row lg:items-center lg:gap-12">
                <div class="flex-1 max-w-xl">
                    <h1 class="text-4xl font-bold leading-[1.1] text-white sm:text-5xl lg:text-6xl xl:text-7xl">
                        Sistem Gudang<br>Koperasi <span class="text-red-400">Terpadu</span>
                    </h1>
                    <p class="mt-6 max-w-md text-base leading-relaxed text-white/70 sm:text-lg">
                        Kelola persediaan, pantau stok, dan optimalkan operasional gudang koperasi desa Anda dalam satu
                        platform digital yang terpercaya.
                    </p>
                    <div
                        class="mt-4 flex items-center gap-2 text-xs font-semibold uppercase tracking-wider text-white/40">
                        <span class="inline-block h-px w-8 bg-white/30"></span>
                        Dipercaya oleh 1000+ Koperasi Desa Merah Putih
                    </div>
                    <div class="mt-8 flex flex-wrap items-center gap-4">
                        <a href="{{ auth()->check() ? route('dashboard') : route('register') }}"
                            class="group rounded-full bg-red-700 px-8 py-3.5 text-sm font-bold text-white shadow-xl shadow-red-800/40 transition hover:bg-red-600 hover:scale-105">
                            {{ auth()->check() ? __('Buka Dashboard') : 'Mulai Sekarang' }} <span class="ml-2 inline-block transition group-hover:translate-x-1">→</span>
                        </a>
                        @guest
                        <a href="{{ route('login') }}"
                            class="rounded-full border border-white/40 px-8 py-3.5 text-sm font-bold text-white transition hover:bg-white/10">
                            Pelajari Lebih Lanjut
                        </a>
                        @endguest
                    </div>
                </div>

                <div class="mt-12 flex flex-col gap-4 lg:mt-0">
                    <div class="w-72 rounded-2xl bg-white/10 p-5 backdrop-blur-md ring-1 ring-white/15 glass-card">
                        <div class="flex items-center gap-3">
                            <div class="flex -space-x-3">
                                <span
                                    class="flex size-10 items-center justify-center rounded-full bg-red-500 text-xs font-bold text-white ring-2 ring-white/20">A</span>
                                <span
                                    class="flex size-10 items-center justify-center rounded-full bg-amber-500 text-xs font-bold text-white ring-2 ring-white/20">B</span>
                                <span
                                    class="flex size-10 items-center justify-center rounded-full bg-emerald-500 text-xs font-bold text-white ring-2 ring-white/20">C</span>
                            </div>
                            <div>
                                <p class="text-lg font-bold text-white">1000+</p>
                                <p class="text-xs text-white/60">Koperasi Desa Merah Putih</p>
                            </div>
                        </div>
                    </div>
                    <div
                        class="ml-auto w-72 rounded-2xl bg-white/10 p-5 backdrop-blur-md ring-1 ring-white/15 glass-card">
                        <div class="flex items-center gap-3">
                            <span
                                class="flex size-11 items-center justify-center rounded-xl bg-red-700 text-white shadow-lg shadow-red-700/30">
                                <svg class="size-5" fill="currentColor" viewBox="0 0 20 20">
                                    <path
                                        d="M3 12v3c0 1.657 3.134 3 7 3s7-1.343 7-3v-3c0 1.657-3.134 3-7 3s-7-1.343-7-3z" />
                                    <path
                                        d="M3 7v3c0 1.657 3.134 3 7 3s7-1.343 7-3V7c0 1.657-3.134 3-7 3S3 8.657 3 7z" />
                                    <path
                                        d="M17 5v3c0 1.657-3.134 3-7 3S3 9.657 3 8V5c0 1.657 3.134 3 7 3s7-1.343 7-3z" />
                                </svg>
                            </span>
                            <div>
                                <p class="text-xs font-medium text-white/60">Mitra Terpercaya dalam</p>
                                <p class="text-sm font-bold text-white">Manajemen Persediaan</p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="absolute bottom-8 left-1/2 z-10 -translate-x-1/2 text-white/50">
            <a href="#layanan" class="flex flex-col items-center gap-1 transition hover:text-white">
                <span class="text-xs font-semibold tracking-widest uppercase mb-2">Scroll</span>
                <svg class="size-5 animate-bounce" fill="none" stroke="currentColor" stroke-width="2"
                    viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M19 14l-7 7m0 0l-7-7m7 7V3" />
                </svg>
            </a>
        </div>
    </section>

    {{-- ════════════════ LAYANAN ════════════════ --}}
    <section id="layanan" class="relative overflow-hidden bg-neutral-900 py-20 lg:py-28">
        <div class="relative mx-auto max-w-7xl px-5 lg:px-8">
            <div class="text-center mb-14 reveal">
                <span
                    class="inline-flex items-center gap-2 text-xs font-bold uppercase tracking-[0.15em] text-red-400 mb-3">
                    <span class="h-px w-5 bg-red-400/50"></span>
                    Layanan Kami
                    <span class="h-px w-5 bg-red-400/50"></span>
                </span>
                <h2 class="text-3xl font-bold text-white sm:text-4xl lg:text-5xl">
                    Solusi Logistik <span class="text-red-400">Terpadu</span>
                </h2>
                <p class="mt-3 max-w-md mx-auto text-sm text-white/40">
                    Empat pilar layanan kami untuk mengoptimalkan rantai pasok koperasi desa.
                </p>
            </div>

            @php
            $services = [
            ['title' => 'Gudang Utama', 'desc' => 'Penyimpanan barang produksi & kebutuhan harian koperasi dengan
            pengelolaan modern.', 'img' => 'images/gudang-utama.png'],
            ['title' => 'Distribusi & Penyaluran', 'desc' => 'Optimalkan rantai pasok dari gudang ke anggota koperasi
            secara efisien.', 'img' => 'images/distribusi-penyaluran.png'],
            ['title' => 'Manajemen Persediaan', 'desc' => 'Pantau dan kelola seluruh inventaris secara real-time dari
            satu dashboard.', 'img' => 'images/manajemen-persediaan.png'],
            ['title' => 'Logistik & Pengiriman', 'desc' => 'Lacak pergerakan barang dari hulu ke hilir dengan akurasi
            tinggi.', 'img' => 'images/logistik-pengiriman.png'],
            ];
            @endphp

            <div class="grid grid-cols-1 gap-6 sm:grid-cols-2 lg:grid-cols-4">
                @foreach($services as $i => $s)
                <div class="svc-card reveal reveal-delay-{{ $i + 1 }}">
                    <div class="svc-card-inner">
                        <div class="svc-img-wrap">
                            <img src="{{ asset($s['img']) }}" alt="{{ $s['title'] }}" class="svc-img">
                            <span class="svc-num-badge">0{{ $i + 1 }}</span>
                        </div>
                        <div class="svc-content">
                            <span class="svc-tag">
                                <span class="svc-tag-dot"></span>
                                Layanan {{ $i + 1 }}
                            </span>
                            <h3 class="svc-title">{{ $s['title'] }}</h3>
                            <p class="svc-desc">{{ $s['desc'] }}</p>
                            {{-- <div class="svc-action">
                                <svg width="14" height="14" fill="none" stroke="currentColor" stroke-width="2"
                                    viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M17 8l4 4m0 0l-4 4m4-4H3" />
                                </svg>
                                </span>
                            </div> --}}
                        </div>
                    </div>
                    <div class="svc-corner"></div>
                </div>
                @endforeach
            </div>
        </div>
    </section>

    {{-- ════════════════ STATISTIK ════════════════ --}}
    <section id="tentang" class="relative overflow-hidden bg-neutral-100 py-20">
        <div class="relative mx-auto grid max-w-7xl grid-cols-1 items-center gap-12 px-5 lg:grid-cols-2 lg:px-8">
            <div class="reveal reveal-left">
                <span class="text-sm font-bold uppercase tracking-widest text-red-700">Tentang Kami</span>
                <h2 class="mt-3 font-body text-4xl font-bold leading-tight text-neutral-900 sm:text-5xl">
                    Logistik Cerdas untuk Koperasi Bergerak
                </h2>
                <p class="mt-4 text-base leading-relaxed text-neutral-500 max-w-md">
                    Kami hadir untuk menyederhanakan rantai pasok koperasi desa dengan teknologi modern yang mudah
                    digunakan.
                </p>
            </div>
            <div class="grid grid-cols-3 gap-6 reveal reveal-right">
                @php $stats = [['n'=>'1000+','l'=>'Koperasi Dilayani'],['n'=>'99%','l'=>'Akurasi
                Stok'],['n'=>'24/7','l'=>'Monitoring Aktif']]; @endphp
                @foreach($stats as $st)
                <div
                    class="text-center p-6 rounded-2xl bg-white shadow-lg shadow-neutral-200/50 hover:shadow-xl transition-shadow duration-300">
                    <p class="font-body text-4xl font-bold text-red-700 stat-num">{{ $st['n'] }}</p>
                    <p class="mt-2 text-xs font-semibold text-neutral-500">{{ $st['l'] }}</p>
                </div>
                @endforeach
            </div>
        </div>
    </section>

    {{-- ════════════════ FITUR ════════════════ --}}
    <section id="fitur" class="relative overflow-hidden bg-white py-24">
        <div class="relative mx-auto max-w-7xl px-5 lg:px-8">
            <div class="text-center mb-14 reveal">
                <span
                    class="inline-flex items-center gap-2 text-xs font-bold uppercase tracking-[0.15em] text-red-700 mb-3">
                    <span class="h-px w-5 bg-red-300"></span>
                    Fitur Unggulan
                    <span class="h-px w-5 bg-red-300"></span>
                </span>
                <h2 class="text-3xl font-bold text-neutral-900 sm:text-4xl lg:text-5xl">
                    Kemampuan <span class="text-red-700">Sistem</span> Kami
                </h2>
            </div>

            <div class="grid grid-cols-1 gap-6 sm:grid-cols-2 lg:grid-cols-4">
                @php
                $features = [
                ['num' => '01', 'title' => 'Konsultasi & Perencanaan', 'desc' => 'Kami memahami kebutuhan logistik Anda
                dan menyusun rencana yang memaksimalkan efisiensi gudang koperasi.'],
                ['num' => '02', 'title' => 'Inventaris & Gudang', 'desc' => 'Sistem pencatatan barang masuk-keluar yang
                rapi, akurat, dan mudah diakses oleh seluruh anggota.'],
                ['num' => '03', 'title' => 'Distribusi & Penyaluran', 'desc' => 'Pengelolaan distribusi produk dari
                gudang koperasi ke anggota dengan tepat waktu dan terdokumentasi.'],
                ['num' => '04', 'title' => 'Pelacakan & Komunikasi', 'desc' => 'Pantau pergerakan stok dan komunikasikan
                informasi persediaan secara real-time antar divisi.'],
                ];
                @endphp
                @foreach($features as $f)
                <div class="feat-card reveal reveal-delay-{{ $loop->iteration }}">
                    <div class="feat-icon">
                        <span class="font-body text-lg font-bold">{{ $f['num'] }}</span>
                    </div>
                    <h3 class="mt-6 font-body text-xl font-bold text-neutral-900">{{ $f['title'] }}</h3>
                    <p class="mt-3 text-sm leading-relaxed text-neutral-500">{{ $f['desc'] }}</p>
                </div>
                @endforeach
            </div>
        </div>
    </section>

    {{-- ════════════════ MENGAPA PILIH KAMI ════════════════ --}}
    <section class="relative overflow-hidden bg-neutral-900 py-28">
        <div class="absolute inset-0 bg-gradient-to-r from-neutral-950/95 via-neutral-950/85 to-neutral-950/70"></div>

        <div class="relative mx-auto grid max-w-7xl grid-cols-1 items-center gap-12 px-5 lg:grid-cols-2 lg:px-8">
            <div class="reveal reveal-left">
                <h2 class="font-body text-4xl font-bold leading-tight text-white sm:text-5xl">
                    Mengapa Memilih <span class="italic text-red-400">Kami?</span>
                </h2>
                <p class="mt-5 max-w-md text-base leading-relaxed text-white/60">
                    Kami menghadirkan solusi logistik digital terpercaya untuk koperasi desa — cepat, transparan, dan
                    sesuai kebutuhan Anda.
                </p>
                <div class="mt-8">
                    <a href="{{ auth()->check() ? route('dashboard') : route('register') }}"
                        class="group inline-flex items-center gap-2 rounded-full bg-red-700 px-7 py-3.5 text-sm font-bold text-white shadow-xl shadow-red-800/40 transition hover:bg-red-600 hover:scale-105">
                        {{ auth()->check() ? __('Buka Dashboard') : 'Mulai Sekarang' }} <span class="transition group-hover:translate-x-1">→</span>
                    </a>
                </div>
            </div>

            <div class="flex flex-col gap-4">
                @php
                $benefits = [
                ['icon' => '
                <path
                    d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z" />
                ', 'title' => 'Keamanan & Akurasi Terjamin', 'desc' => 'Setiap transaksi stok tercatat rapi, transparan,
                dan dapat diaudit kapan saja.'],
                ['icon' => '
                <path stroke-linecap="round" stroke-linejoin="round"
                    d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                ', 'title' => 'Biaya Fleksibel & Transparan', 'desc' => 'Sesuaikan anggaran dengan kebutuhan koperasi
                tanpa biaya tersembunyi.'],
                ['icon' => '
                <path stroke-linecap="round" stroke-linejoin="round" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                ', 'title' => 'Dukungan 24/7 & Pelacakan', 'desc' => 'Tim support kami siap membantu kapan saja,
                dilengkapi pelacakan stok secara real-time.'],
                ];
                @endphp
                @foreach($benefits as $i => $b)
                <div
                    class="flex items-start gap-4 rounded-2xl bg-white/10 p-5 backdrop-blur-sm ring-1 ring-white/10 transition-all duration-500 hover:bg-white/15 hover:translate-x-1 reveal reveal-right reveal-delay-{{ $i + 1 }}">
                    <span
                        class="mt-0.5 flex size-11 shrink-0 items-center justify-center rounded-xl bg-gradient-to-br from-red-600 to-red-700 text-white shadow-lg shadow-red-700/20">
                        <svg class="size-5" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24">{!!
                            $b['icon'] !!}</svg>
                    </span>
                    <div>
                        <h4 class="font-bold text-white">{{ $b['title'] }}</h4>
                        <p class="mt-1 text-sm leading-relaxed text-white/60">{{ $b['desc'] }}</p>
                    </div>
                </div>
                @endforeach
            </div>
        </div>
    </section>

    {{-- ════════════════ TESTIMONI ════════════════ --}}
    <section id="testimoni" class="relative overflow-hidden bg-white py-24">
        <div class="relative mx-auto max-w-7xl px-5 lg:px-8">
            <div class="grid grid-cols-1 items-center gap-16 lg:grid-cols-2">
                <div class="reveal reveal-left">
                    <span class="text-sm font-bold uppercase tracking-widest text-red-700">Testimoni</span>
                    <h2 class="mt-3 font-body text-4xl font-bold text-neutral-900 sm:text-5xl">
                        Dipercaya oleh<br>Koperasi di Seluruh Indonesia
                    </h2>
                    <div class="mt-8 rounded-2xl bg-neutral-50 p-6 ring-1 ring-neutral-100 relative">
                        <p class="text-base italic leading-relaxed text-neutral-600 pl-8">
                            "Gerakan koperasi ini tidak oleh kapitalis besar. Mereka menganggapnya ancaman karena
                            koperasi bisa jadi saingan. Tapi
                            ini adalah perjuangan!"
                        </p>
                        <div class="mt-5 flex items-center gap-4 pl-8">
                            <div class="size-12 overflow-hidden rounded-full ring-2 ring-red-100">
                                <img src="{{ asset('images/prabowo.png') }}" alt="Prabowo Subianto"
                                    class="size-full object-cover">
                            </div>
                            <div>
                                <p class="font-bold text-neutral-900">Prabowo Subianto</p>
                                <p class="text-sm text-neutral-500">Presiden RI — Koperasi Desa Merah Putih</p>
                            </div>
                        </div>
                    </div>
                    <div class="mt-6 flex gap-2">
                        <span class="size-3 rounded-full bg-red-700"></span>
                        <span class="size-3 rounded-full bg-neutral-300"></span>
                    </div>
                </div>

                <div class="flex justify-center reveal">
                    <div class="relative">
                        <div
                            class="absolute -inset-6 -z-10 rounded-[2rem] bg-gradient-to-br from-red-100 via-white to-neutral-100">
                        </div>
                        <div class="size-64 overflow-hidden rounded-full ring-8 ring-white shadow-2xl sm:size-80">
                            <img src="{{ asset('images/prabowo.png') }}" alt="Prabowo Subianto"
                                class="size-full object-cover">
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    {{-- ════════════════ CTA ════════════════ --}}
    <section class="relative overflow-hidden bg-gradient-to-br from-red-800 via-red-700 to-red-600 py-20">
        <div class="relative mx-auto max-w-4xl px-5 text-center sm:px-6 reveal">
            <h2 class="font-body text-3xl font-bold text-white sm:text-5xl">
                Siap Mengelola Gudang <span class="italic text-amber-200">Koperasi?</span>
            </h2>
            <p class="mt-4 text-lg text-red-100">
                Bergabunglah dengan koperasi desa yang telah beralih ke sistem manajemen persediaan modern dan
                terpercaya.
            </p>
            <div class="mt-8 flex flex-col items-center justify-center gap-4 sm:flex-row">
                @auth
                <a href="{{ route('dashboard') }}"
                    class="group w-full rounded-full bg-white px-8 py-4 text-base font-bold text-red-700 shadow-xl transition hover:bg-amber-50 hover:scale-105 sm:w-auto">
                    {{ __('Buka Dashboard') }} <span class="ml-2 inline-block transition group-hover:translate-x-1">→</span>
                </a>
                <form method="POST" action="{{ route('logout') }}" class="w-full sm:w-auto">
                    @csrf
                    <button type="submit"
                        class="w-full rounded-full border-2 border-white/70 px-8 py-4 text-base font-bold text-white transition hover:bg-white hover:text-red-700 sm:w-auto">
                        {{ __('Log out') }}
                    </button>
                </form>
                @else
                <a href="{{ route('register') }}"
                    class="group w-full rounded-full bg-white px-8 py-4 text-base font-bold text-red-700 shadow-xl transition hover:bg-amber-50 hover:scale-105 sm:w-auto">
                    Daftar Sekarang <span class="ml-2 inline-block transition group-hover:translate-x-1">→</span>
                </a>
                <a href="{{ route('login') }}"
                    class="w-full rounded-full border-2 border-white/70 px-8 py-4 text-base font-bold text-white transition hover:bg-white hover:text-red-700 sm:w-auto">
                    Masuk Akun
                </a>
                @endauth
            </div>
        </div>
    </section>

    {{-- ════════════════ FAQ ════════════════ --}}
    <section class="relative overflow-hidden bg-neutral-50 py-24">
        <div class="relative mx-auto grid max-w-7xl grid-cols-1 gap-16 px-5 lg:grid-cols-2 lg:px-8">
            <div class="reveal reveal-left">
                <span class="text-sm font-bold uppercase tracking-widest text-red-700">FAQ</span>
                <h2 class="mt-3 font-body text-4xl font-bold text-neutral-900 sm:text-5xl">
                    Pertanyaan Umum tentang Sistem Logistik Koperasi
                </h2>
            </div>

            <div class="space-y-4">
                @php
                $faqs = [
                ['q' => 'Bagaimana cara memulai menggunakan sistem ini?', 'a' => 'Cukup daftar akun baru, lengkapi data
                koperasi Anda, dan mulai mencatat persediaan gudang hari ini.'],
                ['q' => 'Apakah data saya aman di platform ini?', 'a' => 'Ya. Kami menggunakan enkripsi tingkat
                enterprise dan backup otomatis untuk menjaga keamanan seluruh data Anda.'],
                ['q' => 'Berapa biaya berlangganan sistem ini?', 'a' => 'Kami menawarkan paket fleksibel yang
                menyesuaikan dengan ukuran dan kebutuhan koperasi Anda.'],
                ['q' => 'Bisakah saya mengakses sistem dari perangkat mobile?', 'a' => 'Tentu. Platform kami responsif
                dan dapat diakses dari smartphone, tablet, maupun komputer.'],
                ];
                @endphp
                @foreach($faqs as $i => $faq)
                <details
                    class="group rounded-xl bg-white p-5 ring-1 ring-neutral-200 transition hover:shadow-md reveal reveal-delay-{{ $i + 1 }}">
                    <summary class="flex cursor-pointer items-center gap-4 text-sm font-bold text-neutral-900">
                        <span
                            class="faq-num flex size-8 shrink-0 items-center justify-center rounded-full bg-neutral-200 text-xs font-bold text-neutral-600 transition">{{
                            $i + 1 }}</span>
                        <span class="flex-1">{{ $faq['q'] }}</span>
                        <svg class="size-4 shrink-0 text-neutral-400 transition group-open:rotate-180" fill="none"
                            stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M19 9l-7 7-7-7" />
                        </svg>
                    </summary>
                    <p class="mt-3 ml-12 text-sm leading-relaxed text-neutral-600">{{ $faq['a'] }}</p>
                </details>
                @endforeach
            </div>
        </div>
    </section>

    {{-- ════════════════ FOOTER ════════════════ --}}
    <footer id="kontak" class="relative overflow-hidden bg-neutral-950 text-neutral-400">
        <div class="relative mx-auto max-w-7xl px-5 py-16 lg:px-8">
            <div class="grid gap-10 md:grid-cols-4">
                <div class="md:col-span-2">
                    <div class="flex items-center gap-2.5">
                        <img src="{{ asset('images/logo-kdmp.png') }}" alt="KDMP Logo" class="h-10 w-auto rounded-lg">
                        <span class="font-body text-xl font-bold text-white">Koperasi Desa Merah Putih</span>
                    </div>
                    <p class="mt-4 max-w-sm text-sm leading-relaxed text-neutral-500">
                        Sistem manajemen inventaris gudang terpadu untuk koperasi desa — transparan, akurat, dan mudah
                        digunakan.
                    </p>
                </div>
                <div>
                    <h4 class="text-sm font-bold uppercase tracking-wider text-white">Navigasi</h4>
                    <ul class="mt-4 space-y-2.5 text-sm">
                        <li><a href="#beranda"
                                class="transition hover:text-red-400 hover:translate-x-1 inline-block">Beranda</a></li>
                        <li><a href="#layanan"
                                class="transition hover:text-red-400 hover:translate-x-1 inline-block">Layanan</a></li>
                        <li><a href="#tentang"
                                class="transition hover:text-red-400 hover:translate-x-1 inline-block">Tentang</a></li>
                        <li><a href="#fitur"
                                class="transition hover:text-red-400 hover:translate-x-1 inline-block">Fitur</a></li>
                    </ul>
                </div>
                <div>
                    <h4 class="text-sm font-bold uppercase tracking-wider text-white">Akun</h4>
                    <ul class="mt-4 space-y-2.5 text-sm">
                        @auth
                        <li><a href="{{ route('dashboard') }}"
                                class="transition hover:text-red-400 hover:translate-x-1 inline-block">{{ __('Buka Dashboard') }}</a></li>
                        <li>
                            <form method="POST" action="{{ route('logout') }}">
                                @csrf
                                <button type="submit"
                                    class="transition hover:text-red-400 hover:translate-x-1 inline-block">{{ __('Log out') }}</button>
                            </form>
                        </li>
                        @else
                        <li><a href="{{ route('login') }}"
                                class="transition hover:text-red-400 hover:translate-x-1 inline-block">Masuk</a></li>
                        @if (Route::has('register'))
                        <li><a href="{{ route('register') }}"
                                class="transition hover:text-red-400 hover:translate-x-1 inline-block">Daftar</a></li>
                        @endif
                        @endauth
                    </ul>
                </div>
            </div>
            <div
                class="mt-12 flex flex-col items-center justify-between gap-4 border-t border-neutral-800 pt-6 text-xs text-neutral-600 sm:flex-row">
                <p>&copy; {{ date('Y') }} {{ config('app.name', 'Koperasi Desa Merah Putih') }}. Seluruh hak cipta
                    dilindungi.</p>
                <p>Dibangun dengan <span class="text-red-600">&hearts;</span> untuk Koperasi Indonesia.</p>
            </div>
        </div>
    </footer>

</body>

</html>
