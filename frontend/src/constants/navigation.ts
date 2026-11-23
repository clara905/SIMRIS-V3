import {
  LayoutDashboard,
  ClipboardList,
  ShieldCheck,
  Server,
  FileText,
  BookOpen,
  BarChart2,
  UserCircle,
} from 'lucide-react';
import type { LucideIcon } from 'lucide-react';

// ─── Types ───────────────────────────────────────────────────────────────────

export interface NavItem {
  label: string;
  path:  string;
  icon:  LucideIcon;
}

export interface NavGroup {
  group: string;
  items: NavItem[];
}

// ─── Staff navigation ─────────────────────────────────────────────────────────
//
// Scoped to the STAFF role only.
// Other roles will have their own nav definitions when implemented.
//
// Paths must match the route definitions in AppRouter.tsx exactly.

export const STAFF_NAV: NavGroup[] = [
  {
    group: 'Workspace',
    items: [
      { label: 'Dashboard',         path: '/dashboard',          icon: LayoutDashboard },
      { label: 'Asesmen Risiko',    path: '/asesmen-risiko',     icon: ClipboardList   },
      { label: 'Penanganan Risiko', path: '/penanganan-risiko',  icon: ShieldCheck     },
    ],
  },
  {
    group: 'Data',
    items: [
      { label: 'Aset',         path: '/aset',         icon: Server    },
      { label: 'Dokumentasi',  path: '/dokumentasi',  icon: FileText  },
      { label: 'SOP',          path: '/sop',          icon: BookOpen  },
      { label: 'Laporan',      path: '/laporan',      icon: BarChart2 },
    ],
  },
  {
    group: 'Akun',
    items: [
      { label: 'Profil Saya', path: '/profil', icon: UserCircle },
    ],
  },
];

// Flat list for breadcrumb resolution (Topbar uses this)
export const NAV_ITEMS: NavItem[] = STAFF_NAV.flatMap(g => g.items);
