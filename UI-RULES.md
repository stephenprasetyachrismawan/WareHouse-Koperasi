# UI-RULES.md

## 1. Tujuan

Dokumen ini menetapkan aturan UI/UX wajib untuk Warehouse Koperasi SaaS. Antarmuka harus cepat dipahami, aman, responsif, dan cocok untuk pengguna dengan literasi teknologi beragam, termasuk operator gudang yang bekerja melalui ponsel/tablet dan pengelola koperasi yang menginginkan alur seperti formulir kertas.

## 2. Stack UI

Gunakan Laravel Livewire starter kit terbaru yang kompatibel dengan versi framework yang dikunci proyek. Baseline desain:

- Livewire 4;
- Blade;
- Flux UI;
- Tailwind CSS;
- Alpine hanya bila diperlukan dan tidak menggandakan state Livewire;
- Laravel Reverb/Echo untuk pembaruan real-time;
- browser camera API/library ter-review untuk barcode dan foto;
- icon set yang konsisten dan accessible.

Jangan mencampur React/Vue/Svelte ke aplikasi utama tanpa ADR. Komponen custom harus dibangun di atas pola Flux/Blade yang konsisten.

## 3. Prinsip Utama

1. **Task first.** Halaman memprioritaskan tindakan pekerjaan, bukan dekorasi.
2. **Role aware.** Navigasi dan dashboard berbeda menurut role, tetapi pola visual tetap konsisten.
3. **Mobile first untuk operator dan koperasi.** Seluruh alur penting harus selesai pada viewport kecil.
4. **Progressive disclosure.** Informasi lanjutan muncul saat diperlukan; form awal tetap ringkas.
5. **Safe by default.** Tindakan berisiko membutuhkan konteks, konfirmasi, dan alasan bila relevan.
6. **State is visible.** Status, pemilik tugas berikutnya, waktu terakhir, dan alasan penolakan harus jelas.
7. **No hidden failures.** Error, kegagalan upload, push, sinkronisasi, dan retry harus ditampilkan secara dapat ditindaklanjuti.
8. **Backend is authority.** UI boleh menyembunyikan action yang tidak diizinkan, tetapi tidak boleh dianggap sebagai kontrol akses.

## 4. Aksesibilitas

Target minimum: WCAG 2.2 Level AA.

Aturan wajib:

- seluruh action dapat diakses keyboard;
- focus indicator jelas;
- label form eksplisit, bukan placeholder saja;
- error terhubung ke field menggunakan atribut aksesibilitas;
- heading hierarkis;
- kontras teks/status memenuhi standar;
- warna tidak menjadi satu-satunya pembeda status;
- touch target minimal sekitar 44×44 px;
- teks utama minimal setara 16 px pada mobile;
- zoom browser tidak dinonaktifkan;
- modal memiliki focus trap dan dapat ditutup dengan keyboard;
- tabel memiliki header, caption/label, dan alternatif card pada mobile;
- toast kritis juga tercatat di inbox/halaman, tidak hilang setelah beberapa detik;
- loading menggunakan teks/status yang dapat dibaca screen reader.

## 5. Layout

### 5.1 Desktop

- Sidebar sebagai layout default untuk akun internal.
- Header menampilkan warehouse aktif, role aktif, inbox, status koneksi, dan menu akun.
- Konten menggunakan lebar maksimal yang menjaga keterbacaan; tabel operasional boleh full width.
- Breadcrumb digunakan pada halaman detail bertingkat.

### 5.2 Mobile dan Tablet

- Sidebar berubah menjadi drawer.
- Action primer berada di area mudah dijangkau.
- Tabel kompleks berubah menjadi cards atau horizontal scroll terkontrol.
- Filter utama dapat dibuka melalui bottom sheet/drawer.
- Scanner dan foto memprioritaskan mode layar penuh.
- Sticky action bar boleh digunakan untuk `Simpan`, `Setujui`, `Tolak`, atau `Catat Pengeluaran`, tetapi tidak menutupi konten/error.

### 5.3 Warehouse Context

Warehouse aktif harus selalu terlihat untuk user multi-warehouse. Perubahan warehouse:

- membutuhkan action eksplisit;
- membersihkan filter dan state page tenant sebelumnya;
- tidak membawa ID record dari tenant lama;
- menampilkan konfirmasi jika ada draft yang belum disimpan;
- menghasilkan audit event untuk role berprivilege tinggi bila perlu.

## 6. Navigasi Per Role

### 6.1 `super_admin`

Menu minimum:

- Platform Dashboard;
- Warehouses;
- App Admins;
- Platform Audit;
- Security Events;
- System Health;
- Support Access/Impersonation;
- Settings.

Menu transaksi tenant tidak ditampilkan kecuali sedang masuk ke support session yang jelas dan diaudit.

### 6.2 `app_admin`

Menu minimum:

- Warehouse Dashboard;
- Users;
- Invitations;
- Roles & Permissions;
- Warehouse Settings;
- Security & Sessions;
- Audit;
- optional operational menus hanya jika role operasional juga diberikan eksplisit.

### 6.3 Kepala Gudang

Menu minimum:

- Dashboard;
- Approval Inbox;
- Purchase Requests;
- Pickup Requests;
- Returns;
- Stock Monitoring;
- Predictions, hanya fase ML;
- Notifications;
- Audit view yang diizinkan.

### 6.4 Staff Admin

Menu minimum:

- Task Dashboard;
- Scan Stock In/Out;
- Stock;
- Critical Stock;
- Pickup Requests;
- Purchase Requests;
- Receipts & QC;
- Returns;
- Cancellation Requests;
- Notifications.

### 6.5 Purchasing

Menu minimum:

- Purchase Inbox;
- Request Grouping;
- Purchase Orders;
- Receipts;
- Suppliers;
- Notifications.

### 6.6 Koperasi

Menu harus sangat sederhana:

- Beranda;
- Request Barang;
- Retur Barang;
- Jadwal Pengambilan;
- Status Saya;
- Inbox;
- Akun.

## 7. Dashboard

### 7.1 Aturan Umum

- Dashboard berisi pekerjaan yang perlu tindakan, bukan hanya angka vanity.
- Setiap kartu KPI dapat diklik menuju daftar yang telah difilter.
- Waktu data terakhir dan status real-time ditampilkan.
- Angka sensitif hanya muncul sesuai permission.
- Empty state menjelaskan tindakan berikutnya.

### 7.2 Kepala Gudang

Prioritas:

1. approval menunggu;
2. request duplikat/peringatan;
3. stok kritis/backorder;
4. PO/request yang stagnan;
5. retur menunggu keputusan;
6. status operasional ringkas.

### 7.3 Staff Admin

Prioritas:

1. request pengambilan baru;
2. barang yang harus disiapkan;
3. QC penerimaan;
4. retur yang perlu diverifikasi;
5. critical stock;
6. total purchase request in progress per barang;
7. task overdue.

### 7.4 Purchasing

Prioritas:

1. request approved belum diproses;
2. grouping candidates;
3. PO draft;
4. PO sent menunggu barang;
5. penerimaan yang perlu dicatat.

### 7.5 Koperasi

Prioritas:

1. barang siap diambil;
2. request terbaru dan status;
3. retur terbaru dan status;
4. satu tombol besar untuk request barang;
5. satu tombol besar untuk retur.

## 8. Forms

### 8.1 Aturan Dasar

- Satu field memiliki satu label, helper text bila perlu, dan pesan error spesifik.
- Field wajib ditandai jelas.
- Validasi client-side hanya untuk respons cepat; server-side tetap wajib.
- Nilai tidak hilang saat validasi gagal.
- Simpan draft hanya untuk workflow yang memang mendukung `DRAFT`.
- Tombol submit disabled saat request berjalan untuk mencegah double-submit.
- Setelah submit sukses, tampilkan nomor referensi dan langkah berikutnya.
- Untuk action berisiko, tampilkan summary sebelum konfirmasi.

### 8.2 Form Koperasi Request Barang

Maksimal alur normal:

1. pilih barang;
2. masukkan jumlah;
3. optional catatan;
4. review ringkas;
5. submit.

Aturan:

- pencarian barang menggunakan nama/barcode yang ramah manusia;
- tidak menampilkan data supplier, harga internal, atau detail stok sensitif;
- jumlah memiliki unit jelas;
- dapat menambah beberapa item tanpa membuat halaman kompleks;
- tombol primer tunggal dan besar;
- duplicate/availability warning menggunakan bahasa sederhana.

### 8.3 Form Retur

Wajib:

- pilih request/pengeluaran asal bila tersedia;
- pilih barang dan jumlah;
- alasan dari daftar terkontrol plus catatan;
- foto bukti;
- preview foto;
- pernyataan bahwa barang diserahkan untuk pemeriksaan;
- nomor retur setelah submit.

### 8.4 Approval Form

- detail transaksi dan perubahan stok terlihat sebelum keputusan;
- approval tidak boleh hanya tombol tanpa konteks;
- `Setujui` dan `Tolak` tidak berdekatan secara membingungkan;
- penolakan wajib alasan;
- action memperlihatkan warehouse, pembuat, waktu, item, jumlah, sumber, dan peringatan;
- double approval menghasilkan pesan bahwa keputusan telah diambil, bukan error generik;
- untuk approval sensitif, gunakan step-up MFA sesuai security rules.

## 9. Tables dan Lists

- Default sort harus deterministik.
- Server-side pagination wajib untuk data besar.
- Filter state dapat dibagikan melalui query string bila tidak memuat secret.
- Kolom action tidak memenuhi layar; gunakan menu action untuk operasi sekunder.
- Bulk action hanya tersedia bila business rule mendukung.
- Baris selalu menampilkan identifier manusia dan status.
- Data yang telah terminal tidak dapat diedit melalui inline edit.
- Export mengikuti filter aktif dan meminta konfirmasi bila mengandung data sensitif.
- Mobile memakai card list dengan field paling penting dan action primer.

## 10. Status dan Badge

Gunakan label Bahasa Indonesia yang konsisten. Internal enum tetap bahasa Inggris/konstan.

Contoh mapping:

| Enum | Label UI |
|---|---|
| `DRAFT` | Draft |
| `WAITING_APPROVAL` | Menunggu Persetujuan |
| `APPROVED` | Disetujui |
| `AUTO_APPROVED` | Disetujui Otomatis |
| `REJECTED` | Ditolak |
| `CANCELLED` | Dibatalkan |
| `BACKORDERED` | Menunggu Stok |
| `READY_FOR_PICKUP` | Siap Diambil |
| `PO_SENT` | PO Dikirim |
| `GOODS_RECEIVED` | Barang Diterima |
| `COMPLETED` | Selesai |

Setiap badge memiliki icon/teks, tidak hanya warna. Status terminal dan non-terminal harus mudah dibedakan.

## 11. Barcode Scanner

- Scanner mendukung kamera belakang mobile dan scanner keyboard-wedge.
- Izin kamera diminta saat user memulai scan, bukan saat halaman dibuka.
- Tampilkan fallback input manual.
- Hasil scan harus dikonfirmasi dengan nama barang sebelum transaksi commit.
- Scan berulang memiliki debounce dan indikator jumlah.
- Barcode tidak dikenal memberi pilihan cari/manual, bukan membuat barang otomatis.
- Jumlah negatif atau nol ditolak.
- Kegagalan kamera memberikan instruksi yang jelas.
- Scanner tidak menyimpan frame video.

## 12. Foto dan Upload

- Preview sebelum upload.
- Tampilkan progress upload.
- Kompresi/normalisasi boleh dilakukan, tetapi bukti harus cukup jelas.
- Metadata lokasi EXIF dihapus kecuali ada requirement eksplisit.
- Tampilkan status scan malware/pemrosesan jika asynchronous.
- File gagal diproses tidak dianggap bukti valid.
- URL file tidak diekspos permanen; gunakan temporary signed URL.
- Tombol download mengikuti policy.

## 13. Notifikasi, Inbox, dan Real-Time

- Inbox adalah sumber notifikasi yang persisten.
- Push/toast adalah kanal tambahan.
- Notification item menampilkan tipe, ringkasan, waktu, warehouse, status baca, dan link aman.
- Link notifikasi yang tidak lagi dapat diakses harus menampilkan halaman aman, bukan bocor metadata.
- Unread count diperbarui real-time.
- Kehilangan koneksi real-time menampilkan indikator dan fallback refresh/polling.
- User dapat menandai satu atau semua sebagai terbaca sesuai permission.
- Notifikasi approval penting tidak dapat disembunyikan tanpa keputusan; boleh ditandai terbaca tetapi tetap ada pada task queue.

## 14. Error, Loading, dan Empty State

### Loading

- Gunakan skeleton untuk daftar/dashboard.
- Gunakan progress untuk upload/import.
- Jangan freeze seluruh halaman untuk action kecil.

### Error

- Pesan tidak membocorkan stack trace, SQL, token, path internal, atau tenant lain.
- Berikan correlation ID untuk error server.
- Error authorization menggunakan pesan netral.
- Conflict status menampilkan data terbaru dan tindakan aman.

### Empty State

- Jelaskan apakah data belum ada, filter terlalu ketat, atau user tidak memiliki permission.
- Berikan satu action relevan bila diizinkan.

## 15. Confirmation dan Destructive Actions

Konfirmasi wajib untuk:

- suspend user;
- perubahan role/permission;
- membatalkan request;
- menolak approval;
- mengirim PO ke supplier;
- menandai barang retur disposed;
- delete/archive master data;
- impersonation;
- export sensitif;
- regenerate recovery codes;
- revoke all sessions.

Konfirmasi harus menyebut objek dan dampak. Jangan menggunakan dialog “Are you sure?” tanpa konteks.

## 16. Security UX

- Login hanya menampilkan Google Sign-In dan jalur break-glass yang tidak menonjol/terpisah sesuai kebijakan.
- Setelah Google callback, user tanpa invitation mendapat pesan netral dan instruksi menghubungi admin.
- MFA enrollment menunjukkan recovery code sekali dan meminta acknowledgement.
- Session/device page memungkinkan revoke individual/all sessions.
- Impersonation menampilkan banner permanen, target tenant/user, waktu berakhir, dan tombol keluar.
- Sensitive action menampilkan step-up MFA modal.
- Permission editor menampilkan dampak dan mencegah `app_admin` memberikan permission platform.
- User tidak dapat menonaktifkan MFA sendiri jika policy tenant mewajibkan.

## 17. UI Screen Inventory

Minimum halaman yang harus tersedia:

### Platform

- platform dashboard;
- warehouse list/create/detail/suspend;
- app admin assignment;
- platform audit/security event list;
- impersonation request/history.

### Tenant Administration

- users list/create/invite/detail/edit/suspend;
- invitations list/resend/revoke;
- roles list/detail/permission matrix;
- warehouse settings;
- sessions/security;
- tenant audit.

### Authentication

- login;
- Google callback outcomes;
- MFA enrollment/challenge;
- recovery code;
- account recovery;
- verify email/invitation;
- session expired;
- access denied.

### Inventory

- items list/create/detail/edit/archive;
- barcode view/print;
- stock overview;
- stock ledger;
- scan in/out;
- minimum stock settings;
- critical stock;
- backorder list.

### Procurement

- purchase request list/create/detail;
- duplicate warning/review;
- approval detail;
- cancellation request;
- grouping workspace;
- PO list/create/detail/send;
- receipt list/create/detail;
- QC form/evidence.

### Pickup

- koperasi request form/list/detail;
- staff task/check availability;
- prepare items;
- approval release;
- pickup schedule;
- pickup completion/scan.

### Returns

- return form/list/detail;
- admin verification;
- damage evidence;
- approval;
- fault attribution;
- replacement/backorder;
- repickup schedule/completion.

### Notifications dan Reporting

- inbox;
- notification detail;
- role dashboards;
- operational reports/export yang disetujui;
- ML prediction pages pada fase terakhir.

## 18. Internationalization dan Format

- UI default Bahasa Indonesia.
- Simpan timestamp dalam UTC; tampilkan dalam timezone warehouse/user.
- Gunakan format tanggal konsisten, misalnya `4 Agustus 2026, 11.53 WIB`.
- Angka dan satuan mengikuti locale Indonesia.
- Identifier teknis tidak ditampilkan sebagai label utama.
- Semua string UI harus menggunakan file translation, tidak hard-coded di komponen.

## 19. Performance UI

- Dashboard awal menampilkan konten kritis secepat mungkin.
- Hindari N+1 query pada Livewire render.
- Debounce search.
- Lazy-load tab berat dan foto.
- Gunakan thumbnail, bukan file resolusi penuh pada list.
- Batasi payload komponen Livewire.
- Jangan menyimpan model Eloquent besar sebagai public Livewire property.
- Gunakan pagination dan query projection.

## 20. Testing UI

Minimum:

- feature test setiap route/action;
- Livewire component tests;
- browser tests untuk login, MFA, user management, scan fallback, approval, purchase, pickup, return, dan cross-tenant denial;
- accessibility checks pada halaman kritis;
- responsive checks untuk mobile/tablet/desktop;
- screenshot regression untuk komponen status dan form penting bila tooling tersedia;
- test double-submit, stale state, upload gagal, websocket offline, dan session expired.

## 21. Definition of Done UI

Sebuah layar tidak selesai sebelum:

- semua state loading/error/empty/success tersedia;
- responsive pada target viewport;
- keyboard dan screen reader semantics diuji;
- permission dan tenant denial diuji backend;
- copy Bahasa Indonesia konsisten;
- event audit/notification sesuai requirement;
- screenshot PR tersedia;
- tidak ada console error;
- tidak ada informasi sensitif pada HTML, Livewire payload, log browser, atau URL.
