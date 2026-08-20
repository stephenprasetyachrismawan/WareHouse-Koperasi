---
sidebar_position: 6
title: Pengadaan & Pembelian
---

# Pengadaan & Pembelian

## Alur pengadaan

```text
Purchase Request → Approval → Grouping → Purchase Order → Send to Supplier → Goods Receipt → QC → Stock Update
```

## Staff Admin — membuat purchase request

1. Klik menu **"Purchase Request"**.
2. Klik **"Create"** dan tambahkan item yang perlu dibeli.
3. Pilih urgensi: Low, Normal, High, atau Emergency.
4. Sistem akan memperingatkan jika ada request serupa yang masih in-progress.
5. Klik **"Submit"** untuk mengirim ke approval.

## Kepala Gudang — menyetujui purchase request

1. Klik menu **"Approval Procurement"**.
2. Lihat detail item, jumlah, dan informasi stok saat ini.
3. Klik **"Approve"** atau **"Reject"** (dengan catatan).

## Purchasing — grouping, PO, & penerimaan

1. **Grouping** — alokasi item dari purchase request yang sudah di-approve.
2. **Buat PO** — pilih supplier, masukkan harga satuan, klik "Create Purchase Order".
3. **Kirim PO** — klik "Send to Supplier" di menu "Purchase Orders".
4. **Record Receipt** — setelah barang diterima, klik "Record Receipt" pada PO.

## Staff Admin — Quality Control (QC)

1. Klik menu **"Antrean QC"**.
2. Pilih receipt yang menunggu inspeksi.
3. Untuk setiap item: pilih Result (PASS/FAIL), Condition, Notes, Evidence.
4. Jika **PASS**: stok barang otomatis bertambah.
5. Jika **FAIL**: stok tidak masuk, barang perlu ditindaklanjuti.

## Pembatalan purchase request (Cancellation Request)

Selama PO belum dikirim ke supplier, purchase request masih bisa dibatalkan — tapi tetap lewat proses approval, bukan langsung batal:

1. Pembuat request membuka halaman detail purchase request dan klik **"Request Cancellation"**.
2. Kepala Gudang meninjau permintaan pembatalan di tab **"Cancellations"** pada menu **"Approval Procurement"**.
3. Klik **"Approve"** untuk membatalkan purchase request, atau **"Reject"** agar prosesnya tetap lanjut.

:::warning Perhatian
Setelah PO dikirim ke supplier, purchase request tidak dapat diajukan pembatalan lagi.
:::
