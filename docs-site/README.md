# docs-site

Situs dokumentasi publik WareHouse Koperasi, dibangun dengan [Docusaurus](https://docusaurus.io/) dan MDX, di-deploy otomatis ke GitHub Pages.

**Penting:** folder ini hanya berisi aplikasi/tooling Docusaurus-nya (konfigurasi, tema, `package.json` sendiri, terpisah dari `package.json` root). **Konten dokumentasinya sendiri (file `.md`/`.mdx`) ada di [`../docs`](../docs) pada root repo**, bukan di `docs-site/docs` — dikonfigurasi lewat `path: '../docs'` di `docusaurus.config.ts`. Jadi menambah/mengubah dokumentasi cukup edit file di `docs/`, tidak perlu menyentuh folder ini sama sekali.

Struktur kategori di `docs/`:
- `docs/panduan-pengguna/` — cara pakai fitur untuk pengguna aplikasi (per role).
- `docs/panduan-developer/` — instalasi, menjalankan aplikasi, testing, arsitektur, CI/CD untuk developer baru.
- `docs/rekayasa-operasional/` — catatan teknis internal (keamanan, performa, runbook, ADR, bukti verifikasi) yang sudah ada sebelum situs ini dibuat, dipindah ke sini tanpa dihapus.

## Instalasi & development lokal

```bash
cd docs-site
npm install
npm start
```

## Build

```bash
npm run build
```

Menghasilkan situs statis di `docs-site/build/`.

## Deployment

**Jangan** pakai `npm run deploy` (metode `gh-pages` branch bawaan Docusaurus). Deployment situs ini sepenuhnya lewat GitHub Actions (`.github/workflows/docs-pages.yml`) yang otomatis build & publish ke GitHub Pages setiap ada perubahan di `docs/**` atau `docs-site/**` pada branch `main` — lihat workflow tersebut untuk detail.
