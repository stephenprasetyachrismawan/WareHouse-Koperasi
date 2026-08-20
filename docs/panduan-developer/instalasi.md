---
sidebar_position: 2
title: Instalasi
---

# Instalasi

Panduan lengkap menjalankan WareHouse Koperasi secara lokal, mulai dari fork/clone sampai aplikasi bisa dibuka di browser. Ada dua jalur — pilih salah satu:

- **[Opsi A — Tanpa Docker (native)](#opsi-a--tanpa-docker-native)**: PHP, Composer, Node, dan database di-install langsung di mesin Anda.
- **[Opsi B — Dengan Docker](#opsi-b--dengan-docker)**: semua dependency berjalan dalam container, mesin Anda hanya perlu Docker. Topologinya sama persis dengan yang jalan di VPS development (lihat [Provisioning Server](./provisioning-server)).

## 0. Fork atau clone

Kalau Anda kontributor eksternal, **fork dulu** repo ini ke akun GitHub Anda, lalu clone fork Anda:

```bash
git clone https://github.com/<username-anda>/WareHouse-Koperasi.git
cd WareHouse-Koperasi
```

Kalau Anda punya akses langsung ke repo (kolaborator), clone langsung:

```bash
git clone https://github.com/stephenprasetyachrismawan/WareHouse-Koperasi.git
cd WareHouse-Koperasi
```

## Opsi A — Tanpa Docker (native)

### A.1 Prasyarat

Install di mesin Anda:

- **PHP 8.3–8.5** (proyek pakai fitur PHP 8.4) beserta ekstensi umum Laravel (`pdo_pgsql` atau `pdo_sqlite`, `mbstring`, `xml`, `curl`, `zip`, `gd`/`imagick` untuk evidence foto)
- **Composer 2**
- **Node.js versi LTS aktif** + npm
- **PostgreSQL** dan **Redis** (opsional untuk dev cepat — SQLite + cache/queue/session database-backed juga bisa, lihat langkah A.3)

```bash
php -v
composer -V
node -v
npm -v
```

### A.2 Install dependency

```bash
composer install
npm install
```

### A.3 Konfigurasi environment

```bash
cp .env.example .env
php artisan key:generate
```

Buka `.env`, minimal isi:

```dotenv
APP_URL=http://localhost:8000

# Cara tercepat untuk dev lokal tanpa install PostgreSQL/Redis: pakai SQLite
# + cache/queue/session database-backed (persis topologi yang jalan di VPS
# development — lihat Deployment di CI/CD).
DB_CONNECTION=sqlite
DB_DATABASE=/absolute/path/ke/proyek/database/database.sqlite
CACHE_STORE=database
QUEUE_CONNECTION=database
SESSION_DRIVER=database

# Kalau mau lebih dekat dengan arsitektur target production (lihat
# Ringkasan Arsitektur), pakai PostgreSQL + Redis lokal alih-alih baris di
# atas:
# DB_CONNECTION=pgsql
# DB_HOST=127.0.0.1
# DB_PORT=5432
# DB_DATABASE=warehouse_koperasi
# DB_USERNAME=postgres
# DB_PASSWORD=
# CACHE_STORE=redis
# QUEUE_CONNECTION=redis
# SESSION_DRIVER=redis

GOOGLE_CLIENT_ID=
GOOGLE_CLIENT_SECRET=
GOOGLE_REDIRECT_URI=${APP_URL}/auth/google/callback
```

Kalau pakai SQLite, buat dulu file databasenya:

```bash
touch database/database.sqlite
```

`GOOGLE_CLIENT_ID`/`GOOGLE_CLIENT_SECRET` opsional untuk dev murni lokal — login Google tidak akan berfungsi tanpanya, tapi Anda tetap bisa login lewat akun demo hasil seeder (langkah A.5).

### A.4 Laravel Boost — wajib

```bash
composer require laravel/boost --dev
php artisan boost:install
```

Setelah dependency berubah:

```bash
php artisan boost:update
```

### A.5 Migrate & seed

```bash
php artisan migrate
php artisan db:seed
```

Lihat `PRD.md` bagian "Dummy Data Generator" untuk akun demo apa saja yang dibuat seeder.

### A.6 Jalankan

```bash
npm run build
composer run dev
```

`composer run dev` menjalankan server Laravel, queue worker, Reverb (realtime), dan Vite dev server sekaligus. Buka **http://localhost:8000**.

## Opsi B — Dengan Docker

Jalur ini pakai `deploy/compose.yaml` yang sama persis dengan yang jalan di VPS (5 service: `web`, `app`, `queue`, `scheduler`, `reverb`) — bedanya, image-nya **dibuild lokal**, bukan ditarik dari GHCR.

### B.1 Prasyarat

- **Docker** + **Docker Compose v2** (`docker compose version` harus jalan)

### B.2 Siapkan environment

```bash
cp .env.docker.example .env
```

Buka `.env`, isi minimal `APP_KEY` (kosongkan dulu, diisi di langkah B.4), `APP_ENV=local`, `APP_DEBUG=true`, `APP_URL=http://localhost:8000`, dan `GOOGLE_CLIENT_ID`/`GOOGLE_CLIENT_SECRET` kalau perlu login Google. Nilai lain (path SQLite, `CACHE_STORE=database`, dst.) sudah sesuai untuk dev lokal.

### B.3 Build image lokal

`deploy/compose.yaml` sendiri tidak punya `build:` context (di VPS, image selalu ditarik jadi, tidak pernah dibuild di host). Untuk lokal, build dulu kedua target langsung dari `Dockerfile` di root repo:

```bash
docker build --target runtime -t warehouse-koperasi-app \
  --build-arg VITE_REVERB_APP_KEY=local-dev \
  --build-arg VITE_REVERB_HOST=localhost \
  --build-arg VITE_REVERB_PORT=8080 \
  --build-arg VITE_REVERB_SCHEME=http .

docker build --target web -t warehouse-koperasi-web \
  --build-arg VITE_REVERB_APP_KEY=local-dev \
  --build-arg VITE_REVERB_HOST=localhost \
  --build-arg VITE_REVERB_PORT=8080 \
  --build-arg VITE_REVERB_SCHEME=http .
```

### B.4 Generate APP_KEY, lalu jalankan

```bash
APP_IMAGE=warehouse-koperasi-app WEB_IMAGE=warehouse-koperasi-web \
  docker compose --project-directory deploy run --rm app php artisan key:generate --show
```

Salin hasilnya ke `APP_KEY=` di `.env`, lalu:

```bash
APP_IMAGE=warehouse-koperasi-app WEB_IMAGE=warehouse-koperasi-web \
  docker compose --project-directory deploy up -d
```

### B.5 Migrate & seed

```bash
APP_IMAGE=warehouse-koperasi-app WEB_IMAGE=warehouse-koperasi-web \
  docker compose --project-directory deploy exec app php artisan migrate --seed
```

Buka **http://localhost:8000**.

:::note
`deploy/compose.yaml` mem-publish port `8000` (web) dan `8080` (Reverb) hanya ke `127.0.0.1`. Untuk akses dari mesin lain di jaringan lokal, ubah binding port di compose file (jangan commit perubahan itu).
:::

:::warning `deploy-development.sh` bukan untuk lokal
`deploy/deploy-development.sh` dan `deploy/rollback-development.sh` adalah skrip yang jalan **di VPS lewat GitHub Actions**, menarik image immutable dari GHCR — lihat halaman [CI/CD](./ci-cd). Untuk dev lokal, cukup langkah B.1–B.5 di atas.
:::

## Setelah aplikasi jalan

- Testing: [Menjalankan Aplikasi & Testing](./menjalankan-aplikasi)
- Struktur kode: [Ringkasan Arsitektur](./arsitektur)
- Kontribusi (branch/PR/commit): [Kontribusi](./kontribusi)
- Kerja bareng AI coding agent: [Agent Prompt](./agent-prompt)
- Cara VPS development disiapkan dari nol: [Provisioning Server](./provisioning-server)

Rujukan teknis lebih lengkap juga ada di [GitHub Wiki](https://github.com/stephenprasetyachrismawan/WareHouse-Koperasi/wiki) repo ini.
