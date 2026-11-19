
import { BrowserRouter, Route, Routes } from "react-router-dom";
import AppLayout from "./layouts/AppLayout";
import DashboardPage from "./pages/DashboardPage";
import ModulePage from "./pages/ModulePage";

const modules = [
  {
    path: "organisasi",
    title: "Organisasi",
    description: "Pengelolaan unit organisasi, pegawai, dan penempatan.",
  },
  {
    path: "pengguna",
    title: "Pengguna",
    description: "Administrasi akun, pengajuan pendaftaran, role, dan scope.",
  },
  {
    path: "aset",
    title: "Data Aset",
    description: "Pengelolaan sumber, kategori, klasifikasi, dan data aset.",
  },
  {
    path: "periode",
    title: "Periode Penilaian",
    description: "Pengelolaan periode penilaian risiko setiap satker.",
  },
  {
    path: "asesmen",
    title: "Asesmen Risiko",
    description: "Pengelolaan seri asesmen, versi, dan ruang lingkup.",
  },
  {
    path: "risiko",
    title: "Identifikasi Risiko",
    description: "Identifikasi ancaman, kerentanan, aset, dan area dampak.",
  },
  {
    path: "penanganan",
    title: "Penanganan Risiko",
    description: "Pengelolaan rencana penanganan, PIC, dan progres.",
  },
  {
    path: "kontrol",
    title: "Kontrol Keamanan",
    description: "Pengelolaan standar dan kontrol keamanan.",
  },
  {
    path: "dokumen-sop",
    title: "Dokumen & SOP",
    description: "Pengelolaan dokumen, versi SOP, dan review.",
  },
  {
    path: "laporan",
    title: "Laporan",
    description: "Rekapitulasi asesmen, risiko, dan penanganan.",
  },
  {
    path: "audit",
    title: "Log Audit",
    description: "Penelusuran aktivitas dan riwayat audit sistem.",
  },
];

function NotFoundPage() {
  return (
    <div className="not-found">
      <h1>404</h1>
      <p>Halaman yang kamu cari tidak ditemukan.</p>
      <a href="/">Kembali ke Dashboard</a>
    </div>
  );
}

export default function App() {
  return (
    <BrowserRouter>
      <Routes>
        <Route element={<AppLayout />}>
          <Route index element={<DashboardPage />} />

          {modules.map((module) => (
            <Route
              key={module.path}
              path={module.path}
              element={
                <ModulePage
                  title={module.title}
                  description={module.description}
                />
              }
            />
          ))}
        </Route>

        <Route path="*" element={<NotFoundPage />} />
      </Routes>
    </BrowserRouter>
  );
}
