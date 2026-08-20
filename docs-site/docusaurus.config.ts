import {themes as prismThemes} from 'prism-react-renderer';
import type {Config} from '@docusaurus/types';
import type * as Preset from '@docusaurus/preset-classic';

// This runs in Node.js - Don't use client-side code here (browser APIs, JSX...)

const config: Config = {
  title: 'WareHouse Koperasi',
  tagline: 'Dokumentasi teknis dan panduan pengguna aplikasi manajemen gudang koperasi desa',
  favicon: 'img/favicon.ico',

  future: {
    v4: true,
  },

  // Situs ini di-deploy ke GitHub Pages sebagai project page.
  url: 'https://stephenprasetyachrismawan.github.io',
  baseUrl: '/WareHouse-Koperasi/',

  organizationName: 'stephenprasetyachrismawan',
  projectName: 'WareHouse-Koperasi',

  onBrokenLinks: 'throw',

  markdown: {
    mermaid: true,
    hooks: {
      onBrokenMarkdownLinks: 'warn',
    },
  },
  themes: ['@docusaurus/theme-mermaid'],

  i18n: {
    defaultLocale: 'id',
    locales: ['id'],
  },

  presets: [
    [
      'classic',
      {
        // Kontennya bukan di docs-site/docs, tapi di docs/ pada root repo --
        // itu tetap satu-satunya sumber Markdown/MDX di repo ini, tidak
        // diduplikasi. Lihat README di root docs-site/ untuk penjelasan.
        docs: {
          path: '../docs',
          sidebarPath: './sidebars.ts',
          editUrl: 'https://github.com/stephenprasetyachrismawan/WareHouse-Koperasi/edit/main/docs/',
        },
        blog: false,
        theme: {
          customCss: './src/css/custom.css',
        },
      } satisfies Preset.Options,
    ],
  ],

  themeConfig: {
    image: 'img/docusaurus-social-card.jpg',
    colorMode: {
      respectPrefersColorScheme: true,
    },
    navbar: {
      title: 'WareHouse Koperasi',
      logo: {
        alt: 'WareHouse Koperasi Logo',
        src: 'img/logo.svg',
      },
      items: [
        {
          type: 'docSidebar',
          sidebarId: 'panduanPenggunaSidebar',
          position: 'left',
          label: 'Panduan Pengguna',
        },
        {
          type: 'docSidebar',
          sidebarId: 'panduanDeveloperSidebar',
          position: 'left',
          label: 'Panduan Developer',
        },
        {
          type: 'docSidebar',
          sidebarId: 'rekayasaOperasionalSidebar',
          position: 'left',
          label: 'Rekayasa & Operasional',
        },
        {
          href: 'https://wh.stevewithcode.net',
          label: 'Buka Aplikasi',
          position: 'right',
        },
        {
          href: 'https://github.com/stephenprasetyachrismawan/WareHouse-Koperasi',
          label: 'GitHub',
          position: 'right',
        },
      ],
    },
    footer: {
      style: 'dark',
      links: [
        {
          title: 'Dokumentasi',
          items: [
            {label: 'Panduan Pengguna', to: '/docs/panduan-pengguna/pendahuluan'},
            {label: 'Panduan Developer', to: '/docs/panduan-developer/pendahuluan'},
            {label: 'Rekayasa & Operasional', to: '/docs/rekayasa-operasional/production-readiness'},
          ],
        },
        {
          title: 'Proyek',
          items: [
            {label: 'Aplikasi', href: 'https://wh.stevewithcode.net'},
            {label: 'GitHub', href: 'https://github.com/stephenprasetyachrismawan/WareHouse-Koperasi'},
            {label: 'CI/CD (CI.md)', href: 'https://github.com/stephenprasetyachrismawan/WareHouse-Koperasi/blob/main/CI.md'},
          ],
        },
      ],
      copyright: `WareHouse Koperasi © ${new Date().getFullYear()} — dibuat untuk Koperasi Desa Merah Putih.`,
    },
    prism: {
      theme: prismThemes.github,
      darkTheme: prismThemes.dracula,
    },
  } satisfies Preset.ThemeConfig,
};

export default config;
