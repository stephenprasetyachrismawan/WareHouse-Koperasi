---
sidebar_position: 7
title: Retur Barang
---

# Retur Barang

## Alur retur

```text
Submitted → Admin Verified → Waiting Approval → Approved → Replacement Pending → Ready for Repickup → Completed
```

:::info Catatan
Status bisa berubah menjadi **REJECTED** jika ditolak oleh Kepala Gudang.
:::

## Koperasi — mengajukan retur

1. Klik menu **"Retur Barang"**.
2. Klik **"Buat Retur"** dan pilih item dari pickup yang sudah selesai.
3. Masukkan jumlah yang ingin diretur.
4. Pilih alasan: Barang Rusak, Cacat, Salah Kirim, atau Lainnya.
5. **Upload foto bukti** (wajib, maksimal 5MB, format jpg/jpeg/png).
6. Review di halaman konfirmasi, lalu klik **"Submit"**.

## Staff Admin — memverifikasi retur

1. Klik menu **"Verifikasi Retur"**.
2. Pilih retur yang berstatus **SUBMITTED**.
3. Scan barcode barang untuk memverifikasi.
4. Masukkan jumlah yang diverifikasi dan upload foto kondisi barang.
5. Klik **"Verify"**, lalu **"Submit for Approval"**.

## Kepala Gudang — menyetujui retur

1. Klik menu **"Keputusan Retur"**.
2. Lihat detail retur: bukti foto, hasil verifikasi, asal kesalahan.
3. Klik **"Approve"** atau **"Reject"** (dengan alasan).

## Penggantian barang (otomatis)

1. Barang lama otomatis didisposisi (stok tidak masuk kembali).
2. Sistem memeriksa ketersediaan stok pengganti.
3. Jika stok tersedia: pickup pengganti langsung dibuat.
4. Jika stok tidak tersedia: purchase request baru dibuat terlebih dahulu.
5. Staff Admin menyelesaikan serah terima di menu **"Tugas Penggantian Retur"**.

:::tip
Foto bukti wajib diupload saat mengajukan retur. Tanpa foto, retur tidak dapat diproses.
:::
