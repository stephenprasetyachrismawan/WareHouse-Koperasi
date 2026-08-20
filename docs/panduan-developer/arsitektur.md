---
sidebar_position: 4
title: Ringkasan Arsitektur
---

# Ringkasan Arsitektur

Ini adalah ringkasan singkat untuk orientasi awal. Untuk detail lengkap dan keputusan yang mengikat, `ARCHITECTURE.md` di root repo tetap jadi rujukan otoritatif — halaman ini hanya peta supaya Anda tahu bagian mana yang harus dibaca duluan.

## Modular monolith

Aplikasinya adalah satu aplikasi Laravel (bukan microservices), tapi disusun sebagai modul-modul dengan tanggung jawab yang jelas dan terpisah:

- **Platform** — hal-hal lintas tenant (Super Admin, kesehatan sistem).
- **IdentityAccess** — user, autentikasi, role, permission.
- **Warehouses** — entitas tenant itu sendiri dan keanggotaannya.
- **Catalog** — master data barang.
- **Inventory** — saldo stok dan pergerakannya.
- **Procurement** — purchase request, grouping, PO, penerimaan, QC.
- **Pickup** — permintaan pengambilan barang oleh Koperasi.
- **Returns** — retur barang dan penggantiannya.
- **Approvals** — mekanisme approval yang dipakai bersama beberapa modul.
- **Notifications** — inbox, channel, dan outbox notifikasi.
- **Audit** — jejak audit.
- **Predictions** — bagian yang terhubung ke layanan ML eksternal.

## Tenancy

Setiap tabel yang menyimpan data milik satu warehouse selalu punya kolom `warehouse_id`, dan setiap query yang menyentuh data tenant harus discope ke warehouse aktif pengguna yang sedang login — ini aturan yang tidak boleh dilanggar (lihat `SECURITY-RULES.md`). Resolusi warehouse aktif dan scoping database dijelaskan detail di `ARCHITECTURE.md` §9.

## Stock ledger

Stok tidak disimpan sebagai satu angka yang di-update di tempat — setiap perubahan stok tercatat sebagai baris ledger (append-only), dan saldo dihitung/direkonsiliasi dari ledger itu. Ini yang membuat riwayat pergerakan stok (lihat [Panduan Pengguna → Inventaris](/docs/panduan-pengguna/inventaris-dan-gudang)) selalu bisa ditelusuri sampai ke sumbernya. Detail lengkap ada di `ARCHITECTURE.md` §11.

## State machine

Purchase Request, Pickup Request, Return, dan Approval masing-masing berjalan sebagai state machine dengan status yang jelas (mis. alur Pickup: `Draft → Submitted → ... → Completed`, lihat [Panduan Pengguna → Pickup](/docs/panduan-pengguna/pickup)). Aturan transisi antar status ada di `ARCHITECTURE.md` §12.

## Real-time & notifikasi

Notifikasi dan update status dikirim real-time lewat **Laravel Reverb** (WebSocket). Setiap notifikasi juga tersimpan permanen di inbox pengguna (bukan cuma toast yang hilang), jadi bisa dibuka lagi kapan saja. Detail ada di `ARCHITECTURE.md` §18 dan §20.

## Struktur kode

```text
app/
  Actions/       — satu class = satu operasi tulis/mutasi (dipakai dari Livewire/Controller)
  Domain/        — query object untuk baca data kompleks (mis. dashboard per role)
  Enums/         — status/tipe sebagai backed enum, bukan string bebas
  Http/Controllers/
  Livewire/      — komponen UI per fitur, dikelompokkan per modul
  Models/
  Policies/      — authorization per model
```

Controller dibuat setipis mungkin — logika mutasi ada di Actions, logika baca kompleks ada di query object di `app/Domain`. Detail penuhnya ada di `ARCHITECTURE.md` §6–§8.
