import { useState } from 'react';
import { Outlet } from 'react-router-dom';
import { Sidebar } from '@/components/navigation/Sidebar';
import { Topbar } from '@/components/navigation/Topbar';

export function DashboardLayout() {
  const [sidebarCollapsed, setSidebarCollapsed] = useState(false);
  const [mobileMenuOpen, setMobileMenuOpen] = useState(false);

  const toggleSidebar = () => setSidebarCollapsed(v => !v);
  const openMobileMenu = () => setMobileMenuOpen(true);
  const closeMobileMenu = () => setMobileMenuOpen(false);

  return (
    <div className="flex h-screen overflow-hidden bg-surface-default">
      {/* Sidebar */}
      <Sidebar 
        collapsed={sidebarCollapsed} 
        mobileOpen={mobileMenuOpen} 
        onMobileClose={closeMobileMenu} 
      />

      {/* Main content area */}
      <div className="flex min-w-0 flex-1 flex-col">
        <Topbar
          sidebarCollapsed={sidebarCollapsed}
          onToggleSidebar={toggleSidebar}
          onMobileMenuOpen={openMobileMenu}
        />

        <main className="flex-1 overflow-y-auto">
          <div className="container-padding py-6">
            <Outlet />
          </div>
        </main>
      </div>
    </div>
  );
}
