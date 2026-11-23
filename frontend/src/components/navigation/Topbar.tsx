import React, { useState, useRef, useEffect } from 'react';
import { useLocation, Link } from 'react-router-dom';
import {
  Menu,
  PanelLeftClose,
  PanelLeft,
  Bell,
  Search,
  ChevronRight,
  LogOut,
  User,
  Settings,
} from 'lucide-react';
import { useAuth } from '@/contexts/AuthContext';
import { NAV_ITEMS } from '@/constants/navigation';
import { clsx } from '@/utils/clsx';

interface TopbarProps {
  sidebarCollapsed: boolean;
  onToggleSidebar: () => void;
  onMobileMenuOpen: () => void;
  notificationCount?: number;
}

function getInitials(name: string): string {
  return name
    .split(' ')
    .slice(0, 2)
    .map((n) => n[0])
    .join('')
    .toUpperCase();
}

function useBreadcrumb(): { label: string; path: string }[] {
  const location = useLocation();
  const segments = location.pathname.split('/').filter(Boolean);
  const crumbs: { label: string; path: string }[] = [{ label: 'Dashboard', path: '/dashboard' }];

  let acc = '';
  for (const seg of segments) {
    acc += '/' + seg;
    const match = NAV_ITEMS.find((n) => n.path === acc);
    if (match && acc !== '/dashboard') {
      crumbs.push({ label: match.label, path: acc });
    }
  }
  return crumbs;
}

export function Topbar({ sidebarCollapsed, onToggleSidebar, onMobileMenuOpen, notificationCount = 0 }: TopbarProps) {
  const { user, logout } = useAuth();
  const breadcrumbs = useBreadcrumb();
  const [dropdownOpen, setDropdownOpen] = useState(false);
  const dropdownRef = useRef<HTMLDivElement>(null);

  useEffect(() => {
    function handler(e: MouseEvent) {
      if (dropdownRef.current && !dropdownRef.current.contains(e.target as Node)) {
        setDropdownOpen(false);
      }
    }
    document.addEventListener('mousedown', handler);
    return () => document.removeEventListener('mousedown', handler);
  }, []);

  const displayName = user?.pegawai?.nama ?? user?.username ?? 'Pengguna';

  return (
    <header className="flex h-16 shrink-0 items-center justify-between border-b border-border bg-white px-4 gap-4">
      {/* Left: menu toggle + breadcrumb */}
      <div className="flex items-center gap-3 min-w-0">
        {/* Mobile hamburger */}
        <button
          onClick={onMobileMenuOpen}
          className="rounded p-1.5 text-text-muted hover:bg-surface-muted lg:hidden"
          aria-label="Buka menu"
        >
          <Menu className="h-5 w-5" />
        </button>

        {/* Desktop collapse toggle */}
        <button
          onClick={onToggleSidebar}
          className="hidden rounded p-1.5 text-text-muted hover:bg-surface-muted lg:flex"
          aria-label={sidebarCollapsed ? 'Perluas sidebar' : 'Ciutkan sidebar'}
        >
          {sidebarCollapsed ? (
            <PanelLeft className="h-4 w-4" />
          ) : (
            <PanelLeftClose className="h-4 w-4" />
          )}
        </button>

        {/* Breadcrumb */}
        <nav aria-label="Breadcrumb" className="hidden items-center gap-1 md:flex">
          {breadcrumbs.map((crumb, i) => (
            <React.Fragment key={crumb.path}>
              {i > 0 && <ChevronRight className="h-3.5 w-3.5 shrink-0 text-text-muted" />}
              {i === breadcrumbs.length - 1 ? (
                <span className="max-w-[200px] truncate text-sm font-medium text-text-primary">
                  {crumb.label}
                </span>
              ) : (
                <Link to={crumb.path} className="max-w-[160px] truncate text-sm text-text-secondary hover:text-text-primary">
                  {crumb.label}
                </Link>
              )}
            </React.Fragment>
          ))}
        </nav>
      </div>

      {/* Right: search, notifications, user */}
      <div className="flex shrink-0 items-center gap-2">
        {/* Search (icon only on small screens) */}
        <button className="rounded p-1.5 text-text-muted hover:bg-surface-muted" aria-label="Cari">
          <Search className="h-4 w-4" />
        </button>

        {/* Notifications */}
        <div className="relative">
          <button
            className="rounded p-1.5 text-text-muted hover:bg-surface-muted"
            aria-label={`Notifikasi${notificationCount > 0 ? ` (${notificationCount} belum dibaca)` : ''}`}
          >
            <Bell className="h-4 w-4" />
          </button>
          {notificationCount > 0 && (
            <span className="absolute -right-0.5 -top-0.5 flex h-4 w-4 items-center justify-center rounded-full bg-brand text-[10px] font-bold text-white">
              {notificationCount > 9 ? '9+' : notificationCount}
            </span>
          )}
        </div>

        {/* User dropdown */}
        <div className="relative" ref={dropdownRef}>
          <button
            onClick={() => setDropdownOpen((v) => !v)}
            className={clsx(
              'flex items-center gap-2 rounded-md border border-transparent px-2 py-1.5 text-sm transition-colors',
              'hover:border-border hover:bg-surface-muted',
              dropdownOpen && 'border-border bg-surface-muted',
            )}
            aria-expanded={dropdownOpen}
            aria-haspopup="true"
          >
            <div className="flex h-7 w-7 shrink-0 items-center justify-center rounded-full bg-brand text-xs font-semibold text-white">
              {getInitials(displayName)}
            </div>
            <div className="hidden text-left md:block">
              <p className="max-w-[120px] truncate text-xs font-medium text-text-primary">{displayName}</p>
              <p className="text-[10px] text-text-muted">{user?.role?.nama ?? '—'}</p>
            </div>
          </button>

          {dropdownOpen && (
            <div className="absolute right-0 top-full mt-1 w-52 rounded-lg border border-border bg-white py-1 shadow-dropdown z-50">
              <div className="border-b border-border px-4 py-2.5">
                <p className="text-sm font-medium text-text-primary">{displayName}</p>
                <p className="text-xs text-text-muted">{user?.email}</p>
              </div>
              <Link
                to="/settings"
                onClick={() => setDropdownOpen(false)}
                className="flex items-center gap-2.5 px-4 py-2 text-sm text-text-secondary hover:bg-surface-muted hover:text-text-primary"
              >
                <User className="h-4 w-4" />
                Profil Saya
              </Link>
              <Link
                to="/settings"
                onClick={() => setDropdownOpen(false)}
                className="flex items-center gap-2.5 px-4 py-2 text-sm text-text-secondary hover:bg-surface-muted hover:text-text-primary"
              >
                <Settings className="h-4 w-4" />
                Pengaturan
              </Link>
              <div className="border-t border-border">
                <button
                  onClick={logout}
                  className="flex w-full items-center gap-2.5 px-4 py-2 text-sm text-status-error hover:bg-red-50"
                >
                  <LogOut className="h-4 w-4" />
                  Keluar
                </button>
              </div>
            </div>
          )}
        </div>
      </div>
    </header>
  );
}
