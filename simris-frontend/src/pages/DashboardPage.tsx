
import {
  Boxes,
  ShieldAlert,
  ClipboardCheck,
  Clock3,
  ArrowUpRight,
  Activity,
  ShieldCheck,
} from "lucide-react";

const statistics = [
  {
    title: "Total Aset",
    description: "Aset yang terdaftar",
    icon: Boxes,
  },
  {
    title: "Total Risiko",
    description: "Risiko teridentifikasi",
    icon: ShieldAlert,
  },
  {
    title: "Asesmen Aktif",
    description: "Asesmen berjalan",
    icon: ClipboardCheck,
  },
  {
    title: "Menunggu Review",
    description: "Perlu tindak lanjut",
    icon: Clock3,
  },
];

export default function DashboardPage() {
  return (
    <div className="page-content">
      <section className="page-heading">
        <div>
          <span className="eyebrow">RINGKASAN SISTEM</span>
          <h1>Dashboard</h1>
          <p>
            Pantau pengelolaan aset dan risiko organisasi melalui SIMRIS.
          </p>
        </div>

        <div className="dashboard-date">
          <Activity size={16} />
          <span>Data sistem</span>
        </div>
      </section>

      <section className="statistics-grid">
        {statistics.map((item) => {
          const Icon = item.icon;

          return (
            <article className="stat-card" key={item.title}>
              <div className="stat-card-top">
                <div className="stat-icon">
                  <Icon size={21} />
                </div>
                <ArrowUpRight size={17} className="stat-arrow" />
              </div>

              <h2>—</h2>
              <h3>{item.title}</h3>
              <p>{item.description}</p>
            </article>
          );
        })}
      </section>

      <section className="dashboard-grid">
        <article className="content-card">
          <div className="card-heading">
            <div>
              <h2>Aktivitas Terbaru</h2>
              <p>Riwayat aktivitas pengguna dalam sistem</p>
            </div>
            <Activity size={19} />
          </div>

          <div className="empty-state">
            <div className="empty-icon">
              <Activity size={25} />
            </div>
            <strong>Belum ada data aktivitas</strong>
            <p>
              Aktivitas akan ditampilkan setelah integrasi dengan Laravel API.
            </p>
          </div>
        </article>

        <article className="content-card">
          <div className="card-heading">
            <div>
              <h2>Ringkasan Risiko</h2>
              <p>Distribusi tingkat risiko</p>
            </div>
            <ShieldAlert size={19} />
          </div>

          <div className="empty-state">
            <div className="empty-icon">
              <ShieldAlert size={25} />
            </div>
            <strong>Belum ada data risiko</strong>
            <p>
              Visualisasi risiko akan tersedia setelah data asesmen terhubung.
            </p>
          </div>
        </article>
      </section>

      <div className="dashboard-note">
        <ShieldCheck size={18} />
        <span>
          SIMRIS — Sistem Manajemen Risiko berbasis ISO 27005.
        </span>
      </div>
    </div>
  );
}
