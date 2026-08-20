---
sidebar_position: 1
title: Pendahuluan
---

# Pendahuluan untuk Developer

Selamat datang! Bagian ini untuk siapa pun yang ingin menjalankan, memodifikasi, atau berkontribusi pada **WareHouse Koperasi** secara lokal — baik Anda developer baru di tim ini, maupun kontributor eksternal.

## Apa aplikasinya

WareHouse Koperasi adalah aplikasi web **Laravel 13** (PHP 8.4) dengan **Livewire** untuk UI reaktif, **Fortify** untuk autentikasi, **Socialite** untuk login Google, dan **Reverb** untuk notifikasi real-time. Frontend memakai **Flux UI** + **Tailwind CSS v4**, dibundel dengan **Vite**.

Aplikasinya multi-tenant: setiap baris data tenant (barang, stok, purchase request, dst.) selalu terikat pada satu `warehouse_id`, dan setiap pengguna punya role per-warehouse (lihat [Panduan Pengguna → Dashboard & Role](/docs/panduan-pengguna/dashboard-dan-role) untuk daftar role dari sudut pandang pengguna akhir).

## Peta dokumen penting

Sebelum menulis kode, ada beberapa dokumen di root repo yang wajib dibaca lebih dulu:

| Dokumen | Isinya |
|---|---|
| `PRD.md` | Kebutuhan produk lengkap, alur bisnis, konvensi branch/PR, roadmap. |
| `ARCHITECTURE.md` | Keputusan arsitektur, modul, tenancy, transaksi, queue, deployment. |
| `SECURITY-RULES.md` | Aturan keamanan wajib — autentikasi, authorization, tenant isolation. |
| `UI-RULES.md` | Aturan antarmuka, aksesibilitas, pola layar, UX per role. |
| `AGENTS.md` | Instruksi wajib untuk developer maupun AI coding agent. |

Halaman-halaman berikutnya di panduan ini adalah versi ringkas dan lebih mudah diikuti dari dokumen-dokumen tersebut, khusus untuk onboarding cepat. Untuk detail lengkap dan keputusan yang mengikat, dokumen aslinya tetap jadi rujukan utama.
