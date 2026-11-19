
import { Menu, Bell, ChevronRight, UserRound } from "lucide-react";
import { useLocation } from "react-router-dom";

interface TopbarProps {
  onMenuClick: () => void;
}

const pageNames: Record<string, string> = {
  "/": "Dashboard",
  "/organisasi": "Organisasi",
  "/pengguna": "Pengguna",
  "/aset": "Data Aset",
  "/periode": "Periode Penilaian",
  "/asesmen": "Asesmen Risiko",
  "/risiko": "Identifikasi Risiko",
  "/penanganan": "Penanganan Risiko",
  "/kontrol": "Kontrol Keamanan",
  "/dokumen-sop": "Dokumen & SOP",
  "/laporan": "Laporan",
  "/audit": "Log Audit",
};

export default function Topbar({ onMenuClick }: TopbarProps) {
  const location = useLocation();
  const currentPage = pageNames[location.pathname] ?? "Halaman";

  return (
    <header className="topbar">
      <div className="topbar-left">
        <button
          className="icon-button mobile-menu-button"
          onClick={onMenuClick}
          aria-label="Buka navigasi"
        >
          <Menu size={22} />
        </button>

        <div className="breadcrumb">
          <span>SIMRIS</span>
          <ChevronRight size={15} />
          <strong>{currentPage}</strong>
        </div>
      </div>

      <div className="topbar-right">
        <span className="environment-label">Sistem Manajemen Risiko</span>

        <button
          className="icon-button"
          aria-label="Notifikasi"
          title="Notifikasi akan diintegrasikan"
          disabled
        >
          <Bell size={19} />
        </button>

        <div className="topbar-divider" />

        <div className="user-placeholder">
          <div className="user-avatar">
            <UserRound size={19} />
          </div>
          <div className="user-info">
            <strong>Pengguna</strong>
            <span>Menunggu autentikasi</span>
          </div>
        </div>
      </div>
    </header>
  );
}
