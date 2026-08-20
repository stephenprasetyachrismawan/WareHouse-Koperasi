---
sidebar_position: 3
title: Menjalankan Aplikasi & Testing
---

# Menjalankan Aplikasi & Testing

## Menjalankan secara lokal

```bash
composer run dev
```

Perintah ini menjalankan beberapa proses sekaligus (server Laravel, queue worker, Reverb untuk realtime, dan Vite dev server) supaya Anda tidak perlu membuka banyak terminal terpisah.

## Menjalankan test

Proyek ini pakai [Pest](https://pestphp.com/) di atas PHPUnit. Sebagian besar test adalah Feature test di `tests/Feature/`.

```bash
php artisan test --compact
```

Untuk menjalankan test tertentu saja (lebih cepat saat sedang debugging satu fitur):

```bash
php artisan test --compact --filter=NamaTest
```

## Perintah kualitas kode

Sebelum membuat pull request, jalankan gate kualitas berikut secara lokal:

```bash
composer validate --strict
php artisan test
vendor/bin/pint --test
npm run build
npm run lint
```

Kalau Pint menemukan masalah format, perbaiki otomatis dengan:

```bash
vendor/bin/pint
```

:::warning Lolos lokal saja tidak cukup
Gate lengkap yang benar-benar dipakai untuk memutuskan sebuah PR boleh di-merge berjalan di GitHub Actions (PHPStan level 7, security regression suite, dependency audit, Gitleaks, kompatibilitas PostgreSQL/Redis, Docker image build) — bukan hasil test di komputer Anda. Detail lengkapnya ada di halaman [CI/CD](./ci-cd).
:::

## Konvensi TDD

Proyek ini mengikuti alur **Red → Green → Refactor**: tulis test yang gagal lebih dulu (Red), implementasikan kode minimal supaya test itu lolos (Green), baru rapikan kodenya tanpa mengubah perilaku (Refactor). Konvensi lengkapnya ada di `AGENTS.md` dan `PRD.md`.
