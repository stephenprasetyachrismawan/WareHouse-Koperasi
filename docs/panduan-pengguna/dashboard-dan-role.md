---
sidebar_position: 3
title: Dashboard & Role
---

# Dashboard & Role

## Dashboard

Dashboard menampilkan ringkasan data sesuai role Anda:

| Role | Yang ditampilkan |
|---|---|
| Super Admin | Warehouse aktif, cakupan admin, total pengguna |
| App Admin | Pengguna aktif, distribusi role, stok kritis |
| Kepala Gudang | Approval pending, stok kritis, backorder |
| Staff Admin | Tugas pickup, QC pending, verifikasi retur |
| Purchasing | Item perlu perhatian, goods receipt terbaru |
| Koperasi | Pickup siap ambil, status terakhir, kotak masuk |

## Role & hak akses

Sistem memiliki **5 role per-warehouse**, ditambah **1 hak akses khusus admin platform** (Super Admin) yang berlaku lintas warehouse:

| Role | Untuk siapa |
|---|---|
| **Super Admin** (`super_admin`) | Pemilik platform. Mengelola semua tenant, support pengguna, memantau kesehatan sistem. Ini **bukan** bagian dari 5 role per-warehouse — statusnya terpisah dari keanggotaan warehouse manapun. |
| **App Admin** (`app_admin`) | Administrator warehouse. Mengelola pengguna, role, dan pengaturan untuk warehouse-nya. |
| **Kepala Gudang** (`kepala_gudang`) | Approver. Menyetujui/menolak pembelian, pengeluaran, retur, dan pickup. |
| **Staff Admin** (`staff_admin`) | Operator. Mengelola stok, membuat purchase request, verifikasi retur, QC, menyiapkan pickup. |
| **Purchasing** (`purchasing`) | Koordinator pengadaan. Mengelola PO, grouping request, mencatat penerimaan, mengelola supplier. |
| **Koperasi** (`koperasi`) | Pelanggan. Membuat permintaan pickup, mengajukan retur, melihat status permintaan. |

### Ringkasan hak akses per modul

| Modul | Super Admin | App Admin | Kep. Gudang | Staff Admin | Purchasing | Koperasi |
|---|:---:|:---:|:---:|:---:|:---:|:---:|
| Dashboard | ✓ | ✓ | ✓ | ✓ | ✓ | ✓ |
| Manajemen Pengguna | ✓ | ✓ | — | — | — | — |
| Katalog Barang | ✓ | ✓ | Lihat | CRUD | Lihat | — |
| Saldo & Transaksi | ✓ | ✓ | Lihat | CRUD | Lihat | — |
| Lokasi & Rak | ✓ | ✓ | Lihat | CRUD | — | — |
| Request Pickup | ✓ | ✓ | — | — | — | Buat & Lihat |
| Approval Pickup | ✓ | ✓ | Setujui/Tolak | — | — | — |
| Penyiapan Pickup | ✓ | ✓ | — | Siapkan & Fulfill | — | — |
| Purchase Request | ✓ | ✓ | Lihat | Buat & Lihat | Lihat | — |
| Approval Pembelian | ✓ | ✓ | Setujui/Tolak | — | — | — |
| Grouping & PO | ✓ | ✓ | — | — | CRUD | — |
| Receipts & QC | ✓ | ✓ | — | QC | Record | — |
| Retur Barang | ✓ | ✓ | Setujui/Tolak | Verifikasi | — | Buat & Lihat |
| Laporan | — | — | Lihat & Export | Lihat & Export | Lihat & Export | — |

## Manajemen pengguna

Hanya **Super Admin** dan **App Admin** yang dapat mengelola pengguna.

1. **Melihat daftar pengguna** — klik menu "Manajemen Pengguna" di sidebar, gunakan kolom pencarian untuk mencari berdasarkan nama atau email.
2. **Membuat pengguna baru** — klik "Create"/"Tambah Pengguna", isi Nama, Email, Password, Role, lalu klik "Save".
3. **Mengubah status pengguna** — pengguna bisa diaktifkan atau di-suspend. Pengguna yang di-suspend tidak dapat login.

:::warning Peringatan
Super Admin tidak dapat memberikan hak akses platform (`super_admin`) kepada pengguna lain lewat aplikasi — hanya bisa dilakukan langsung di database.
:::
