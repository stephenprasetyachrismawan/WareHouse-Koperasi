---
sidebar_position: 8
title: Agent Prompt
---

# Agent Prompt

Panduan memakai AI coding agent (Claude Code, Cursor, Codex, dsb.) untuk mengerjakan repo ini dengan aman dan produktif. Isinya sintesis dari `AGENTS.md`, Laravel Boost guidelines, `CI.md`, `CD.md`, dan `SECURITY-RULES.md` — dokumen itu tetap sumber otoritatif penuh; halaman ini merangkumnya jadi satu prompt siap pakai.

## Kenapa ini penting

Repo ini punya banyak aturan non-negotiable yang **tidak jelas** hanya dari membaca kode (tenant scoping, alur approval, larangan bypass). Kalau agent tidak diberi konteks ini di awal, hasil kerjanya bisa lolos test tapi tetap melanggar aturan bisnis/keamanan kritis.

## Prompt siap pakai

Salin blok ini sebagai instruksi awal untuk agent Anda sebelum meminta perubahan kode apa pun:

```text
Kamu bekerja di repo WareHouse-Koperasi — aplikasi Laravel 13 + Livewire,
multi-tenant (satu warehouse = satu tenant), untuk manajemen gudang koperasi.

WAJIB dibaca sebelum mengubah kode apa pun, urutannya:
1. PRD.md            — kebutuhan bisnis
2. BATASAN.md         — ruang lingkup & yang sengaja di luar lingkup
3. SECURITY-RULES.md  — aturan keamanan wajib (normatif, level WAJIB/DILARANG)
4. ARCHITECTURE.md    — desain sistem, modul, tenancy, state machine
5. UI-RULES.md        — aturan antarmuka
6. CI.md              — gate merge yang BENAR-BENAR aktif di GitHub Actions
7. CD.md              — alur deployment yang BENAR-BENAR aktif

CI.md dan CD.md adalah kebenaran normatif tentang bagaimana perubahan di-merge
dan di-deploy — baca meski perubahanmu tidak menyentuh .github/workflows/ atau
deploy/. Kalau memori/percakapan sebelumnya menyiratkan proses CI/CD berbeda
dari isi CI.md/CD.md saat ini, dokumen repo yang menang.

Aturan non-negotiable (pelanggaran = perubahan ditolak):
1. Setiap record milik tenant di-scope oleh warehouse_id.
2. Setiap model/action sensitif pakai Policy/Gate + cek tenant.
3. Visibilitas UI TIDAK PERNAH jadi satu-satunya lapisan otorisasi.
4. Controller tetap tipis — mutasi lewat Actions, baca kompleks lewat Query Object.
5. Perubahan status pakai Action eksplisit, bukan CRUD status generik.
6. Pergerakan stok = ledger append-only + update saldo atomik.
7. Keputusan approval bersifat immutable dan diaudit.
8. File evidence privat dan dilindungi Policy.
9. Queue job, cache key, lock, broadcast, notifikasi, export, search — semua tenant-aware.
10. Fitur Machine Learning TETAP nonaktif sampai fase yang direncanakan.
11. Tidak ada network call di dalam DB transaction.
12. Dependency baru butuh justifikasi + review.
13. Jangan pernah menampilkan secret/data production/token/signed URL di
    output, log, fixture, atau commit.

Larangan eksplisit (contoh pola yang DILARANG):
- Model::find($id) tanpa scoping tenant untuk data tenant.
- Mempercayai warehouse_id/actor ID/approver ID/status dari input klien.
- $request->all() untuk create/update model.
- $guarded = [] pada model sensitif tanpa persetujuan eksplisit.
- Gate::before universal yang membuat super_admin bypass semua authorization.
- Menyimpan file evidence retur/QC di disk publik.
- Mengedit/menghapus riwayat ledger stok atau approval.
- Membuat PO dari purchase request yang belum di-approve.
- Membiarkan Staff Admin melakukan approval final.

Alur kerja WAJIB untuk setiap perubahan (AGENTS.md §5 & §11):
1. Branch baru dari main (fix/... atau feat/...) — JANGAN commit langsung ke main.
2. Tulis test yang gagal dulu (RED).
3. Implementasikan perubahan sekecil mungkin (GREEN).
4. Jalankan test fokus, vendor/bin/pint --test, npm run build.
5. Push branch, buka Pull Request (gh pr create).
6. Cek hasil GitHub Actions sungguhan (gh pr checks / gh run view).
7. Jangan merge selama ada required check merah/pending.
8. Merge hanya setelah semua required check hijau.
9. Kalau perubahan berdampak ke environment yang di-deploy, verifikasi hasil
   deploy sungguhan (digest, health check, smoke test) sebelum melaporkan
   selesai.

Setiap model/endpoint tenant baru wajib punya test untuk: akses diizinkan di
warehouse aktif, ditolak di warehouse lain, ditolak dengan membership tidak
aktif, allow/deny per role, scope route-model-binding, dan percobaan
mass-assignment warehouse_id/role/status/actor.
```

## Bagaimana memberi tugas ke agent setelah prompt di atas

```text
Tugas: [deskripsi singkat perilaku yang diinginkan, dalam bahasa bisnis —
mis. "Kepala Gudang perlu bisa melihat riwayat pembatalan purchase request
di halaman approval"]

Modul terdampak: [Procurement/Approvals/dst.]
Role terdampak: [role mana yang boleh/tidak boleh]
Ada perubahan skema? [ya/tidak, kalau ya jelaskan]
```

Agent yang sudah diberi prompt di atas akan otomatis: baca dokumen wajib dulu, cek boundary modul & security, buat branch, TDD, jalankan quality gate lokal, push, buka PR, tunggu CI, baru lapor selesai — tanpa perlu diingatkan.

## Tooling wajib

```bash
composer require laravel/boost --dev
php artisan boost:install
php artisan boost:update   # setiap kali dependency berubah

npx skills@latest add mattpocock/skills
# lalu jalankan /setup-matt-pocock-skills di agent
```

## Yang harus dicantumkan agent di setiap Pull Request

Requirement/ticket ID, alasan perubahan, tenant/role/workflow terdampak, dampak skema & migrasi, dampak keamanan, test yang ditambah + command yang dijalankan, screenshot (untuk perubahan UI), catatan rollout/rollback, risiko yang belum terselesaikan. PR yang menyentuh autentikasi, otorisasi, resolusi tenant, stok, approval, akses file, impersonasi, atau ML **wajib** review keamanan eksplisit.

## Kalau agent mengubah infrastruktur

PR yang menyentuh `.github/workflows/**`, `Dockerfile`, `deploy/**`, file compose, health endpoint, strategi registry GHCR, kontrak GitHub Environment, transport deployment, atau alur rollback — **wajib** review `CI.md` dan `CD.md` di PR yang sama.

## Rujukan lengkap

Halaman ini ringkasan operasional. Detail penuh dan normatif: `AGENTS.md`, `PRD.md`, `SECURITY-RULES.md`, `ARCHITECTURE.md`, `UI-RULES.md`, `CI.md`, `CD.md`, `.agent/WORKFLOW.md` di root repo, dan versi yang setara di [GitHub Wiki](https://github.com/stephenprasetyachrismawan/WareHouse-Koperasi/wiki/Agent-Prompt).
