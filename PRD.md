# PRODUCT REQUIREMENTS DOCUMENT
## Warehouse Koperasi SaaS — Sistem Pengelolaan Gudang

**Status:** Draft implementasi v1.0  
**Target framework:** Laravel 13.x  
**Target UI:** Livewire 4 + Blade + Flux UI + Tailwind CSS  
**Machine learning:** API service Python, fase implementasi terakhir  
**Sumber utama:** `PRD_Sistem_Pengelolaan_Gudang.pdf`  
**Dokumen pendamping:** `ARCHITECTURE.md`, `SECURITY-RULES.md`, `UI-RULES.md`, `BATASAN.md`, `AGENTS.md`

---

## 1. Tujuan Dokumen

Dokumen ini menerjemahkan kebutuhan pada PRD sumber menjadi spesifikasi implementasi web Laravel yang lengkap dan dapat digunakan oleh developer maupun AI coding agent. Seluruh kebutuhan operasional FR-01 sampai FR-57 dipertahankan, kemudian diperluas agar produk dapat berjalan sebagai SaaS multi-warehouse dengan hierarki akun:

- `super_admin` sebagai pemilik/platform operator SaaS;
- `app_admin` sebagai administrator pada warehouse/tenant tertentu;
- Kepala Gudang;
- Staff Admin;
- Purchasing;
- Koperasi.

Dokumen juga menetapkan autentikasi Google, MFA, ACL tenant-scoped sampai tingkat model, audit, dummy data generator, UI modern bawaan ekosistem Laravel, serta integrasi machine learning melalui API Python yang hanya dikerjakan setelah fitur inti stabil.

## 2. Precedence dan Change Control

Urutan precedence apabila dokumen bertentangan:

1. `SECURITY-RULES.md` untuk kontrol keamanan;
2. `BATASAN.md` untuk ruang lingkup dan deliberate override;
3. `PRD.md` untuk kebutuhan produk;
4. `ARCHITECTURE.md` untuk desain teknis;
5. `UI-RULES.md` untuk implementasi antarmuka;
6. PRD PDF sumber untuk konteks asli.

Perubahan requirement wajib:

- memiliki ID perubahan/ticket;
- menjelaskan dampak bisnis, keamanan, tenancy, data, UI, migration, dan test;
- memperbarui traceability matrix;
- direview oleh product owner dan technical/security owner;
- dicatat dalam ADR apabila mengubah keputusan arsitektur.

Developer atau agent dilarang “memperbaiki” aturan bisnis yang tampak tidak lazim tanpa persetujuan. Contoh: aturan attribution retur pada FR-32 harus diimplementasikan sebagaimana tertulis sampai product owner mengubahnya.

## 3. Latar Belakang

Operasional gudang saat ini bergantung pada formulir kertas, WhatsApp, surel, dan catatan manual yang tersebar. Dampaknya:

- stok tidak selalu diperbarui tepat waktu;
- approval terlambat ketika Kepala Gudang tidak berada di lokasi;
- Purchasing menerima request secara tidak terstruktur;
- request dapat terlewat atau terduplikasi;
- koperasi perlu bolak-balik ke gudang;
- bukti kondisi barang dan retur tidak terhubung;
- keputusan sulit diaudit;
- akses user dan tanggung jawab tidak dikelola secara konsisten.

Produk harus memusatkan seluruh alur tersebut, memberikan status real-time, dan memastikan setiap aktor hanya dapat mengakses data serta tindakan sesuai warehouse dan tanggung jawabnya.

## 4. Product Vision

Menyediakan sistem SaaS pengelolaan gudang yang aman, mudah digunakan, dapat diaudit, dan stabil untuk mengelola pergerakan stok, approval, pengadaan, pengambilan koperasi, penerimaan supplier, dan retur dalam satu alur kerja terpusat.

## 5. Sasaran Produk

### 5.1 Sasaran Bisnis

1. Mengurangi ketergantungan pada proses kertas dan komunikasi terpisah.
2. Mempercepat approval pembelian, pengeluaran, dan retur.
3. Mengurangi kesalahan data dan request pembelian ganda.
4. Menyediakan traceability dari request sampai stok dan retur.
5. Mengurangi waktu tunggu koperasi.
6. Menyediakan kontrol dan visibilitas tenant bagi pengelola warehouse.
7. Menyediakan platform SaaS yang dapat melayani lebih dari satu warehouse tanpa kebocoran data.
8. Memungkinkan pengadaan proaktif melalui prediksi pada fase terakhir.

### 5.2 Sasaran Pengguna

- Kepala Gudang dapat memberi keputusan dari perangkat mobile dengan bukti yang cukup.
- Staff Admin dapat menjalankan tugas lapangan dengan sedikit pengetikan.
- Purchasing memiliki inbox request approved yang terstruktur.
- Koperasi dapat mengajukan request dan retur melalui form sederhana.
- `app_admin` dapat mengelola user dan akses warehouse tanpa bantuan `super_admin` untuk operasi rutin.
- `super_admin` dapat mengelola platform tanpa menjadi operator gudang sehari-hari.

### 5.3 Sasaran Teknis

- Laravel modular monolith yang mudah diuji dan dioperasikan;
- tenant isolation kuat;
- stock ledger konsisten;
- authorization pada route, action, policy, model, query, file, queue, dan broadcast;
- audit append-only untuk tindakan penting;
- UI responsif dan accessible;
- side effect asynchronous yang idempotent;
- ML terisolasi di API Python.

### 5.4 Baseline Teknologi

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

Versi final harus dikunci melalui `composer.lock` dan lockfile frontend. Jangan mengandalkan versi global mesin developer. Langkah instalasi ada di `README.md`.

## 6. Non-Goals

Non-goals utama:

- billing subscription otomatis;
- supplier portal;
- negosiasi/penolakan/partial fulfillment supplier;
- refund retur;
- memasukkan barang retur approved ke stok;
- akuntansi dan pembayaran;
- production/manufacturing planning;
- native mobile app;
- autonomous PO oleh ML;
- microservices untuk setiap modul;
- public self-registration.

Detail lengkap ada di `BATASAN.md`.

## 7. Persona

### 7.1 Platform Owner — `super_admin`

Pemilik SaaS yang mengelola lifecycle tenant, menunjuk `app_admin`, melihat kesehatan sistem, dan melakukan support access terkontrol. Membutuhkan visibilitas platform tanpa secara default melihat atau mengubah transaksi tenant.

### 7.2 Warehouse Administrator — `app_admin`

Administrator setiap warehouse. Membuat invitation, user, role tenant, dan account Koperasi. Membutuhkan kontrol akses yang lengkap tetapi tidak boleh memberikan hak platform atau melintasi tenant.

### 7.3 Kepala Gudang — The Approver

Berpengalaman dalam operasional gudang dan membutuhkan approval real-time, monitoring penuh di warehouse, serta kemampuan menjalankan prediksi pada fase terakhir.

### 7.4 Staff Admin Gudang — The Operator

Bekerja di lantai gudang melalui mobile/tablet. Membutuhkan scanner barcode, form singkat, task list, stock update cepat, QC, dan retur.

### 7.5 Purchasing — The Coordinator

Menghubungkan gudang dan supplier. Membutuhkan inbox approved request, grouping, PO, dan penerimaan yang tertelusur.

### 7.6 Koperasi — The Customer

Memerlukan form seperti formulir kertas untuk request dan retur, serta status dan jadwal yang jelas tanpa harus datang berulang kali.

## 8. Tenant, Account, dan Role Model

### 8.1 Tenant

`Warehouse` adalah tenant utama. Seluruh data operasional memiliki `warehouse_id`.

### 8.2 Membership

Akses user ke warehouse diberikan melalui `WarehouseMembership`. Role tenant ditempelkan pada membership, bukan semata-mata pada user global.

### 8.3 Hierarki

```text
super_admin (platform)
└── warehouse
    └── app_admin
        ├── kepala_gudang
        ├── staff_admin
        ├── purchasing
        └── koperasi
```

Hierarki menunjukkan kewenangan provisioning, bukan inheritance permission otomatis. `app_admin` tidak otomatis memiliki permission operasional.

### 8.4 Role Matrix Ringkas

| Capability | super_admin | app_admin | Kepala Gudang | Staff Admin | Purchasing | Koperasi |
|---|:---:|:---:|:---:|:---:|:---:|:---:|
| Kelola tenant | ✓ | – | – | – | – | – |
| Tunjuk app admin | ✓ | – | – | – | – | – |
| Kelola user tenant | support only | ✓ | – | – | – | – |
| Kelola role tenant | template/platform | ✓ terbatas | – | – | – | – |
| Lihat semua operasional tenant | impersonation only | sesuai permission | ✓ | terbatas | terbatas | milik sendiri |
| Approval pembelian | – | bila role tambahan | ✓ | – | – | – |
| Approval pengeluaran | – | bila role tambahan | ✓ | – | – | – |
| Approval retur | – | bila role tambahan | ✓ | – | – | – |
| Stock in/out | – | bila role tambahan | monitoring/opsional | ✓ | receipt sesuai proses | – |
| Buat purchase request | – | bila role tambahan | direct ML/opsional | ✓ | – | memicu via request pickup |
| Buat/kirim PO | – | bila role tambahan | – | – | ✓ | – |
| Request barang | – | – | – | – | – | ✓ |
| Retur | – | – | approval | verify | – | submit |
| Prediksi | – | – | ✓ | – | – | – |

Tabel permission rinci dihasilkan melalui seed data dan diuji pada authorization matrix.

## 9. Skenario Utama

### 9.1 Stok Kritis dan Pembelian

Staff Admin menemukan stok di bawah minimum, membuat request, menerima duplicate warning bila ada request berjalan, lalu request dikirim ke Kepala Gudang. Setelah approved, Purchasing membuat PO. Barang diterima dan QC dicatat, lalu stok diperbarui.

### 9.2 Pengambilan oleh Koperasi — Stok Tersedia

Koperasi membuat request. Staff Admin memeriksa dan menyiapkan barang. Kepala Gudang approve pengeluaran. Koperasi menerima jadwal. Saat barang diambil, Staff Admin scan/catat pengeluaran.

### 9.3 Pengambilan oleh Koperasi — Stok Tidak Tersedia

Request menjadi backorder dan memicu draft purchase request. Setelah pembelian, penerimaan, QC, dan stok tersedia, barang disiapkan dan pengeluaran di-approve.

### 9.4 Retur

Koperasi mengunggah foto dan alasan. Staff Admin scan, memverifikasi, dan mengambil foto kerusakan. Kepala Gudang melihat bukti dan memberi keputusan. Sistem menentukan attribution berdasarkan bukti QC penerimaan. Jika approved, barang lama disposed dan replacement diproses. Bila stok replacement kosong, alur pembelian dijalankan terlebih dahulu.

### 9.5 Prediksi Pembelian

Pada fase terakhir, Kepala Gudang memilih item/horizon dan meminta prediksi. Laravel mengirim agregat histori arus keluar ke service Python. Hasil dapat:

- dimasukkan ke jalur purchase request normal; atau
- dikirim langsung ke Purchasing sebagai request `AUTO_APPROVED`.

### 9.6 User Provisioning

`app_admin` membuat invitation untuk email Google tertentu, memilih role dan warehouse. User login Google, menyelesaikan MFA, lalu membership aktif. Perubahan role mencabut/mengevaluasi session dan dicatat di audit.

### 9.7 Support Access

`super_admin` memulai support session untuk tenant tertentu setelah step-up MFA dan alasan. Session time-boxed, banner jelas, dan seluruh action mencatat actor serta impersonator.

## 10. Functional Requirements — Kebutuhan Sumber FR-01 sampai FR-57

### 10.1 Authentication dan Akses

**FR-01** Sistem menyediakan login untuk Kepala Gudang, Staff Admin, Purchasing, dan Koperasi. Implementasi SaaS menambahkan `super_admin` dan `app_admin`.

**FR-02** Setiap role memiliki dashboard dan hak akses berbeda sesuai job description dan warehouse membership.

**FR-03** Kepala Gudang dapat mengakses seluruh approval dan monitoring operasional pada warehouse yang ditugaskan.

**FR-04** Koperasi hanya dapat mengakses request barang, retur, jadwal, status, dan inbox miliknya.

**FR-05** Staff Admin dapat mengakses manajemen stok, retur, request Koperasi, purchase request, pencatatan pengeluaran, penerimaan/QC sesuai assignment, dan notifikasi approval.

**FR-06** Purchasing dapat mengakses approved purchase request, grouping, PO, supplier, dan penerimaan sesuai permission.

### 10.2 Stock Management

**FR-07** Staff Admin dapat mencatat barang masuk dan keluar melalui pemindaian barcode dengan fallback input manual.

**FR-08** Sistem memperbarui dan menampilkan stok real-time setelah transaksi commit.

**FR-09** Pencatatan barang retur dipisahkan dari stok reguler.

**FR-10** Sistem menandai item dengan stok di bawah minimum sebagai stok kritis.

### 10.3 Approval Workflow

**FR-11** Kepala Gudang menerima notifikasi approval untuk pembelian, pengeluaran, dan retur.

**FR-12** Kepala Gudang dapat menyetujui atau menolak melalui action yang ringkas setelah melihat konteks dan bukti.

**FR-13** Penolakan wajib memiliki alasan.

**FR-14** Status approval diperbarui real-time dan dapat dilihat pihak terkait sesuai policy.

### 10.4 Purchase Request

**FR-15** Staff Admin dapat membuat purchase request saat stok kritis atau kebutuhan sah lain.

**FR-16** Jika barang yang diminta Koperasi tidak tersedia, sistem membuat draft purchase request.

**FR-17** Purchase request normal dikirim ke Kepala Gudang sebelum Purchasing.

**FR-18** Approved purchase request masuk ke inbox Purchasing.

**FR-19** Purchasing melihat item, quantity, urgency, sumber, request asal, dan status.

**FR-20** Purchasing mengonversi approved request/group menjadi PO dan mencatat pengiriman ke supplier.

### 10.5 Pickup oleh Koperasi

**FR-21** Koperasi dapat membuat request pengambilan.

**FR-22** Staff Admin menerima task dan memeriksa ketersediaan.

**FR-23** Bila tersedia, Staff Admin menyiapkan, Kepala Gudang approve, dan sistem memberi tahu Koperasi.

**FR-24** Bila tidak tersedia, sistem mengikuti FR-16 dan backorder.

**FR-25** Koperasi menerima notifikasi “Barang Siap Diambil” beserta jadwal.

**FR-26** Staff Admin mencatat pengeluaran saat pickup melalui scan/konfirmasi.

### 10.6 Penerimaan Supplier

**FR-27** Purchasing mencatat penerimaan barang dari supplier ke warehouse.

**FR-28** Staff Admin melakukan QC saat penerimaan dan menyimpan hasil serta foto sebagai bukti kondisi awal.

### 10.7 Retur

**FR-29** Koperasi mengajukan retur melalui form, foto, dan alasan.

**FR-30** Staff Admin memverifikasi retur dengan scan barcode dan foto kerusakan.

**FR-31** Kepala Gudang melihat foto sebelum memutuskan.

**FR-32** Sistem membandingkan retur dengan bukti QC: bila bukti pemeriksaan tersedia, attribution ke gudang; bila tidak tersedia, attribution ke supplier.

**FR-33** Koperasi menerima status retur dan alasan.

### 10.8 Machine Learning

**FR-34** Sistem menyediakan prediksi kebutuhan item berdasarkan histori arus keluar melalui API Python.

**FR-35** Kepala Gudang memilih item dan horizon, lalu menjalankan prediksi.

**FR-36** Kepala Gudang dapat menetapkan hasil ke jalur request normal.

**FR-37** Kepala Gudang dapat membuat direct purchase request dari hasil prediksi.

**FR-38** Dashboard Staff Admin menampilkan total request in-progress per item dari seluruh sumber.

**FR-39** Kepala Gudang melihat prediksi, purchase request, approval, stok, pengeluaran, dan retur pada warehouse.

**FR-40** Item tanpa histori menghasilkan fallback rekomendasi `0`.

### 10.9 Stock Parameter

**FR-41** Staff Admin dapat menetapkan dan memperbarui minimum stock sesuai permission.

### 10.10 Batching dan Grouping

**FR-42** Purchase request dibuat per item dan dapat dimasukkan melalui batch.

**FR-43** Purchasing dapat grouping request/item terpilih, termasuk quantity item sama dari beberapa Koperasi setelah mempertimbangkan stok.

**FR-44** Satu request Koperasi tidak harus dipetakan ke satu purchase request tunggal; traceability allocation harus dipertahankan.

### 10.11 Warning dan Cancellation

**FR-45** Saat request dibuat untuk item yang sudah memiliki request in-progress, sistem memberi warning kepada pembuat dan inbox Staff Admin/Kepala Gudang; pembuat dapat lanjut atau batal.

**FR-46** Hanya Kepala Gudang yang dapat membatalkan purchase request yang berjalan.

**FR-47** Staff Admin dapat mengajukan cancellation request dengan alasan; Kepala Gudang approve/reject. Reject membuat request tetap berjalan.

**FR-48** Kepala Gudang dapat membatalkan sepihak sebelum PO dikirim ke supplier, dengan notifikasi ke Staff Admin dan Purchasing.

**FR-49** Tanpa cancellation, request berjalan normal.

### 10.12 Inbox dan Notification

**FR-50** Setiap akun memiliki inbox untuk approval, warning, status request, pickup schedule, dan return status.

**FR-51** Notifikasi penting masuk inbox; Kepala Gudang juga menerima push notification sesuai device/consent.

### 10.13 Stock/Return Rules

**FR-52** Stok dapat negatif sebagai backorder; pickup tertunda sampai cukup.

**FR-53** Barang retur approved dianggap disposed dan tidak kembali ke stok.

**FR-54** Direct purchase request Kepala Gudang bersifat self-approved dan menghasilkan audit `AUTO_APPROVED`.

### 10.14 Replacement

**FR-55** Retur approved dikompensasi replacement, bukan refund.

**FR-56** Sistem menerbitkan repickup schedule untuk replacement.

**FR-57** Bila stok replacement tidak tersedia, alur purchase request dijalankan sebelum schedule diterbitkan.

## 11. Functional Requirements — Perluasan SaaS dan Security

**FR-58 — Multi-Warehouse Tenancy**  
Sistem melayani beberapa warehouse dengan isolasi data, konfigurasi, role, file, cache, queue, notification, dan audit.

**FR-59 — Platform Super Admin**  
Sistem menyediakan `super_admin` untuk provisioning/suspend warehouse, assignment app admin, security monitoring, dan support session terkontrol.

**FR-60 — Warehouse App Admin**  
Setiap warehouse memiliki minimal satu `app_admin` yang dapat mengelola user dan role tenant di bawahnya.

**FR-61 — Invitation-Only Onboarding**  
User tidak mendaftar publik. `super_admin` atau `app_admin` membuat invitation terikat email, tenant, role, expiry, dan token sekali pakai.

**FR-62 — Google Sign-In**  
User login dengan Google. Identity provider success hanya mengautentikasi identitas; membership aktif tetap diperlukan.

**FR-63 — MFA**  
Seluruh user production wajib menyelesaikan MFA. Privileged action memerlukan step-up MFA.

**FR-64 — Tenant-Scoped RBAC**  
Role/permission disimpan per warehouse membership. `app_admin` tidak dapat memberikan capability platform.

**FR-65 — Model-Level ACL**  
Setiap model dan action sensitif memiliki Policy/Gate yang memeriksa actor, tenant, permission, ownership, status, dan segregation of duties.

**FR-66 — User Lifecycle**  
Admin dapat invite, resend, revoke, activate, suspend, restore, change role, revoke session, dan reset MFA melalui workflow aman.

**FR-67 — Session and Device Management**  
User melihat session/device dan mencabutnya; admin dapat revoke sesuai permission; privilege change dapat mencabut session.

**FR-68 — Immutable Audit Trail**  
Sistem mencatat auth, access change, impersonation, stock, approval, cancellation, PO, return, file, export, dan ML metadata dalam audit append-only.

**FR-69 — Secure Attachment**  
Foto QC/retur disimpan private, divalidasi, diproses, dan diakses melalui policy + temporary signed URL.

**FR-70 — Support Impersonation**  
`super_admin` dapat membuka support session time-boxed dengan step-up MFA, alasan, banner, restriction, dan audit actor/impersonator.

**FR-71 — Factories and Seeders**  
Sistem menyediakan Laravel factories/states dan seeders untuk demo, test, role/permission, serta edge cases.

**FR-72 — ML API Boundary**  
Prediksi dipanggil melalui interface/gateway ke Python API dengan authentication, timeout, idempotency, schema validation, dan data minimization.

**FR-73 — ML Feature Gate**  
ML default disabled dan hanya diaktifkan pada fase terakhir setelah core production readiness.

**FR-74 — Tenant-Aware Real-Time**  
Broadcast channel dan push notification memverifikasi tenant/membership dan tidak mengirim payload sensitif.

**FR-75 — Operational Health**  
Platform menyediakan health, job failure, stock reconciliation alert, audit write failure alert, dan integration monitoring yang hanya dapat diakses actor berwenang.

## 12. Feature Identification dan Traceability

| Source Feature | Implementasi |
|---|---|
| F1 Approval Notification System | Approval inbox, database notification, broadcast, push Kepala Gudang |
| F2 Barcode Scan IN/OUT | Mobile scanner + manual fallback + stock action |
| F3 Stok Kritis Alert | Minimum stock rule + critical query + notification |
| F4 Return Checklist | Staff task list untuk retur |
| F5 Purchase Request Inbox | Purchasing approved inbox |
| F6 Purchase Order ke Supplier | PO lifecycle, send action, audit |
| F7 Koperasi Request Form | Simplified multi-item form |
| F8 Check Stock & Auto-Draft Purchase | Availability action + backorder + draft purchase |
| F9 Ready for Pickup Notification | Schedule + persistent notification |
| F10 Koperasi Return Form | Return form + evidence upload |
| F11 Return Quality Check | Staff verification + damage evidence |
| F12 Fault Attribution | Rule engine berdasarkan QC evidence |
| F13 Return Status Notification | Inbox/push channel sesuai actor |
| F14 Purchase Prediction | Python API prediction |
| F15 Prediction-to-Request | Convert prediction ke normal request |
| F16 Direct Purchase Request | Kepala Gudang auto-approved request |
| F17 In-Progress Request Aggregator | Query per item/source/status |
| F18 Full Visibility Dashboard | Warehouse-level operational dashboard |
| F19 Duplicate Request Warning | Detection + override/cancel + inbox |
| F20 Request Cancellation Workflow | Staff request + Kepala decision/direct cancel |
| F21 User Inbox | Persistent tenant-aware inbox |
| F22 Request Batching & Grouping | Batch input, group, allocation trace |
| F23 Stock Parameter Setting | Minimum stock per item |

Perluasan SaaS menambahkan user/tenant/access/security modules tanpa menghapus feature sumber.

## 13. Detailed Module Requirements

### 13.1 Platform dan Warehouse Provisioning

#### Capabilities

- create warehouse;
- assign initial app admin;
- activate/suspend/archive;
- configure name, code, timezone, address, allowed Google domain, status;
- view platform audit/security/health;
- support session.

#### Acceptance Criteria

- warehouse baru tidak memiliki data tenant lain;
- initial app admin menerima invitation;
- suspend warehouse memblokir login/transactions tetapi mempertahankan data;
- action menghasilkan audit;
- non-super-admin mendapat 403/not found netral;
- support access membutuhkan MFA, reason, expiry.

### 13.2 User Management

#### Capabilities

- list/search/filter tenant users;
- invite user;
- create Koperasi account/membership;
- assign role template;
- customize permission dalam batas;
- resend/revoke invitation;
- suspend/restore;
- revoke sessions;
- reset MFA workflow;
- view audit.

#### Business Rules

- email invitation unique per pending invitation/tenant;
- same Google identity dapat memiliki membership berbeda bila diizinkan;
- app admin tidak dapat mengubah super admin;
- minimum one active app admin per active warehouse;
- tidak boleh menonaktifkan app admin terakhir tanpa replacement;
- privileged changes require step-up MFA.

### 13.3 Authentication

#### Flow

1. user membuka login;
2. redirect Google dengan state;
3. callback diverifikasi;
4. provider identity dipetakan;
5. invitation/membership/status diperiksa;
6. user enroll/challenge MFA;
7. tenant context dipilih;
8. redirect role dashboard;
9. audit login.

#### Failure States

- invalid state;
- email unverified;
- no invitation/membership;
- invitation expired;
- user suspended;
- warehouse suspended;
- MFA failed/locked;
- provider outage;
- session expired.

### 13.4 Catalog

#### Item Fields

- public ID;
- warehouse;
- code/SKU;
- name;
- description optional;
- barcode(s);
- unit;
- minimum stock;
- active/archive status;
- default location optional;
- metadata/notes.

#### Supplier Fields

- warehouse;
- name;
- contact name;
- email;
- phone;
- address;
- active;
- notes.

No sensitive bank/payment data in v1.

### 13.5 Inventory

#### Capabilities

- stock overview;
- ledger;
- scan in/out;
- opening balance;
- adjustment with reason/permission;
- critical stock;
- negative stock/backorder;
- reconciliation;
- history/export controlled.

#### Acceptance Criteria

- each movement creates immutable ledger entry;
- balance updated atomically;
- duplicate submit does not double movement;
- movement references source entity;
- tenant isolation tests pass;
- approved return does not increase stock;
- real-time view refreshes after commit.

### 13.6 Purchase Requests

#### Sources

```text
CRITICAL_STOCK
COOPERATIVE_BACKORDER
PREDICTION_NORMAL
PREDICTION_DIRECT
MANUAL_STAFF
RETURN_REPLACEMENT
```

#### Fields

- warehouse;
- request number;
- source;
- creator;
- urgency;
- status;
- items/quantities;
- linked pickup/return/prediction;
- duplicate override reason optional;
- group/allocation;
- timestamps/version.

#### Duplicate Detection

A request dianggap duplicate candidate bila item sama memiliki request non-terminal di warehouse. Sistem menampilkan total in-progress dan sumber. Override tidak dilarang, tetapi dicatat dan diinformasikan.

### 13.7 Approval

Approval UI menampilkan:

- request identifier;
- warehouse;
- creator/source;
- item/quantity;
- current stock/minimum/in-progress total;
- evidence/link;
- warning;
- previous decisions;
- action approve/reject.

Reject reason wajib. Decision terminal immutable.

### 13.8 Cancellation

- Staff Admin membuat `CancellationRequest` dengan alasan.
- Kepala Gudang approve/reject.
- Kepala Gudang dapat direct cancel sebelum PO sent.
- Jika PO sent, action ditolak.
- Purchasing dan Staff Admin menerima notification.
- Allocation/group diperbarui secara aman.

### 13.9 Grouping dan PO

Grouping workspace menunjukkan:

- approved unallocated lines;
- item/supplier candidate;
- source requests dan Koperasi;
- total quantity;
- stock context;
- duplicate warning;
- allocation preview.

PO hanya dibuat dari approved quantities. `Send PO` adalah action terminal untuk cancellation eligibility.

### 13.10 Receipt dan QC

Receipt:

- references PO;
- recorded by Purchasing;
- quantity expected/received sesuai scope no partial fulfillment;
- receipt date;
- supplier document optional;
- items.

QC:

- performed by Staff Admin;
- result per item/receipt;
- photo evidence;
- notes;
- pass/fail/condition enum;
- completed before stock-in commit atau melalui controlled flow.

### 13.11 Pickup

Koperasi request dapat memiliki beberapa item. Staff check tiap item. V1 dapat menetapkan request sebagai satu kesatuan untuk schedule; allocation per item tetap disimpan.

Pickup completion:

- scan item;
- confirm quantity;
- verify approved/ready status;
- record recipient;
- append stock out;
- set completed;
- notify Koperasi.

### 13.12 Returns

Return harus merujuk original pickup bila tersedia agar item/quantity/tenant tervalidasi. Return quantity tidak boleh melebihi quantity eligible tanpa override policy.

Fault attribution rule version disimpan untuk audit. Replacement membuat linked pickup request. Old item disposed event dicatat tanpa stock-in.

### 13.13 Inbox dan Notifications

Notification type minimum:

```text
APPROVAL_REQUIRED
APPROVAL_APPROVED
APPROVAL_REJECTED
DUPLICATE_PURCHASE_WARNING
PURCHASE_REQUEST_STATUS
CANCELLATION_REQUIRED
CANCELLATION_STATUS
PO_STATUS
RECEIPT_REQUIRED
PICKUP_REQUESTED
READY_FOR_PICKUP
RETURN_SUBMITTED
RETURN_STATUS
REPLACEMENT_READY
SECURITY_ALERT
INVITATION
```

Inbox item hanya dapat dibuka jika target masih authorized.

### 13.14 Prediction

Prediction page hanya visible jika feature flag aktif dan actor Kepala Gudang memiliki permission.

Prediction record menyimpan:

- warehouse/item;
- requested by;
- horizon;
- recommendation;
- fallback;
- model version;
- request/correlation ID;
- status/latency;
- created time;
- linked purchase request(s).

Recommendation harus dapat diedit manusia sebelum convert, dengan original recommendation tetap tersimpan.

## 14. MVC dan Application Component Specification

### 14.1 Models

| Model | Tujuan | Tenant-scoped |
|---|---|:---:|
| User | Identity global | tidak langsung |
| Warehouse | Tenant | platform |
| WarehouseMembership | User-role-tenant relationship | ✓ |
| Invitation | Onboarding | ✓ |
| Item | Master barang | ✓ |
| ItemBarcode | Barcode | ✓ |
| WarehouseLocation | Lokasi | ✓ |
| StockBalance | Saldo item/location | ✓ |
| StockTransaction | Ledger | ✓ |
| Supplier | Supplier master | ✓ |
| PickupRequest/Item | Request Koperasi | ✓ |
| PurchaseRequest/Item | Request pembelian | ✓ |
| PurchaseRequestGroup/Allocation | Grouping trace | ✓ |
| CancellationRequest | Pembatalan | ✓ |
| PurchaseOrder/Item | PO | ✓ |
| GoodsReceipt/Item | Penerimaan | ✓ |
| QualityInspection | QC evidence | ✓ |
| ReturnRequest/Item | Retur | ✓ |
| Approval | Decision | ✓ |
| Attachment | File metadata | ✓ |
| DeviceToken | Push device | user/tenant context |
| AuditEvent | Audit | platform/tenant |
| SecurityEvent | Security monitoring | platform/tenant |
| Prediction | ML result | ✓ |
| OutboxMessage | Async side effect | ✓/platform |
| IdempotencyKey | Replay protection | ✓/actor |

### 14.2 Views/Livewire Pages

#### Platform

- platform dashboard;
- warehouses index/create/show/edit;
- app admin assignment;
- audit/security events;
- support session.

#### Tenant Admin

- users/invitations;
- roles/permission matrix;
- warehouse settings;
- sessions/MFA reset;
- tenant audit.

#### Operational

- role dashboards;
- items/suppliers;
- stock overview/ledger/scanner/critical;
- purchase request list/form/detail/approval/cancellation/grouping;
- PO list/form/detail/send;
- receipt/QC;
- pickup list/form/preparation/approval/schedule/completion;
- return form/verification/approval/attribution/replacement;
- inbox;
- prediction pages.

### 14.3 Controllers/Livewire Actions

Controllers atau full-page Livewire components harus tetap memakai Action/Service dan Policy yang sama.

Minimum HTTP controllers tercantum di `ARCHITECTURE.md`. Tidak boleh ada generic controller yang mengubah status arbitrary.

### 14.4 Form Requests

Contoh:

```text
InviteUserRequest
UpdateMembershipRoleRequest
CreateItemRequest
RecordStockMovementRequest
CreatePurchaseRequestRequest
ApprovePurchaseRequestRequest
RejectApprovalRequest
CreateCancellationRequest
GroupPurchaseRequestsRequest
CreatePurchaseOrderRequest
SendPurchaseOrderRequest
RecordGoodsReceiptRequest
CompleteQualityInspectionRequest
CreatePickupRequestRequest
PreparePickupRequest
CompletePickupRequest
CreateReturnRequest
VerifyReturnRequest
ApproveReturnRequest
RunPredictionRequest
ConvertPredictionRequest
```

`authorize()` memanggil Policy/Gate. Input actor/warehouse/status tidak dipercaya dari client.

### 14.5 Policies

Policy wajib untuk semua model sensitif. Method minimum mengikuti kebutuhan:

```text
viewAny, view, create, update, archive/delete,
approve, reject, cancel, send, receive, verify,
downloadAttachment, export, impersonate
```

### 14.6 Actions/Services

```text
InviteWarehouseUser
AcceptInvitation
AssignTenantRole
SuspendMembership
ResolveTenantContext
RecordStockIn
RecordStockOut
ReconcileStock
CreatePurchaseRequest
DetectDuplicatePurchaseRequest
SubmitPurchaseForApproval
ApprovePurchaseRequest
RejectPurchaseRequest
RequestPurchaseCancellation
CancelPurchaseRequest
GroupPurchaseRequestLines
CreatePurchaseOrder
SendPurchaseOrder
RecordGoodsReceipt
CompleteQualityInspection
CreatePickupRequest
CheckPickupAvailability
PreparePickup
ApprovePickupRelease
CompletePickup
CreateReturn
VerifyReturn
DetermineReturnFault
ApproveReturn
RejectReturn
CreateReplacementPickup
DispatchDomainNotification
StartSupportSession
RunPurchasePrediction
ConvertPredictionToPurchaseRequest
```

## 15. Data Dictionary

### 15.1 Warehouse

```text
id/public_id
name
code
status
address
timezone
allowed_google_domains json
settings json
created_by
created_at/updated_at/suspended_at
```

### 15.2 WarehouseMembership

```text
id
warehouse_id
user_id
status
primary_role_id
joined_at
suspended_at
invited_by
last_active_at
```

Role/permission pivot memakai warehouse/team context.

### 15.3 Item

```text
id/public_id
warehouse_id
code
name
description
unit
minimum_stock
is_active
archived_at
created_by/updated_by
```

### 15.4 StockBalance

```text
id
warehouse_id
item_id
location_id nullable
quantity
version
updated_at
```

### 15.5 StockTransaction

```text
id/public_id
warehouse_id
item_id
location_id nullable
movement_type
signed_quantity
balance_after
source_type/source_id
reason
performed_by
idempotency_key
occurred_at
reversal_of_id nullable
metadata
```

### 15.6 PurchaseRequest

```text
id/public_id
warehouse_id
request_number
source
status
urgency
created_by
prediction_id nullable
linked_pickup_id nullable
linked_return_id nullable
duplicate_override_at/by/reason nullable
version
submitted_at/approved_at/completed_at/cancelled_at
```

### 15.7 Approval

```text
id/public_id
warehouse_id
approval_type
approvable_type/id
status
requested_by
approver_id nullable
reason nullable
auto_approved boolean
source_version
requested_at
decided_at
impersonator_id nullable
metadata
```

### 15.8 PurchaseOrder

```text
id/public_id
warehouse_id
po_number
supplier_id
status
created_by
sent_by/sent_at
received_at
metadata
```

### 15.9 GoodsReceipt dan QualityInspection

```text
goods_receipts:
  warehouse_id, purchase_order_id, receipt_number, received_by,
  received_at, status, notes

quality_inspections:
  warehouse_id, goods_receipt_item_id, result, notes,
  inspected_by, inspected_at, evidence_status
```

### 15.10 PickupRequest

```text
id/public_id
warehouse_id
cooperative_membership_id
request_number
status
requested_at
prepared_by/at
approved_by/at
pickup_scheduled_at
completed_by/at
version
```

### 15.11 ReturnRequest

```text
id/public_id
warehouse_id
pickup_request_id nullable
cooperative_membership_id
return_number
status
reason_code/reason_text
verified_by/at
approved_by/at
fault_attribution
fault_rule_version
replacement_pickup_id nullable
completed_at
version
```

### 15.12 Prediction

```text
id/public_id
warehouse_id
item_id
requested_by
horizon_days
recommended_quantity
adjusted_quantity nullable
fallback
fallback_reason nullable
model_version
request_id
status
latency_ms
error_code nullable
created_at
```

## 16. State Lifecycle

Status lifecycle mengikuti `ARCHITECTURE.md`. Aturan umum:

- enum terkontrol;
- action-specific transition;
- terminal state immutable;
- stale version menghasilkan conflict;
- transition dalam transaction;
- audit dan outbox ditulis bersama;
- UI menampilkan state dan next actor.

## 17. Non-Functional Requirements

### 17.1 Source NFR

**NFR-01** Staff Admin UI mobile/tablet optimized.  
**NFR-02** Form Koperasi sederhana, teks besar, tombol minimal.  
**NFR-03** Push approval real-time ke Kepala Gudang.  
**NFR-04** Stock/approval real-time tanpa refresh manual.  
**NFR-05** Minimize typing melalui barcode.  
**NFR-06** Audit approval/rejection dan alasan.  
**NFR-07** Dapat dipakai user literasi teknologi rendah.

### 17.2 Security

- seluruh control di `SECURITY-RULES.md` mandatory;
- zero known critical/high vulnerability tanpa risk acceptance;
- tenant isolation automated test;
- MFA enforcement;
- private file storage;
- no public registration;
- least privilege DB/runtime;
- audit integrity.

### 17.3 Performance Targets

Target awal pada workload normal staging/production:

- p95 page/action non-report < 2 detik di region deployment;
- p95 simple API/action < 500 ms tanpa provider eksternal;
- stock/approval broadcast terlihat umumnya < 3 detik setelah commit;
- barcode lookup < 500 ms p95;
- inbox/dashboard first useful content < 2.5 detik p75 pada koneksi wajar;
- ML timeout default 5 detik, configurable;
- heavy export asynchronous.

Target harus divalidasi dengan load test dan dapat direvisi berdasarkan baseline nyata.

### 17.4 Availability dan Resilience

- core workflow tetap berfungsi bila push provider gagal;
- inbox tetap source of truth;
- ML failure tidak menghentikan core;
- queue retry/dead letter;
- backup dan restore tested;
- health/readiness;
- no network call inside DB transaction;
- idempotent retry.

### 17.5 Scalability

Aplikasi harus dapat menambah warehouse dan user tanpa schema per tenant. Scale web/worker/Reverb secara horizontal. ML scale terpisah.

### 17.6 Accessibility

WCAG 2.2 AA target; keyboard, contrast, labels, focus, touch targets, screen reader semantics.

### 17.7 Maintainability

- controller tipis;
- named actions;
- enums/value objects;
- no arbitrary status update;
- architecture tests;
- docs/ADR;
- code style Pint;
- static analysis;
- test suite deterministic.

### 17.8 Observability

Structured logs, metrics, correlation IDs, queue failure, stock reconciliation, security alerts, audit write alert, ML metrics.

### 17.9 Data Retention dan Privacy

Retention diputuskan sebelum production. Data minimization, private evidence, anonymized non-prod, controlled export/purge.

## 18. Dummy Data Generator

### 18.1 Requirements

Setiap model utama memiliki Laravel Factory. Factory harus:

- menghasilkan data valid sesuai tenant;
- memiliki state untuk status utama;
- tidak membuat cross-tenant relation;
- mendukung deterministic seed;
- dapat membuat high-volume data untuk performance test;
- menghasilkan edge cases.

### 18.2 Required Factory States

```text
WarehouseFactory::active(), suspended()
UserFactory::superAdmin(), appAdmin(), head(), staff(), purchasing(), cooperative()
MembershipFactory::active(), suspended(), invited()
ItemFactory::critical(), healthy(), noHistory()
StockBalanceFactory::positive(), zero(), negative()
PurchaseRequestFactory::draft(), waitingApproval(), approved(), rejected(), cancelled(), inProgress()
ApprovalFactory::pending(), approved(), rejected(), autoApproved()
PurchaseOrderFactory::draft(), sent(), received(), completed()
PickupRequestFactory::submitted(), backordered(), ready(), completed()
ReturnRequestFactory::submitted(), verified(), approved(), rejected(), replacementPending()
NotificationFactory::read(), unread()
PredictionFactory::success(), fallback(), failed()
```

### 18.3 Demo Scenarios

Seeder demo minimal membuat:

1. dua warehouse agar tenant isolation dapat diuji;
2. seluruh role per warehouse;
3. item dengan stok sehat/kritis/nol/negatif;
4. duplicate in-progress purchase request;
5. request Koperasi tersedia dan backorder;
6. approval pending/approved/rejected;
7. PO draft/sent/received;
8. QC dengan dan tanpa bukti;
9. retur approved/rejected;
10. replacement ready/backorder;
11. notification unread/read;
12. support session/audit contoh;
13. item tanpa histori untuk fallback ML.

### 18.4 Production Seeder

Production seeder hanya:

- permission;
- role template;
- system settings;
- bootstrap command untuk super admin.

Tidak ada demo password atau data palsu production.

## 19. API Requirements

### 19.1 Internal Web/API

UI utama memakai web/session/Livewire. API stateless hanya dibuat bila ada kebutuhan integration/mobile. Setiap route memiliki auth, rate limit, tenant context, Policy, validation, idempotency bila mutasi.

### 19.2 ML API

Contract dan security di `ARCHITECTURE.md`/`SECURITY-RULES.md`. Versioning endpoint, contoh:

```text
POST /v1/predictions/purchase-demand
GET /health/live
GET /health/ready
```

Laravel tidak memberi DB credential ke Python.

### 19.3 Error Contract

External/internal API error terstruktur:

```json
{
  "error": {
    "code": "PREDICTION_TIMEOUT",
    "message": "Prediksi belum dapat diproses.",
    "correlation_id": "..."
  }
}
```

Tidak ada stack trace atau secret.

## 20. Notification Matrix

| Event | Kepala Gudang | Staff Admin | Purchasing | Koperasi | App Admin |
|---|---|---|---|---|---|
| Purchase approval required | inbox + push | status | – | – | optional |
| Purchase approved/rejected | inbox | inbox | approved inbox | linked status bila relevan | – |
| Duplicate warning | inbox | inbox | optional | – | – |
| Cancellation requested | inbox + push | status | – | – | – |
| Request cancelled | inbox | inbox | inbox | linked status | – |
| Pickup requested | – | inbox/task | – | confirmation | – |
| Ready for pickup | – | task/status | – | inbox/push optional | – |
| Return submitted | – | task | – | confirmation | – |
| Return approval required | inbox + push | status | – | – | – |
| Return decision | inbox | inbox | – | inbox | – |
| User invited/suspended | security optional | – | – | affected user | app admin inbox |
| Security event high | super admin alert | affected | affected | affected | app admin alert |

## 21. Reporting dan Dashboard Requirements

### 21.1 Kepala Gudang

- pending approvals;
- critical stock/backorder;
- in-progress purchase per item;
- request aging;
- pickup ready/overdue;
- return status/attribution;
- stock movement summary;
- prediction history pada fase ML.

### 21.2 Staff Admin

- task queue;
- critical stock;
- request to prepare;
- QC pending;
- return verification;
- in-progress purchase totals.

### 21.3 Purchasing

- approved unprocessed request;
- grouping candidates;
- PO drafts/sent;
- receipts pending.

### 21.4 App Admin

- active/invited/suspended users;
- MFA compliance;
- active sessions/security events;
- role changes;
- tenant settings/audit.

### 21.5 Super Admin

- tenant status;
- app admin coverage;
- system health;
- failed jobs/integration;
- high security events;
- support sessions.

## 22. Search, Filter, Export

- server-side search/filter/pagination;
- tenant-scoped;
- stable query string;
- export permission khusus;
- export asynchronous untuk data besar;
- export file private dan expiring;
- export audit;
- no cross-tenant aggregate kecuali platform metric yang telah didefinisikan dan tidak membocorkan detail.

## 23. Acceptance Criteria by Epic

### Epic A — Foundation/Tenancy

- dua tenant dapat menggunakan sistem tanpa data leak;
- all tenant models scoped/policy-tested;
- super admin/app admin boundaries work;
- audit writes for admin actions;
- CI passes tenant matrix.

### Epic B — Auth/User Management

- Google login valid;
- no invitation denied;
- MFA enforced;
- app admin invite/suspend/change role;
- cannot assign super admin;
- session revoke works;
- security events/audit complete.

### Epic C — Catalog/Stock

- barcode scan in/out;
- atomic balance and ledger;
- critical stock;
- negative stock supported;
- reconciliation test;
- mobile UI.

### Epic D — Pickup

- Koperasi submit simple request;
- Staff check/prepare;
- Kepala approve/reject;
- ready schedule;
- stock out at completion;
- backorder auto-draft path.

### Epic E — Procurement

- manual/critical/backorder request;
- duplicate warning/override;
- approval;
- Purchasing inbox/group/PO;
- cancellation rules;
- receipt/QC/stock in;
- full traceability.

### Epic F — Return

- evidence upload;
- verification;
- approval;
- attribution rule;
- disposal no stock-in;
- replacement and repickup;
- purchase fallback.

### Epic G — Notifications/Dashboard

- persistent inbox;
- private real-time;
- push Kepala;
- role dashboards;
- failure fallback;
- no unauthorized notification target.

### Epic H — ML

- feature flag off by default;
- signed/authenticated API;
- no-history fallback 0;
- response validation;
- normal/direct conversion;
- auto-approved audit;
- Staff in-progress total updates;
- timeout/failure safe.

## 24. Testing Requirements

### 24.1 Mandatory Test Layers

- unit;
- feature;
- policy/authorization;
- tenant isolation;
- database concurrency;
- Livewire component;
- browser critical flow;
- accessibility smoke;
- API contract;
- performance/load;
- security scanning.

### 24.2 Critical End-to-End Scenarios

1. app admin invites every role;
2. Google + MFA login;
3. stock critical → request → approval → PO → receipt/QC → stock;
4. Koperasi pickup available;
5. Koperasi pickup backorder;
6. duplicate request continued/cancelled;
7. Staff cancellation approved/rejected;
8. Kepala direct cancellation before/after PO sent;
9. return approved/rejected;
10. return replacement available/unavailable;
11. direct prediction auto-approved;
12. tenant A attempts all tenant B URLs/files/search;
13. concurrent stock/pickup submissions;
14. push failure with inbox success;
15. ML timeout/malformed/mismatched response.

### 24.3 Definition of Done

Feature selesai hanya bila:

- requirement ID linked;
- acceptance criteria pass;
- happy/error/permission/tenant tests exist;
- audit/notification verified;
- migration/backfill/rollback considered;
- UI responsive/accessibility checked;
- observability added;
- docs updated;
- no critical/high security issue;
- PR reviewed.

## 25. Implementation Roadmap

### Phase 0 — Foundation and Security

- repository/bootstrap;
- Laravel starter kit;
- PostgreSQL/Redis;
- CI;
- tenant context;
- ACL foundation;
- audit/outbox/idempotency;
- security headers/secrets/logging;
- factories base;
- Laravel Boost dan agent skills.

### Phase 1 — Identity/User

- Google Sign-In;
- invitation;
- MFA/passkey/TOTP;
- super/app admin;
- user lifecycle;
- session/device;
- impersonation foundation.

### Phase 2 — Catalog/Inventory

- items/barcodes/units/locations/suppliers;
- stock ledger/balance;
- critical/negative stock;
- scanner;
- reconciliation;
- dummy scenarios.

### Phase 3 — Pickup/Approval

- Koperasi form;
- Staff task;
- availability/backorder;
- release approval;
- schedule/completion.

### Phase 4 — Procurement

- purchase request;
- duplicate/cancellation;
- batching/grouping;
- PO;
- receipt/QC;
- stock in.

### Phase 5 — Returns

- submission/evidence;
- verification;
- approval/attribution;
- disposal/replacement/repickup.

### Phase 6 — Notification/Dashboard/Hardening

- inbox/realtime/push;
- role dashboards/reporting;
- performance/security/load;
- backup/restore;
- production readiness.

### Phase 7 — ML API

- Python service;
- gateway/security/contract;
- prediction pages;
- conversions;
- monitoring/model lifecycle.

ML tidak boleh dipindahkan lebih awal tanpa sign-off.

## 26. Success Metrics

Metric awal yang harus diinstrumentasikan:

- waktu median/p95 approval;
- persentase request duplicate warning;
- jumlah duplicate override;
- stock ledger reconciliation mismatch;
- waktu Koperasi dari request sampai ready;
- return resolution time;
- failed/overdue task;
- scan vs manual input ratio;
- active user/MFA compliance;
- cross-tenant denial/security events;
- notification delivery success;
- ML adoption, error, latency, dan recommendation adjustment pada fase ML.

Target numerik ditetapkan setelah baseline pilot tersedia.

## 27. Risks dan Mitigation

| Risk | Dampak | Mitigasi |
|---|---|---|
| Tenant leak | Kritis | Policies, scoping, DB constraints/RLS, automated tests |
| Over-privileged app admin | Tinggi | role boundary, permission allowlist, audit, step-up |
| Stock race/lost update | Tinggi | atomic transaction, versioning, ledger, reconciliation |
| Approval replay | Tinggi | terminal state, idempotency, optimistic concurrency |
| Upload malware/data leak | Tinggi | private storage, validation, scan/re-encode, signed URL |
| Push outage | Sedang | persistent inbox and retry |
| Google outage | Sedang | controlled break-glass policy |
| Complex UI for Koperasi | Tinggi adoption | user testing, minimal steps, accessibility |
| Grouping loses traceability | Tinggi | allocation table and immutable source links |
| ML scope creep | Tinggi | feature gate, last phase, strict boundary |
| AI agent introduces insecure shortcut | Tinggi | AGENTS.md, Boost, skills, CI/security review |
| Audit storage growth | Sedang | retention/partition/archive strategy |
| Rule FR-32 misunderstood | Tinggi | explicit rule version and test |

## 28. Open Decisions

Open decisions tercantum di `BATASAN.md`. Implementasi modul terkait tidak dimulai sebelum pertanyaan yang memengaruhi schema/workflow diputuskan.

## 29. Developer dan AI Agent Requirements

Setiap developer/agent wajib:

1. membaca seluruh dokumen utama;
2. menginstall `mattpocock/skills` dengan `npx skills@latest add mattpocock/skills` dan memilih `setup-matt-pocock-skills`;
3. menjalankan `/setup-matt-pocock-skills` pada agent;
4. menginstall Laravel Boost dengan `composer require laravel/boost --dev` dan `php artisan boost:install`;
5. menjaga `.agent`, `AGENTS.md`, dan `.ai/guidelines`;
6. bekerja dari requirement/ticket kecil;
7. menulis test sebelum/bersamaan dengan implementasi;
8. menjalankan quality gates;
9. tidak mengubah scope/security/architecture diam-diam;
10. meminta review untuk perubahan role, tenant, stock, approval, file, auth, atau integration.

## 30. Final Release Gate

Sebelum production:

- seluruh FR fase aktif ditelusuri ke test;
- security production checklist pass;
- tenant isolation matrix pass;
- role matrix disetujui;
- backup restore drill pass;
- stock reconciliation pass;
- audit verified;
- load test target utama pass;
- accessibility critical pages pass;
- incident runbook siap;
- monitoring/alerting aktif;
- ML tetap disabled sampai Phase 7 review.

## 31. Prinsip Implementasi Inti

1. Seluruh data operasional wajib memiliki `warehouse_id`, kecuali data platform yang secara eksplisit global.
2. Menyembunyikan tombol di UI bukan authorization. Setiap route, controller/action, policy, query, job, broadcast channel, export, dan file download harus memverifikasi akses.
3. Controller tipis; validasi di Form Request; authorization di Policy/Gate; aturan bisnis di Action/Service; query kompleks di Query Object/Repository terarah.
4. Transisi status hanya melalui service/action yang tervalidasi dan ditulis dalam transaksi database.
5. Semua approval, penolakan, pembatalan, perubahan role/permission, login berisiko, impersonation, export, dan akses lintas tenant dicatat di audit log.
6. Implementasi ML tidak boleh dimulai sebelum seluruh fase inti stabil, dites, dan diterima.
7. Tidak ada fitur dianggap selesai tanpa test sukses, authorization test, tenant isolation test, audit evidence, error handling, dan dokumentasi.

## 32. Kontribusi: Branch dan Pull Request

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
