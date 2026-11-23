import { NavLink, useLocation } from 'react-router-dom';
import { X, ShieldCheck } from 'lucide-react';
import { NAV_ITEMS } from '@/constants/navigation';
import { clsx } from '@/utils/clsx';

interface SidebarProps {
  collapsed: boolean;
  mobileOpen: boolean;
  onMobileClose: () => void;
}

export function Sidebar({ collapsed, mobileOpen, onMobileClose }: SidebarProps) {
  const location = useLocation();

  const isActive = (path: string) =>
    location.pathname === path || location.pathname.startsWith(path + '/');

  const sidebarContent = (
    <div className="flex h-full flex-col">
      {/* Logo */}
      <div className="flex h-16 shrink-0 items-center gap-3 border-b border-border px-4">
        <div className="flex h-8 w-8 shrink-0 items-center justify-center rounded-lg bg-brand">
          <ShieldCheck className="h-4 w-4 text-white" />
        </div>
        {!collapsed && (
          <div className="min-w-0">
            <p className="truncate text-sm font-bold tracking-tight text-text-primary">SIMRIS V3</p>
            <p className="truncate text-[10px] text-text-muted uppercase tracking-wider">Risk Management</p>
          </div>
        )}
      </div>

      {/* Navigation */}
      <nav className="flex-1 overflow-y-auto py-4 px-2">
        <ul className="space-y-0.5">
          {NAV_ITEMS.map((item) => {
            const active = isActive(item.path);
            return (
              <li key={item.path}>
                <NavLink
                  to={item.path}
                  title={collapsed ? item.label : undefined}
                  className={clsx(
                    'group flex items-center gap-3 rounded-md px-3 py-2.5 text-sm font-medium transition-colors',
                    active
                      ? 'bg-brand-muted text-brand-dark'
                      : 'text-text-secondary hover:bg-surface-muted hover:text-text-primary',
                    collapsed && 'justify-center',
                  )}
                >
                  <item.icon
                    className={clsx(
                      'h-4 w-4 shrink-0 transition-colors',
                      active ? 'text-brand' : 'text-text-muted group-hover:text-text-secondary',
                    )}
                  />
                  {!collapsed && <span className="truncate">{item.label}</span>}
                  {!collapsed && active && (
                    <span className="ml-auto h-1.5 w-1.5 shrink-0 rounded-full bg-brand" />
                  )}
                </NavLink>
              </li>
            );
          })}
        </ul>
      </nav>

      {/* Footer */}
      {!collapsed && (
        <div className="shrink-0 border-t border-border px-4 py-3">
          <p className="text-[10px] text-text-muted">
            © 2026 SIMRIS V3
          </p>
        </div>
      )}
    </div>
  );

  return (
    <>
      {/* Desktop sidebar */}
      <aside
        className={clsx(
          'hidden lg:flex lg:flex-col',
          'fixed inset-y-0 left-0 z-30',
          'border-r border-border bg-white transition-all duration-200',
          collapsed ? 'w-16' : 'w-[240px]',
        )}
      >
        {sidebarContent}
      </aside>

      {/* Mobile drawer */}
      {mobileOpen && (
        <>
          <div
            className="fixed inset-0 z-40 bg-black/40 lg:hidden"
            onClick={onMobileClose}
            aria-hidden="true"
          />
          <aside className="fixed inset-y-0 left-0 z-50 flex w-[240px] flex-col border-r border-border bg-white lg:hidden">
            <div className="absolute right-3 top-4">
              <button
                onClick={onMobileClose}
                className="rounded p-1 text-text-muted hover:bg-surface-muted"
                aria-label="Tutup menu"
              >
                <X className="h-4 w-4" />
              </button>
            </div>
            {sidebarContent}
          </aside>
        </>
      )}
    </>
  );
}
