---
sidebar_position: 6
title: Kontribusi
---

# Kontribusi

## Alur branch & pull request

Semua perubahan masuk lewat pull request, tidak pernah langsung push ke `main` — `main` diproteksi GitHub ruleset dan menolak push langsung maupun force-push.

```text
branch baru dari main
  → commit
  → push
  → buka Pull Request
  → GitHub Actions (lihat halaman CI/CD)
  → semua required check hijau
  → merge lewat PR
```

Konvensi penamaan branch dan pesan commit yang lebih detail ada di `PRD.md`.

## Sebelum membuat PR

Jalankan gate kualitas lokal dari halaman [Menjalankan Aplikasi & Testing](./menjalankan-aplikasi) — ini bukan pengganti CI, tapi menghindari siklus perbaikan yang lambat karena baru ketahuan gagal di GitHub Actions.

Baca juga dokumen wajib sebelum menulis kode: `PRD.md`, `SECURITY-RULES.md`, dan `ARCHITECTURE.md`.

## Menambah dependency

Jangan menambah dependency baru tanpa kebutuhan yang jelas di PRD, pemeriksaan kompatibilitas, security review, dan persetujuan maintainer terlebih dulu.

## Menambah atau mengubah dokumentasi

Situs dokumentasi ini (yang sedang Anda baca) sumbernya adalah folder [`docs/`](https://github.com/stephenprasetyachrismawan/WareHouse-Koperasi/tree/main/docs) di root repo — cukup edit file `.md`/`.mdx` di sana, GitHub Actions otomatis build & publish ulang ke GitHub Pages setiap ada perubahan yang di-merge ke `main`. Tidak perlu menyentuh isi folder `docs-site/` kecuali sedang mengubah tema/konfigurasi situsnya sendiri.
