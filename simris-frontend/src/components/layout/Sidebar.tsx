
import {
  LayoutDashboard,
  Building2,
  Users,
  Package,
  CalendarDays,
  ClipboardList,
  ShieldAlert,
  ListChecks,
  ShieldCheck,
  FileText,
  ChartNoAxesCombined,
  History,
  X,
} from "lucide-react";
import { NavLink } from "react-router-dom";

interface SidebarProps {
  open: boolean;
  onClose: () => void;
  collapsed?: boolean;
}

const navigation = [
  {
    label: "DASHBOARD",
    items: [
      { name: "Dashboard", path: "/", icon: LayoutDashboard },
    ],
  },
  {
    label: "MANAJEMEN ORGANISASI",
    items: [
      { name: "Organisasi", path: "/organisasi", icon: Building2 },
      { name: "Pengguna", path: "/pengguna", icon: Users },
    ],
  },
  {
    label: "MANAJEMEN ASET",
    items: [
      { name: "Data Aset", path: "/aset", icon: Package },
    ],
  },
  {
    label: "MANAJEMEN RISIKO",
    items: [
      { name: "Periode Penilaian", path: "/periode", icon: CalendarDays },
      { name: "Asesmen Risiko", path: "/asesmen", icon: ClipboardList },
      { name: "Identifikasi Risiko", path: "/risiko", icon: ShieldAlert },
      { name: "Penanganan Risiko", path: "/penanganan", icon: ListChecks },
    ],
  },
  {
    label: "DOKUMENTASI & KONTROL",
    items: [
      { name: "Kontrol Keamanan", path: "/kontrol", icon: ShieldCheck },
      { name: "Dokumen & SOP", path: "/dokumen-sop", icon: FileText },
    ],
  },
  {
    label: "PELAPORAN",
    items: [
      { name: "Laporan", path: "/laporan", icon: ChartNoAxesCombined },
      { name: "Log Audit", path: "/audit", icon: History },
    ],
  },
];

export default function Sidebar({
  open,
  onClose,
  collapsed = false,
}: SidebarProps) {
  return (
    <>
      {open && (
        <button
          className="sidebar-overlay"
          onClick={onClose}
          aria-label="Tutup navigasi"
        />
      )}

      <aside
        className={`sidebar ${open ? "sidebar-open" : ""} ${
          collapsed ? "sidebar-collapsed" : ""
        }`}
      >
        <div className="sidebar-brand">
          <div className="brand-logo">
            <ShieldCheck size={25} />
          </div>

          {!collapsed && (
            <div className="brand-text">
              <h1>SIMRIS</h1>
              <span>Risk Management System</span>
            </div>
          )}

          <button
            className="sidebar-close"
            onClick={onClose}
            aria-label="Tutup sidebar"
          >
            <X size={20} />
          </button>
        </div>

        <div className="sidebar-divider" />

        <nav className="sidebar-navigation">
          {navigation.map((section) => (
            <div className="nav-section" key={section.label}>
              {!collapsed && (
                <p className="nav-section-label">{section.label}</p>
              )}

              {section.items.map((item) => {
                const Icon = item.icon;

                return (
                  <NavLink
                    key={item.path}
                    to={item.path}
                    end={item.path === "/"}
                    onClick={onClose}
                    title={collapsed ? item.name : undefined}
                    className={({ isActive }) =>
                      `nav-link ${isActive ? "nav-link-active" : ""}`
                    }
                  >
                    <Icon size={19} strokeWidth={1.8} />
                    {!collapsed && <span>{item.name}</span>}
                  </NavLink>
                );
              })}
            </div>
          ))}
        </nav>

        <div className="sidebar-footer">
          <div className="sidebar-footer-accent" />
          {!collapsed && (
            <div>
              <strong>SIMRIS</strong>
              <span>ISO 27005 Risk Management</span>
              <small>Frontend Development</small>
            </div>
          )}
        </div>
      </aside>
    </>
  );
}
