# Warehouse Koperasi

Aplikasi web untuk mengelola stok barang, pengambilan, pengadaan, dan retur di gudang koperasi desa — multi-tenant, satu platform untuk lebih dari satu gudang.

## Anggota Kelompok

| Nama | NIM | GitHub |
|---|---|---|
| Stephen Prasetya Chrismawan | 25/563032/PPA/07093 | [@stephenprasetyachrismawan](https://github.com/stephenprasetyachrismawan) |
| Wiladahtul Awaliah | 25/569610/PPA/07174 | [@wiladahtulawaliah2002-maker](https://github.com/wiladahtulawaliah2002-maker) |
| Muhammad Zhafir Zaydan | 25/563967/PPA/07119 | [@zhafirzidann](https://github.com/zhafirzidann) |

---

## Untuk Pengguna

### Apa itu Warehouse Koperasi?

**Warehouse Koperasi** membantu koperasi desa mengelola gudang tanpa proses kertas dan komunikasi terpisah-pisah — satu alur kerja terpusat dari request sampai stok dan retur, dengan approval dan bukti yang jelas.

| Fitur | Fungsi |
|---|---|
| 📦 Inventaris & Stok | Kelola daftar barang, cek saldo stok, lihat riwayat pergerakan |
| 🛒 Pengambilan (Pickup) | Koperasi anggota mengajukan permintaan barang, disetujui Kepala Gudang |
| 📄 Pengadaan (Procurement) | Purchase request, grouping, PO, penerimaan & QC barang dari supplier |
| ↩️ Retur Barang | Ajukan retur, verifikasi, approval, dan penggantian barang |
| 📊 Laporan | Lihat dan export laporan stok, mutasi, pengadaan, pickup, dan retur |
| 🔔 Notifikasi | Notifikasi real-time untuk approval dan perubahan status |

Setiap pengguna tergabung dalam satu atau lebih **warehouse** (tenant) dengan role tertentu — data antar warehouse terpisah sepenuhnya.

### Siapa yang Menggunakan

| Role | Untuk Siapa |
|---|---|
| **Platform Owner** (`super_admin`) | Mengelola lifecycle tenant dan menunjuk admin warehouse, tanpa jadi operator gudang sehari-hari |
| **Warehouse Administrator** (`app_admin`) | Mengelola user, role, dan akses warehouse-nya sendiri |
| **Kepala Gudang** | Memberi keputusan approval secara real-time, termasuk dari perangkat mobile |
| **Staff Admin Gudang** | Kerja di lantai gudang lewat mobile/tablet — scan barcode, update stok, QC, retur |
| **Purchasing** | Menghubungkan gudang dan supplier — inbox request, grouping, PO, penerimaan |
| **Koperasi** | Anggota koperasi yang mengajukan request barang dan retur lewat form sederhana |

### Cara Masuk

Masuk menggunakan **Google Sign-In** — tidak ada pendaftaran mandiri; akun dibuat lewat undangan oleh admin warehouse Anda.

### Dokumentasi Lengkap

Panduan penggunaan lengkap (login, dashboard, role & hak akses, inventaris, pickup, pengadaan, retur, laporan, notifikasi, pengaturan akun) tersedia langsung di dalam aplikasi pada halaman **[Dokumentasi](https://wh.stevewithcode.net/documentation)**.

---

## Untuk Development

Bagian ini untuk developer yang ingin menjalankan, memodifikasi, atau berkontribusi pada aplikasi ini secara lokal.

### Dokumen Utama

| Dokumen | Fungsi |
|---|---|
| `PRD.md` | Kebutuhan produk lengkap, alur bisnis, kebutuhan fungsional/non-fungsional, model-view-controller, baseline teknologi, prinsip implementasi, konvensi branch/PR, acceptance criteria, dan roadmap. |
| `ARCHITECTURE.md` | Keputusan arsitektur, modul, struktur kode, tenancy, transaksi, queue, notifikasi real-time, integrasi Python, deployment, dan observability. |
| `SECURITY-RULES.md` | Aturan keamanan wajib dari autentikasi sampai model-level authorization, tenant isolation, audit, upload, API, CI/CD, backup, dan incident response. |
| `UI-RULES.md` | Aturan antarmuka, responsivitas, aksesibilitas, pola layar, komponen, status, formulir, pemindaian barcode, dan UX per role. |
| `BATASAN.md` | Ruang lingkup, di luar lingkup, batas fase, asumsi, keputusan yang tidak boleh diubah diam-diam, serta daftar pertanyaan terbuka. |
| `AGENTS.md` | Instruksi wajib untuk developer dan AI coding agent. |
| [`CI.md`](CI.md) | Kontrak continuous integration — gate merge yang aktif di GitHub Actions. |
| [`CD.md`](CD.md) | Kontrak continuous delivery/deployment — alur deployment development yang aktif. |
| `.agent/README.md` | Entry point aturan agent di repositori. |
| `.agent/WORKFLOW.md` | Workflow implementasi, TDD, review, dan quality gate. |
| `.ai/gudelines/warehouse-project.md` | Custom guideline yang dapat digabungkan oleh Laravel Boost. |

### 1. Prasyarat

Siapkan PHP `8.3–8.5`, Composer, Node.js versi LTS aktif, npm, PostgreSQL, dan Redis. Docker/Sail dapat dipakai agar lingkungan konsisten — lihat `CD.md` untuk alur deployment berbasis Docker yang aktif untuk lingkungan development.

Daftar lengkap dependency baseline (Laravel, Livewire, Fortify, Socialite, Reverb, dll.) beserta rasionalnya ada di `PRD.md` bagian "Baseline Teknologi". Versi final harus dikunci melalui `composer.lock` dan lockfile frontend — jangan mengandalkan versi global mesin developer.

### 2. Clone dan install

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

Custom guideline proyek berada pada `.ai/gudelines/warehouse-project.md`. Setelah file guideline berubah, jalankan kembali instalasi/update Boost sesuai dokumentasi paket.

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

Untuk lingkungan development berbasis Docker (SQLite, image immutable dari GHCR, deployment via GitHub Actions), lihat `CD.md` dan `.env.docker.example` — bukan template di atas, yang menggambarkan target production (PostgreSQL/Redis).

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

Gate lengkap yang benar-benar aktif di GitHub Actions (PHPStan level 7, security regression suite, dependency audit, Gitleaks, kompatibilitas PostgreSQL/Redis, Docker image build) didokumentasikan di [`CI.md`](CI.md). `main` diproteksi oleh GitHub ruleset — PR wajib lolos check tersebut sebelum bisa di-merge.

Prinsip implementasi inti, konvensi branch/pull request, dan urutan roadmap fase ada di `PRD.md`. Baca `PRD.md`, `SECURITY-RULES.md`, dan `ARCHITECTURE.md` sebelum menulis kode.

## CI/CD

Setiap pull request wajib lolos satu workflow GitHub Actions (`.github/workflows/ci-cd.yml`) sebelum bisa di-merge ke `main` — detail lengkapnya ada di [`CI.md`](CI.md) (integrasi) dan [`CD.md`](CD.md) (deployment).

```text
PR dibuka
  → quality (Pint, PHPStan level 7, full test suite, security regression suite, dependency audit, Gitleaks)
  → integration (PostgreSQL + Redis)
  → image build (no publish) — build image runtime & web tanpa push
  → ketiganya WAJIB hijau (GitHub ruleset di `main`, tanpa bypass) sebelum merge
  → merge ke main
  → image publish (GHCR) — build ulang & push image dengan digest immutable
      ke ghcr.io/stephenprasetyachrismawan/warehouse-koperasi[-web]
  → deploy manual: workflow_dispatch dengan run_deploy=true
      → SSH ke VPS development → wh.stevewithcode.net
```

Poin penting:
- `quality`, `integration (PostgreSQL + Redis)`, dan `image build (no publish)` adalah *required check* — nama persis yang dicek GitHub ruleset di `main`, tidak ada bypass actor sama sekali (termasuk admin repo).
- `image publish (GHCR)` hanya berjalan di push ke `main` (bukan di PR), menghasilkan image dengan digest `sha256:...` yang immutable — bukan tag `latest`.
- Deployment ke VPS development **tidak otomatis saat merge** — harus dipicu manual lewat `workflow_dispatch` dengan `run_deploy=true`, dan selalu memakai digest dari run yang sama (lihat [`CD.md`](CD.md) §3).
- Rollback (kalau deploy gagal) otomatis lewat `deploy/rollback-development.sh` dan selalu melaporkan status apa adanya, tidak pernah mengklaim sukses kalau sebenarnya di-rollback.

## License

Warehouse Koperasi is free software: you can redistribute it and/or modify it
under the terms of the **GNU General Public License version 3** (or, at your
option, any later version), as published by the Free Software Foundation.

This program is distributed in the hope that it will be useful, but **WITHOUT
ANY WARRANTY**; without even the implied warranty of MERCHANTABILITY or
FITNESS FOR A PARTICULAR PURPOSE. See [`LICENSE`](LICENSE) for the complete
terms.
