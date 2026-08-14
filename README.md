# Warehouse Koperasi SaaS — Panduan Instalasi

Dokumen ini adalah panduan instalasi dan menjalankan **Warehouse Koperasi SaaS** secara lokal untuk pengembangan. Untuk kebutuhan produk, arsitektur, aturan keamanan, dan aturan UI, baca dokumen terkait di bawah — bukan file ini.

## Dokumen Utama

| Dokumen | Fungsi |
|---|---|
| `PRD.md` | Kebutuhan produk lengkap, alur bisnis, kebutuhan fungsional/non-fungsional, model-view-controller, baseline teknologi, prinsip implementasi, konvensi branch/PR, acceptance criteria, dan roadmap. |
| `ARCHITECTURE.md` | Keputusan arsitektur, modul, struktur kode, tenancy, transaksi, queue, notifikasi real-time, integrasi Python, deployment, dan observability. |
| `SECURITY-RULES.md` | Aturan keamanan wajib dari autentikasi sampai model-level authorization, tenant isolation, audit, upload, API, CI/CD, backup, dan incident response. |
| `UI-RULES.md` | Aturan antarmuka, responsivitas, aksesibilitas, pola layar, komponen, status, formulir, pemindaian barcode, dan UX per role. |
| `BATASAN.md` | Ruang lingkup, di luar lingkup, batas fase, asumsi, keputusan yang tidak boleh diubah diam-diam, serta daftar pertanyaan terbuka. |
| `AGENTS.md` | Instruksi wajib untuk developer dan AI coding agent. |
| `.agent/README.md` | Entry point aturan agent di repositori. |
| `.agent/WORKFLOW.md` | Workflow implementasi, TDD, review, dan quality gate. |
| `.ai/guidelines/warehouse-project.md` | Custom guideline yang dapat digabungkan oleh Laravel Boost. |

## Bootstrap Proyek

### 1. Prasyarat

Siapkan PHP `8.3–8.5`, Composer, Node.js versi LTS aktif, npm, PostgreSQL, dan Redis. Docker/Sail dapat dipakai agar lingkungan konsisten.

Daftar lengkap dependency baseline (Laravel, Livewire, Fortify, Socialite, Reverb, dll.) beserta rasionalnya ada di `PRD.md` bagian "Baseline Teknologi". Versi final harus dikunci melalui `composer.lock` dan lockfile frontend — jangan mengandalkan versi global mesin developer.

### 2. Buat aplikasi Laravel

```bash
composer global require laravel/installer
laravel new warehouse-koperasi
cd warehouse-koperasi
```

Saat installer meminta starter kit, pilih **Livewire**. Pilih autentikasi bawaan Laravel/Fortify, bukan public self-registration untuk production. Registrasi publik harus dinonaktifkan setelah scaffolding karena seluruh user dibuat melalui invitation oleh `super_admin` atau `app_admin` sesuai scope.

```bash
npm install
npm run build
composer run dev
```

### 3. Paket aplikasi

```bash
composer require laravel/socialite
composer require spatie/laravel-permission
```

Tambahkan paket lain hanya setelah ada kebutuhan di PRD, pemeriksaan kompatibilitas, security review, dan persetujuan maintainer.

### 4. Laravel Boost — wajib

```bash
composer require laravel/boost --dev
php artisan boost:install
```

Setelah dependency berubah, perbarui resource agent:

```bash
php artisan boost:update
```

Custom guideline proyek berada pada `.ai/guidelines/warehouse-project.md`. Setelah file guideline berubah, jalankan kembali instalasi/update Boost sesuai dokumentasi paket.

### 5. Matt Pocock Skills — wajib untuk developer/agent

Untuk Codex dan agent lain yang didukung:

```bash
npx skills@latest add mattpocock/skills
```

Pada installer, pilih skill yang diperlukan dan pastikan `setup-matt-pocock-skills` ikut dipasang. Setelah itu jalankan perintah agent:

```text
/setup-matt-pocock-skills
```

Jangan memasang dua mekanisme yang menghasilkan duplikasi skill pada agent yang sama. File hasil installer boleh berbeda menurut agent; perubahan yang relevan terhadap repositori harus direview sebelum commit.

## Konfigurasi Lingkungan

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

FILESYSTEM_DISK=s3
AWS_ACCESS_KEY_ID=
AWS_SECRET_ACCESS_KEY=
AWS_DEFAULT_REGION=
AWS_BUCKET=
AWS_USE_PATH_STYLE_ENDPOINT=false

ML_SERVICE_ENABLED=false
ML_SERVICE_BASE_URL=
ML_SERVICE_KEY_ID=
ML_SERVICE_SECRET=
ML_SERVICE_TIMEOUT_SECONDS=5
```

Secret production tidak boleh berada di `.env` yang tersimpan di Git. Gunakan secret manager platform deployment.

### Kontrak production dan smoke check

Production harus menggunakan managed PostgreSQL dengan TLS, Redis terautentikasi
melalui TLS, private object storage, queue/cache/session berbasis Redis, asset
frontend hasil build, dan Reverb melalui hostname WSS publik. Jangan gunakan
`composer run dev` sebagai supervisor production.

Validator aman (tidak menulis data) dan probe infrastruktur sintetis tersedia:

```bash
php artisan ops:validate-production
php artisan ops:verify-production-infrastructure
```

Probe object storage hanya boleh dijalankan pada environment terisolasi dengan
konfirmasi eksplisit:

```bash
php artisan ops:verify-production-infrastructure --storage-smoke --confirm-storage-smoke
```

Perintah tersebut menulis lalu menghapus satu object sintetis pada disk `private`;
jangan jalankan terhadap data bisnis production tanpa synthetic tenant/prosedur
operasional yang disetujui. Status provisioning provider dan bukti executable
Phase 6.4E dicatat di
[`docs/verification/phase-6-4e-production-infrastructure.md`](docs/verification/phase-6-4e-production-infrastructure.md).

## Database, Seeder, dan Dummy Data

```bash
php artisan migrate
php artisan db:seed
```

Spesifikasi lengkap factory, state wajib, skenario demo, dan batasan seeder production ada di `PRD.md` bagian "Dummy Data Generator".

## Perintah Kualitas

Nama script dapat disesuaikan pada `composer.json`, tetapi quality gate minimal adalah:

```bash
composer validate --strict
php artisan test
vendor/bin/pint --test
npm run build
npm run lint
```

Tambahkan PHPStan/Larastan, test coverage, browser test, dependency audit, dan migration smoke test dalam CI sebelum release production.

Prinsip implementasi inti, konvensi branch/pull request, dan urutan roadmap fase ada di `PRD.md`. Baca `PRD.md`, `SECURITY-RULES.md`, dan `ARCHITECTURE.md` sebelum menulis kode.

## License

This repository is proprietary software. Use, modification, access, and
distribution are restricted to parties authorised by the SaaS owner. See
[`LICENSE`](LICENSE) for the complete terms.
