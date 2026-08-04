# Warehouse Koperasi SaaS

Warehouse Koperasi SaaS adalah aplikasi pengelolaan gudang berbasis web untuk mendigitalkan stok, approval, request pembelian, pengambilan barang oleh koperasi, penerimaan supplier, retur, notifikasi, dan—pada fase terakhir—prediksi pembelian melalui API layanan Python.

Dokumen di repositori ini merupakan spesifikasi implementasi Laravel yang diturunkan dari `PRD_Sistem_Pengelolaan_Gudang.pdf`, dengan perluasan penting untuk arsitektur SaaS multi-warehouse, manajemen akun bertingkat, Google Sign-In, MFA, ACL sampai tingkat model, dan pemisahan layanan machine learning.

## Dokumen Utama

| Dokumen | Fungsi |
|---|---|
| `PRD.md` | Kebutuhan produk lengkap, alur bisnis, kebutuhan fungsional/non-fungsional, model-view-controller, acceptance criteria, dan roadmap. |
| `ARCHITECTURE.md` | Keputusan arsitektur, modul, struktur kode, tenancy, transaksi, queue, notifikasi real-time, integrasi Python, deployment, dan observability. |
| `SECURITY-RULES.md` | Aturan keamanan wajib dari autentikasi sampai model-level authorization, tenant isolation, audit, upload, API, CI/CD, backup, dan incident response. |
| `UI-RULES.md` | Aturan antarmuka, responsivitas, aksesibilitas, pola layar, komponen, status, formulir, pemindaian barcode, dan UX per role. |
| `BATASAN.md` | Ruang lingkup, di luar lingkup, batas fase, asumsi, keputusan yang tidak boleh diubah diam-diam, serta daftar pertanyaan terbuka. |
| `AGENTS.md` | Instruksi wajib untuk developer dan AI coding agent. |
| `.agent/README.md` | Entry point aturan agent di repositori. |
| `.agent/WORKFLOW.md` | Workflow implementasi, TDD, review, dan quality gate. |
| `.ai/guidelines/warehouse-project.md` | Custom guideline yang dapat digabungkan oleh Laravel Boost. |

## Target Teknologi

Baseline target pada saat dokumen ini dibuat:

- PHP `8.3–8.5`.
- Laravel `13.x`.
- Laravel Livewire starter kit: Livewire 4, Tailwind CSS, dan Flux UI.
- PostgreSQL sebagai basis data relasional utama.
- Redis untuk cache, rate limiter, queue, lock teknis, dan broadcast state.
- Laravel Fortify dari starter kit untuk autentikasi, MFA, passkey/TOTP, email verification, dan recovery flow.
- Laravel Socialite untuk Google Sign-In.
- `spatie/laravel-permission` untuk penyimpanan role/permission; seluruh keputusan akses tetap diselesaikan melalui Laravel Policies/Gates dan pemeriksaan tenant.
- Laravel Reverb/Echo untuk pembaruan real-time pada web.
- Firebase Cloud Messaging atau provider push setara untuk push notification Kepala Gudang.
- Object storage kompatibel S3 untuk foto QC dan retur.
- Service Python terpisah untuk prediksi pembelian; fitur ini diimplementasikan paling akhir.

Versi final harus dikunci melalui `composer.lock` dan lockfile frontend. Jangan mengandalkan versi global mesin developer.

## Bootstrap Proyek

### 1. Prasyarat

Siapkan PHP, Composer, Node.js versi LTS aktif, npm, PostgreSQL, dan Redis. Docker/Sail dapat dipakai agar lingkungan konsisten.

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

## Database, Seeder, dan Dummy Data

```bash
php artisan migrate
php artisan db:seed
```

Seeder development harus menyediakan:

- satu akun `super_admin` platform;
- beberapa warehouse/tenant;
- satu `app_admin` per warehouse;
- user Kepala Gudang, Staff Admin, Purchasing, dan Koperasi per warehouse;
- katalog barang, barcode, stok minimum, stok positif/negatif, supplier, transaksi stok, request, approval, PO, penerimaan/QC, retur, inbox, dan audit log;
- skenario edge case: duplikasi request, pembatalan, backorder, retur disetujui/ditolak, foto hilang, barang tanpa histori, dan user lintas tenant.

Seeder production hanya boleh membuat role, permission, parameter sistem, dan bootstrap `super_admin` melalui secret/command aman. Seeder production tidak boleh membuat password default yang diketahui umum.

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

## Prinsip Implementasi

1. Seluruh data operasional wajib memiliki `warehouse_id`, kecuali data platform yang secara eksplisit global.
2. Menyembunyikan tombol di UI bukan authorization. Setiap route, controller/action, policy, query, job, broadcast channel, export, dan file download harus memverifikasi akses.
3. Controller tipis; validasi di Form Request; authorization di Policy/Gate; aturan bisnis di Action/Service; query kompleks di Query Object/Repository terarah.
4. Transisi status hanya melalui service/action yang tervalidasi dan ditulis dalam transaksi database.
5. Semua approval, penolakan, pembatalan, perubahan role/permission, login berisiko, impersonation, export, dan akses lintas tenant dicatat di audit log.
6. Implementasi ML tidak boleh dimulai sebelum seluruh fase inti stabil, dites, dan diterima.
7. Tidak ada fitur dianggap selesai tanpa test sukses, authorization test, tenant isolation test, audit evidence, error handling, dan dokumentasi.

## Branch dan Pull Request

Gunakan branch kecil berbasis ticket, misalnya:

```text
feat/auth-google-mfa
feat/tenant-user-management
feat/stock-ledger
fix/return-tenant-leak
```

PR harus menyertakan:

- referensi requirement/ticket;
- ringkasan perubahan;
- migration/data impact;
- screenshot atau rekaman untuk perubahan UI;
- test yang ditambahkan;
- security dan tenant-isolation impact;
- rollback plan bila mengubah schema atau workflow;
- bukti quality gate.

## Urutan Implementasi

1. Foundation, CI, tenancy, bootstrap `super_admin`, role/permission, audit.
2. Google Sign-In, invitation, MFA, session/device management.
3. User management dan warehouse administration.
4. Master data barang, supplier, lokasi, barcode, stok minimum.
5. Stock ledger, penerimaan, QC, dan real-time stock.
6. Request pengambilan dan approval pengeluaran.
7. Request pembelian, duplicate warning, batching/grouping, PO, penerimaan.
8. Retur, fault attribution, replacement, dan pickup ulang.
9. Inbox, push notification, dashboard, reporting, hardening, observability.
10. API service Python untuk prediksi pembelian, sebagai fitur terakhir.

Baca `PRD.md`, `SECURITY-RULES.md`, dan `ARCHITECTURE.md` sebelum menulis kode.

## License

This repository is proprietary software. Use, modification, access, and
distribution are restricted to parties authorised by the SaaS owner. See
[`LICENSE`](LICENSE) for the complete terms.
