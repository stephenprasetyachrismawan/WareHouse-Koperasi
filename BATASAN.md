# BATASAN.md

## 1. Tujuan Dokumen

Dokumen ini menetapkan batas produk, batas teknis, batas keamanan, asumsi, urutan fase, serta keputusan yang tidak boleh diubah secara diam-diam. Setiap perubahan terhadap batas berikut harus melalui perubahan PRD/ADR dan review keamanan.

## 2. Perubahan Terhadap PRD Sumber

PRD sumber menggambarkan satu perusahaan, satu gudang, empat role operasional, monolith tunggal, dan prediksi yang dihitung di service layer aplikasi. Implementasi ini secara sengaja memperluas atau mengubah beberapa hal:

1. Produk menjadi SaaS multi-warehouse/multi-tenant.
2. Ditambahkan `super_admin` sebagai akun pemilik SaaS dengan scope platform.
3. Setiap warehouse wajib memiliki minimal satu `app_admin` sebagai administrator tenant.
4. `app_admin` dapat mendaftarkan user tenant: Kepala Gudang, Staff Admin, Purchasing, dan Koperasi.
5. Google Sign-In, MFA, invitation-only onboarding, ACL, model policies, dan audit keamanan menjadi requirement inti.
6. Prediksi machine learning tidak dihitung di Laravel. Prediksi dipanggil melalui API service Python terpisah dan hanya dikerjakan pada fase terakhir.
7. Struktur tetap modular monolith untuk aplikasi Laravel utama, dengan service Python sebagai komponen eksternal terbatas.

Perubahan ini mengungguli keputusan arsitektur pada bagian 13 PRD sumber apabila terjadi konflik. Aturan bisnis FR-01 sampai FR-57 tetap dipertahankan kecuali dinyatakan eksplisit di dokumen ini.

## 3. Ruang Lingkup Produk

### 3.1 Platform SaaS

Termasuk:

- provisioning, aktivasi, suspend, dan arsip warehouse/tenant;
- bootstrap dan pengelolaan `super_admin`;
- pengelolaan `app_admin` per warehouse;
- tenant context dan isolasi data;
- audit lintas tenant untuk tindakan platform;
- konfigurasi tenant seperti nama, zona waktu, alamat, domain email yang diizinkan, dan status subscription secara administratif.

Tidak termasuk pada versi awal:

- billing otomatis, payment gateway, invoice subscription, pajak SaaS, metering, dan dunning;
- marketplace plugin;
- white-label domain per tenant;
- data residency per negara;
- database terpisah per tenant;
- self-service sign-up warehouse publik.

### 3.2 Identity dan Access Management

Termasuk:

- Google Sign-In melalui OAuth/OpenID Connect yang dibungkus Laravel Socialite;
- onboarding berbasis invitation;
- MFA aplikasi;
- recovery code dan secure account recovery;
- role dan permission scoped ke warehouse;
- Policies/Gates sampai level model;
- session/device management;
- suspend/restore user;
- perubahan role dan akses dengan audit log;
- akun koperasi dibuat oleh `app_admin`, bukan registrasi publik.

Tidak termasuk pada versi awal:

- SAML enterprise SSO;
- SCIM provisioning;
- LDAP/Active Directory;
- login Facebook, X, GitHub, atau provider sosial lain;
- anonymous/guest access ke data gudang;
- sharing data publik.

### 3.3 Operasional Gudang

Termasuk seluruh proses berikut:

- master barang, barcode, satuan, lokasi, stok minimum;
- stok masuk/keluar dan ledger transaksi;
- stok real-time, stok kritis, dan backorder/stock negatif;
- tiga approval: pembelian, pengeluaran, dan retur;
- request pembelian dari stok kritis, kebutuhan koperasi, atau prediksi;
- duplicate request warning;
- cancellation request dan cancellation oleh Kepala Gudang sebelum PO dikirim;
- batching request oleh Staff Admin dan grouping oleh Purchasing;
- pembuatan PO dan pencatatan pengiriman ke supplier;
- pencatatan penerimaan barang dan QC awal;
- request pengambilan oleh koperasi;
- ready-for-pickup schedule;
- retur dengan foto, verifikasi, attribution gudang/supplier, disposal, replacement, dan pickup ulang;
- inbox, notification, push notification Kepala Gudang, dan dashboard;
- audit trail.

### 3.4 Machine Learning

Termasuk pada fase terakhir:

- pengiriman data/fitur yang telah diminimalkan dari Laravel ke API Python;
- prediksi kebutuhan barang berdasarkan histori arus keluar;
- fallback `0` jika barang tidak memiliki histori;
- penyimpanan model version, request ID, horizon, recommendation, confidence/metadata bila tersedia, dan waktu respons;
- dua jalur hasil prediksi: dimasukkan ke request normal atau request langsung self-approved oleh Kepala Gudang;
- circuit breaker, timeout, retry terbatas, idempotency, audit, dan observability integrasi.

Tidak termasuk tanpa perubahan PRD:

- training model di browser atau di proses Laravel;
- autonomous purchase order tanpa keputusan manusia;
- model yang mengubah stok atau PO secara langsung;
- penggunaan data lintas tenant untuk training tanpa kontrak, anonimisasi, dan persetujuan eksplisit;
- real-time streaming inference per transaksi;
- generative AI untuk mengambil keputusan approval.

## 4. Di Luar Lingkup Bisnis

Ketentuan berikut berasal dari PRD sumber dan tetap di luar lingkup:

- negosiasi harga, penolakan PO, atau partial fulfillment oleh supplier;
- supplier portal penuh;
- refund uang untuk retur;
- barang retur yang disetujui masuk kembali ke stok reguler;
- mekanisme reservasi stok yang menjamin tidak pernah negatif;
- optimistic/pessimistic reservation yang menghalangi backorder;
- akuntansi, jurnal, buku besar, pajak, payroll, dan rekonsiliasi pembayaran;
- manufaktur, bill of materials, production planning, dan work order;
- fleet/logistics delivery management;
- procurement tender dan vendor bidding;
- penilaian supplier yang kompleks;
- forecasting keuangan;
- aplikasi mobile native pada fase awal.

Walaupun reservation bisnis tidak digunakan, operasi database tetap harus atomic untuk mencegah lost update, double approval, dan duplikasi side effect.

## 5. Batas Role dan Delegasi

### 5.1 `super_admin`

- Scope: platform SaaS.
- Boleh membuat/suspend warehouse dan menunjuk `app_admin`.
- Boleh melakukan support access lintas tenant hanya melalui mode impersonation yang time-boxed, membutuhkan step-up MFA, alasan wajib, banner jelas, dan audit append-only.
- Tidak boleh melakukan transaksi operasional tenant sebagai default.
- Tidak boleh melihat file/foto tenant tanpa kebutuhan support yang tercatat.
- Tidak boleh menghapus audit log.

### 5.2 `app_admin`

- Scope: tepat satu atau beberapa warehouse yang secara eksplisit ditugaskan.
- Boleh membuat, mengundang, suspend, dan mengubah role user di warehouse yang dikelola.
- Boleh mengatur permission role tenant dalam batas template yang diizinkan platform.
- Tidak boleh membuat `super_admin`.
- Tidak boleh mengakses warehouse lain.
- Tidak otomatis menjadi Kepala Gudang atau operator transaksi; permission operasional harus diberikan eksplisit dan sebaiknya dipisah untuk segregation of duties.

### 5.3 Kepala Gudang

- Scope: warehouse aktif.
- Approver akhir untuk pembelian, pengeluaran, retur, dan pembatalan request.
- Memiliki full operational visibility di warehouse, bukan visibility platform.
- Direct purchase request dari hasil prediksi adalah self-approved, tetapi wajib menghasilkan approval audit otomatis.

### 5.4 Staff Admin

- Scope: warehouse aktif.
- Mengelola master operasional yang diizinkan, stok, QC, request, penyiapan, pencatatan pengeluaran, retur, dan cancellation request.
- Tidak boleh memutuskan approval final.
- Tidak boleh membatalkan request yang telah berjalan tanpa keputusan Kepala Gudang.

### 5.5 Purchasing

- Scope: warehouse aktif.
- Menerima request yang disetujui, grouping, membuat/mengirim PO, dan mencatat penerimaan yang menjadi tanggung jawabnya.
- Tidak boleh mengubah approval yang telah diputuskan.
- Tidak boleh membuat PO dari request yang belum approved.

### 5.6 Koperasi

- Scope: akun/organisasi koperasi yang ditugaskan pada warehouse.
- Hanya boleh melihat dan mengelola request pengambilan, retur, jadwal, status, dan inbox miliknya.
- Tidak boleh melihat stok internal detail jika kebijakan tenant tidak mengizinkan; UI minimal hanya memberi availability/status yang diperlukan.
- Tidak boleh melihat request koperasi lain.

## 6. Batas Data dan Tenancy

1. Satu record operasional hanya dimiliki satu `warehouse_id`.
2. User dapat memiliki membership di lebih dari satu warehouse hanya jika bisnis menyetujuinya; role dan permission disimpan per membership/tenant, bukan global.
3. `super_admin` tidak menggunakan membership tenant untuk tugas platform, kecuali sedang impersonation.
4. Semua foreign key lintas entitas harus konsisten tenant. Relasi silang tenant harus gagal pada validasi dan database constraint bila memungkinkan.
5. File object storage wajib menyimpan tenant prefix dan metadata kepemilikan; URL langsung tidak dianggap authorization.
6. Export tenant tidak boleh mencampur data tenant lain.
7. Search, autocomplete, queue job, notification, broadcast channel, cache key, dan metrics label wajib tenant-aware.
8. Penghapusan warehouse tidak langsung hard delete. Gunakan suspend, retention, export, lalu controlled purge.

## 7. Batas Status dan Workflow

### 7.1 Request Pembelian

Status resmi:

`DRAFT → WAITING_APPROVAL → APPROVED → PO_CREATED → PO_SENT → GOODS_RECEIVED → COMPLETED`

Cabang terminal:

- `REJECTED`, dengan alasan wajib;
- `CANCELLED`, hanya sebelum `PO_SENT`.

`IN_PROGRESS` adalah kategori query, bukan status tersimpan, dan mencakup seluruh status non-terminal.

### 7.2 Request Pengambilan

`SUBMITTED → CHECKED → PREPARED → WAITING_APPROVAL → APPROVED → READY_FOR_PICKUP → COMPLETED`

Cabang stok tidak tersedia:

`CHECKED → BACKORDERED → PREPARED → WAITING_APPROVAL → ...`

### 7.3 Retur

`SUBMITTED → ADMIN_VERIFIED → WAITING_APPROVAL → APPROVED/REJECTED → REPLACEMENT_PENDING → READY_FOR_REPICKUP → COMPLETED`

Barang lama pada retur approved berstatus disposed dan tidak menambah stok.

### 7.4 Approval

`PENDING → APPROVED/REJECTED` atau `AUTO_APPROVED` untuk request langsung Kepala Gudang.

Keputusan terminal tidak boleh diedit. Koreksi dilakukan melalui compensating action yang tercatat.

### 7.5 Purchase Order

`DRAFT → SENT_TO_SUPPLIER → GOODS_RECEIVED → COMPLETED`

Tidak ada partial receipt pada versi awal. Bila bisnis memerlukan partial receipt, perubahan tersebut membutuhkan revisi schema, status, UI, dan acceptance criteria.

## 8. Batas Stabilitas dan Konsistensi

- Tidak ada distributed transaction antara Laravel, provider push, object storage, dan Python ML.
- Gunakan transactional outbox/idempotent jobs untuk side effect eksternal yang kritis.
- Notifikasi gagal tidak boleh membatalkan transaksi bisnis yang sudah commit.
- Upload file yang gagal harus menghasilkan status yang dapat diperbaiki; jangan menyimpan referensi file seolah berhasil.
- Setiap action mutasi penting menggunakan idempotency key atau guard terhadap double-submit.
- Websocket/push hanya kanal pemberitahuan; database tetap source of truth.
- Nilai stok harus diturunkan dari ledger dan/atau materialized balance yang di-update atomik. Rekonsiliasi periodik wajib tersedia.
- **Preservasi Data Database**: Modifikasi kode, patching, dan deployment biasa TIDAK BOLEH menghapus atau mereset database utama (`database/database.sqlite` / DB produksi). Perintah `php artisan migrate:fresh` hanya dijalankan pada automated testing atau verifikasi awal lingkungan dev; perubahan skema biasa wajib menggunakan `php artisan migrate` agar data pengguna tetap utuh. Data penting/demo yang wajib bertahan dalam environment diisikan melalui Seeder (`DatabaseSeeder.php`).

## 9. Batas UI

- Web responsif adalah target utama; mobile native tidak termasuk.
- Kamera browser untuk barcode/foto boleh digunakan, tetapi manual input tetap disediakan sebagai fallback yang diaudit.
- Form Koperasi harus tetap sederhana, teks besar, langkah minimal.
- UI tidak boleh menampilkan tindakan yang tidak diizinkan, tetapi backend tetap wajib memblokirnya.
- Dark mode opsional; aksesibilitas dan keterbacaan lebih tinggi prioritasnya daripada tema.
- Offline-first/PWA penuh tidak termasuk versi awal.

## 10. Batas Keamanan

- Tidak ada public registration.
- Google account yang sukses login tidak otomatis memperoleh akses; harus cocok dengan invitation/membership aktif.
- MFA wajib untuk seluruh akun production, dengan step-up MFA untuk operasi berisiko tinggi.
- Shared account dilarang.
- Password darurat/break-glass hanya untuk akun yang disetujui, disimpan aman, dipantau, dan diuji berkala.
- Permission tidak boleh ditentukan dari request parameter seperti `role=admin`.
- `super_admin` bypass tidak boleh diterapkan sebagai query tanpa tenant guard yang tidak diaudit.
- Foto QC/retur dianggap data sensitif internal; tidak publik.
- Audit log tidak dapat diedit melalui UI biasa.

## 11. Batas Fase

### Fase 0 — Foundation dan Security

Tenancy, role/permission, policy, audit, CI, logging, secret handling, bootstrap admin, threat model.

### Fase 1 — Identity dan User Management

Google Sign-In, invitation, MFA, user lifecycle, session/device, warehouse membership.

### Fase 2 — Master Data dan Stok

Barang, barcode, lokasi, supplier, stok minimum, stock ledger, transaksi, critical stock, factories/seeders.

### Fase 3 — Pengambilan dan Approval

Request Koperasi, check stock, backorder, approval pengeluaran, ready for pickup, pencatatan keluar.

### Fase 4 — Pembelian dan Penerimaan

Purchase request, duplicate warning, cancellation, batching/grouping, PO, receipt, QC.

### Fase 5 — Retur dan Replacement

Return form, verification, fault attribution, disposal, replacement, pickup ulang.

### Fase 6 — Notification, Dashboard, Hardening

Inbox, push, real-time, operational dashboards, reporting dasar, load/security test, backup/restore drill.

### Fase 7 — Machine Learning API

Hanya dimulai setelah Fase 0–6 memenuhi acceptance criteria dan production readiness review.

## 12. Asumsi

- Satu warehouse memiliki satu timezone utama.
- Satu barcode mengidentifikasi satu barang dalam tenant; uniqueness global tidak diperlukan.
- Satu item request menggunakan satu satuan yang telah didefinisikan pada barang.
- Foto QC dan retur dapat berupa JPEG/PNG/HEIC yang dinormalisasi server-side ke format aman.
- Supplier tidak login ke aplikasi.
- Purchase Order dikirim melalui kanal yang diimplementasikan kemudian, minimal PDF/email manual-assisted; supplier acknowledgment tidak dicatat pada versi awal.
- Notifikasi push Kepala Gudang memerlukan device token yang valid dan consent pengguna.
- Koperasi dapat berupa satu user atau organisasi dengan beberapa user. Implementasi awal boleh satu organisasi dengan beberapa membership bila model data mendukungnya.

## 13. Pertanyaan Terbuka yang Harus Diputuskan Sebelum Coding Modul Terkait

1. Apakah satu user boleh menjadi anggota beberapa warehouse?
2. Apakah satu warehouse boleh memiliki lebih dari satu Kepala Gudang aktif?
3. Apakah `app_admin` boleh merangkap role operasional?
4. Apakah stok ditampilkan kepada Koperasi sebagai angka atau hanya tersedia/tidak tersedia?
5. Siapa yang memasukkan supplier dan barang: `app_admin`, Staff Admin, atau Purchasing?
6. Apakah Purchase Order harus menghasilkan PDF dengan nomor legal tertentu?
7. Apakah penerimaan harus dilakukan Purchasing, Staff Admin, atau dua langkah?
8. Apakah QC wajib untuk setiap item atau cukup per penerimaan/lot?
9. Berapa lama foto QC, retur, audit, dan notifikasi disimpan?
10. Apakah tenant memiliki kebijakan domain Google yang wajib?
11. Apakah semua role wajib TOTP, passkey, atau kombinasi keduanya?
12. Apakah aplikasi membutuhkan Bahasa Indonesia saja atau bilingual?
13. Provider push notification dan object storage apa yang dipilih?
14. Apakah forecasting memakai hari, minggu, atau bulan sebagai horizon resmi?
15. Apakah model Python dilatih per tenant atau model bersama dengan isolasi data?

Keputusan untuk pertanyaan tersebut harus dicatat dalam ADR atau revisi PRD; developer tidak boleh memilih secara diam-diam.
