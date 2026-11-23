import { useState } from 'react';
import { Outlet } from 'react-router-dom';
import { Sidebar } from '@/components/navigation/Sidebar';
import { Topbar } from '@/components/navigation/Topbar';

export function DashboardLayout() {
  const [sidebarCollapsed, setSidebarCollapsed] = useState(false);
  const [mobileMenuOpen,   setMobileMenuOpen  ] = useState(false);

  return (
    <div className="flex h-screen overflow-hidden bg-surface-base">

      {/* ── Sidebar ───────────────────────────────────────────────
          Renders as position:fixed on desktop, overlay drawer on mobile.
          It lives outside the normal flow on both breakpoints.
      ─────────────────────────────────────────────────────────── */}
      <Sidebar
        collapsed={sidebarCollapsed}
        mobileOpen={mobileMenuOpen}
        onMobileClose={() => setMobileMenuOpen(false)}
      />

      {/* ── Content column ────────────────────────────────────────
          Strategy: use margin-left that matches the sidebar width.
          `transition-[margin-left]` keeps the animation in lockstep
          with the sidebar's own `transition-all duration-200`.

          `min-w-0` is critical: without it, a flex child's minimum
          width defaults to its content's intrinsic width, which can
          exceed the available space and cause horizontal overflow.

          On mobile (< lg) the sidebar is an overlay, so margin-left
          must be 0 — controlled by the responsive class override.
      ─────────────────────────────────────────────────────────── */}
      <div
        className={[
          // Base: take all remaining space, never overflow
          'flex flex-col w-full min-w-0 overflow-hidden',
          // Animate the margin change in sync with the sidebar transition
          'transition-[margin-left] duration-200',
          // Desktop: push right by sidebar width
          sidebarCollapsed
            ? 'lg:ml-16'       // collapsed  → 64px  (w-16)
            : 'lg:ml-[240px]', // expanded   → 240px
          // Mobile: sidebar is an overlay, no offset needed
          // (lg: prefix already handles this — below lg = no lg:ml-* applied)
        ].join(' ')}
      >
        <Topbar
          sidebarCollapsed={sidebarCollapsed}
          onToggleSidebar={() => setSidebarCollapsed(v => !v)}
          onMobileMenuOpen={() => setMobileMenuOpen(true)}
          notificationCount={2}
        />

        <main className="flex-1 overflow-y-auto">
          {/*
            max-w-screen-xl: caps line length on very wide monitors.
            w-full + min-w-0: ensures the inner container never pushes
            wider than the content column itself.
          */}
          <div className="w-full min-w-0 mx-auto max-w-screen-xl p-6">
            <Outlet />
          </div>
        </main>
      </div>
    </div>
  );
}
