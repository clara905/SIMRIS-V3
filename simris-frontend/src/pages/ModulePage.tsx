
import { Construction, ArrowLeft } from "lucide-react";
import { Link } from "react-router-dom";

interface ModulePageProps {
  title: string;
  description: string;
}

export default function ModulePage({
  title,
  description,
}: ModulePageProps) {
  return (
    <div className="page-content">
      <section className="page-heading">
        <div>
          <span className="eyebrow">MODUL SIMRIS</span>
          <h1>{title}</h1>
          <p>{description}</p>
        </div>
      </section>

      <section className="module-placeholder">
        <div className="placeholder-icon">
          <Construction size={32} />
        </div>

        <h2>Modul sedang dipersiapkan</h2>

        <p>
          Halaman ini akan dikembangkan dan dihubungkan dengan Laravel API
          sesuai alur bisnis dan kewenangan SIMRIS.
        </p>

        <Link to="/" className="back-link">
          <ArrowLeft size={16} />
          Kembali ke Dashboard
        </Link>
      </section>
    </div>
  );
}
