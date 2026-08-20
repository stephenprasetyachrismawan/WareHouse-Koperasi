import type {ReactNode} from 'react';
import clsx from 'clsx';
import Link from '@docusaurus/Link';
import Heading from '@theme/Heading';
import styles from './styles.module.css';

type FeatureItem = {
  title: string;
  description: ReactNode;
  to: string;
};

const FeatureList: FeatureItem[] = [
  {
    title: 'Panduan Pengguna',
    to: '/docs/panduan-pengguna/pendahuluan',
    description: (
      <>
        Cara pakai fitur untuk Kepala Gudang, Staff Admin, Purchasing, dan
        Koperasi &mdash; login, dashboard, inventaris, pickup, pengadaan,
        retur, laporan, hingga notifikasi.
      </>
    ),
  },
  {
    title: 'Panduan Developer',
    to: '/docs/panduan-developer/pendahuluan',
    description: (
      <>
        Prasyarat, instalasi, menjalankan aplikasi secara lokal, testing,
        ringkasan arsitektur, dan alur CI/CD &mdash; ditulis untuk yang baru
        pertama kali menyentuh proyek ini.
      </>
    ),
  },
  {
    title: 'Rekayasa & Operasional',
    to: '/docs/rekayasa-operasional/production-readiness',
    description: (
      <>
        Catatan teknis internal: keamanan, performa, runbook operasional,
        keputusan arsitektur, dan bukti verifikasi dari setiap fase
        pengerjaan.
      </>
    ),
  },
];

function Feature({title, description, to}: FeatureItem) {
  return (
    <div className={clsx('col col--4')}>
      <div className="text--center padding-horiz--md">
        <Heading as="h3">
          <Link to={to}>{title}</Link>
        </Heading>
        <p>{description}</p>
      </div>
    </div>
  );
}

export default function HomepageFeatures(): ReactNode {
  return (
    <section className={styles.features}>
      <div className="container">
        <div className="row">
          {FeatureList.map((props, idx) => (
            <Feature key={idx} {...props} />
          ))}
        </div>
      </div>
    </section>
  );
}
