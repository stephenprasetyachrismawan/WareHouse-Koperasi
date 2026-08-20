---
sidebar_position: 2
title: Instalasi
---

# Instalasi

## Prasyarat

Siapkan PHP `8.3–8.5`, Composer, Node.js versi LTS aktif, npm, PostgreSQL, dan Redis. Docker/Sail bisa dipakai agar lingkungan konsisten — lihat [CI/CD](./ci-cd) untuk bagaimana Docker dipakai di deployment sungguhan.

## Clone dan install

```bash
git clone https://github.com/stephenprasetyachrismawan/WareHouse-Koperasi.git
cd WareHouse-Koperasi
composer install
npm install
```

```bash
npm run build
composer run dev
```

`composer run dev` menjalankan server Laravel, queue worker, Reverb, dan Vite dev server sekaligus lewat satu perintah — lihat halaman [Menjalankan Aplikasi](./menjalankan-aplikasi) untuk detail.

## Laravel Boost — wajib

```bash
composer require laravel/boost --dev
php artisan boost:install
```

Setelah dependency berubah, perbarui resource agent:

```bash
php artisan boost:update
```

## Konfigurasi lingkungan

Salin `.env.example` menjadi `.env`, lalu isi minimal:

```dotenv
APP_NAME="Warehouse Koperasi"
APP_ENV=local
APP_DEBUG=true
APP_URL=http://localhost:8000

DB_CONNECTION=pgsql
DB_HOST=127.0.0.1
DB_PORT=5432
DB_DATABASE=warehouse_koperasi
DB_USERNAME=postgres
DB_PASSWORD=

CACHE_STORE=redis
QUEUE_CONNECTION=redis
SESSION_DRIVER=redis
BROADCAST_CONNECTION=reverb

GOOGLE_CLIENT_ID=
GOOGLE_CLIENT_SECRET=
GOOGLE_REDIRECT_URI=${APP_URL}/auth/google/callback
```

Secret production tidak boleh berada di `.env` yang tersimpan di Git — gunakan secret manager platform deployment.

:::tip Menjalankan lewat Docker?
Untuk lingkungan development berbasis Docker (SQLite, image immutable dari GHCR, deployment via GitHub Actions), konfigurasinya berbeda — lihat `.env.docker.example` dan halaman [CI/CD](./ci-cd).
:::

## Database, seeder, dan dummy data

```bash
php artisan migrate
php artisan db:seed
```

Spesifikasi lengkap factory, state wajib, dan skenario demo ada di `PRD.md` bagian "Dummy Data Generator".
