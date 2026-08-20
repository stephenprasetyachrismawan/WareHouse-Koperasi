---
sidebar_position: 7
title: Provisioning Server
---

# Provisioning Server (VPS Development)

Runbook ini mendokumentasikan **apa saja yang harus ada/dilakukan di VPS** supaya `wh.stevewithcode.net` bisa menerima deploy dari GitHub Actions dan melayani traffic — sesuai kondisi yang **benar-benar berjalan sekarang** (diverifikasi langsung di server). Ini bukan panduan dev lokal — untuk itu lihat [Instalasi](./instalasi).

Prinsip inti (lihat [CI/CD](./ci-cd) dan `CD.md`): **GitHub yang build, VPS cuma jalankan.** VPS ini tidak pernah `git clone`, `composer install`, `npm run build`, atau `docker build` sebagai bagian dari deployment normal.

## 1. Sistem operasi & Docker

- OS: **Amazon Linux 2023**.
- **Docker Engine** terinstall dan berjalan sebagai service (`systemctl enable/start docker`) — bukan Docker Desktop, bukan Sail.
- **Docker Compose v2** (plugin `docker compose`).

## 2. User `deploy`

Deployment tidak berjalan sebagai `root` atau user login utama:

```bash
sudo useradd -m deploy
sudo usermod -aG docker deploy
```

User `deploy` harus ada di grup `docker` supaya bisa menjalankan `docker compose` tanpa `sudo` — GitHub Actions masuk lewat SSH non-interaktif, tidak ada TTY untuk prompt password.

## 3. Struktur direktori `/srv/warehouse-koperasi`

```text
/srv/warehouse-koperasi/
└── deploy/                    # dimiliki user `deploy`, mode 750
    ├── .env                   # SECRET — dibuat manual sekali, tidak pernah di-commit
    ├── .deploy.lock           # flock lock file, dibuat otomatis
    ├── compose.yaml           # disalin dari deploy/compose.yaml repo ini
    ├── deploy-development.sh  # disalin dari deploy/ repo ini
    ├── rollback-development.sh
    ├── current-digest.env     # ditulis otomatis tiap deploy sukses
    ├── previous-digest.env    # ditulis otomatis sebelum tiap deploy
    └── sqlite-backups/        # backup SQLite otomatis, dibuat sebelum tiap migrasi
```

Setup awal (sekali saja):

```bash
sudo mkdir -p /srv/warehouse-koperasi/deploy/sqlite-backups
sudo chown -R deploy:deploy /srv/warehouse-koperasi/deploy
sudo chmod 750 /srv/warehouse-koperasi/deploy
```

Lalu, sebagai user `deploy`, salin `deploy/compose.yaml`, `deploy/deploy-development.sh`, `deploy/rollback-development.sh` dari repo ini, dan buat `.env` berisi isian nyata dari `.env.docker.example` — `APP_KEY`, `REVERB_APP_SECRET`, `GOOGLE_CLIENT_SECRET` harus nilai sungguhan, bukan placeholder, dan file ini tidak pernah masuk Git.

:::warning Jebakan volume Docker
Docker menyimpan SQLite di named volume `warehouse-koperasi-sqlite`, mount ke `storage/database` (bukan `database/` — direktori itu sudah berisi file migrasi bawaan image, kalau volume di-mount langsung ke situ akan menimpanya). Volume baru yang dibuat Docker root directory-nya sendiri juga harus di-`chown` ke user yang menjalankan `php-fpm` di dalam container, atau SQLite akan gagal menulis (`readonly database`) — bukan cukup `chown` filenya saja.
:::

## 4. Cloudflare Tunnel (`cloudflared`)

Traffic publik masuk lewat **Cloudflare Tunnel**, bukan port yang dibuka langsung ke internet — `deploy/compose.yaml` sengaja mem-publish port `web` (8000) dan `reverb` (8080) hanya ke `127.0.0.1`.

- Binary: `/usr/local/bin/cloudflared`, dijalankan sebagai systemd service `cloudflared.service`.
- Tunnel ini **token-based** (dikelola dari Cloudflare dashboard), service-nya jalan dengan `--token-file`:

```ini
# /etc/systemd/system/cloudflared.service
[Service]
ExecStart=/usr/bin/cloudflared --no-autoupdate --config /etc/cloudflared/config.yml tunnel run --token-file /etc/cloudflared/token
Restart=on-failure
```

- **Urutan ingress rule penting**: rule path-specific (`^/(app|apps)(/.*)?$` → port 8080, untuk WebSocket Reverb) harus terdaftar **sebelum** rule catch-all hostname (→ port 8000) untuk hostname yang sama — kalau terbalik, catch-all menelan semua request termasuk yang harusnya ke Reverb, handshake WebSocket gagal 502.
- Karena token-based, ingress rule yang berlaku bisa jadi dikelola dari Cloudflare dashboard, bukan murni `/etc/cloudflared/config.yml` lokal — verifikasi lewat `journalctl -u cloudflared -f` kalau perilakunya tidak sesuai file config.
- `REVERB_ALLOWED_ORIGINS` **wajib host-only, tanpa skema** (`wh.stevewithcode.net`, bukan `https://wh.stevewithcode.net`).

## 5. Kredensial di GitHub (Environment `development`)

Deploy dipicu dari GitHub Actions lewat GitHub **Environment** bernama `development`, menyimpan 4 secret:

| Secret | Isi |
|---|---|
| `DEPLOY_HOST` | Hostname/IP VPS |
| `DEPLOY_USER` | `deploy` |
| `DEPLOY_SSH_KEY` | Private key SSH — tulis dengan `printf '%s\n'`, bukan `printf '%s'`, saat menyimpannya (GitHub men-strip newline akhir, parser OpenSSH butuh itu) |
| `DEPLOY_KNOWN_HOSTS` | Output `ssh-keyscan` untuk host ini |

Ada satu repository **variable** (bukan secret) bernama `REVERB_APP_KEY` — dipakai sebagai Docker build ARG saat build image.

## 6. Deploy pertama kali

1. Pastikan `.env` di `/srv/warehouse-koperasi/deploy/` lengkap dan benar.
2. Jalankan workflow `ci-cd.yml` lewat `workflow_dispatch` dengan `run_deploy=true` (lihat [CI/CD](./ci-cd)).
3. Verifikasi manual: `docker compose -f /srv/warehouse-koperasi/deploy/compose.yaml ps` (semua `Up`), buka `https://wh.stevewithcode.net`.

## 7. Yang sengaja TIDAK ada di server ini

- Tidak ada PHP/Composer/Node native — semuanya di dalam image Docker.
- Tidak ada PostgreSQL/Redis — development runtime pakai SQLite + database-backed cache/queue/session.
- Tidak ada reverse proxy (nginx/Caddy) di level OS yang mendengarkan port publik — itu peran Cloudflare Tunnel.
