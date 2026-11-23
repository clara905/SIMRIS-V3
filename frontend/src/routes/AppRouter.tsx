import { BrowserRouter, Routes, Route, Navigate } from 'react-router-dom';
import { AuthLayout } from '@/layouts/AuthLayout';
import { DashboardLayout } from '@/layouts/DashboardLayout';
import { ProtectedRoute } from './ProtectedRoute';

import { LoginPage } from '@/pages/auth/LoginPage';
import { DashboardPage } from '@/pages/dashboard/DashboardPage';
import { AssetsPage } from '@/pages/assets/AssetsPage';
import { RiskAssessmentPage } from '@/pages/risk-assessment/RiskAssessmentPage';
import { RiskTreatmentPage } from '@/pages/risk-treatment/RiskTreatmentPage';
import { ReportsPage } from '@/pages/reports/ReportsPage';
import { SettingsPage } from '@/pages/settings/SettingsPage';

/**
 * AppRouter — STAFF (SATKER) role routes
 * 
 * Uses Indonesian route paths matching the navigation constants.
 * Admin-only routes (users, threats, vulnerabilities) are excluded.
 */
export function AppRouter() {
  return (
    <BrowserRouter>
      <Routes>
        {/* Auth routes */}
        <Route
          path="/login"
          element={
            <AuthLayout>
              <LoginPage />
            </AuthLayout>
          }
        />

        {/* Protected STAFF routes */}
        <Route element={<ProtectedRoute />}>
          <Route element={<DashboardLayout />}>
            {/* Workspace */}
            <Route path="/dashboard" element={<DashboardPage />} />
            <Route path="/asesmen-risiko" element={<RiskAssessmentPage />} />
            <Route path="/penanganan-risiko" element={<RiskTreatmentPage />} />
            
            {/* Data */}
            <Route path="/aset" element={<AssetsPage />} />
            <Route path="/dokumentasi" element={<div className="p-6 text-text-muted">Halaman Dokumentasi - Dalam pengembangan</div>} />
            <Route path="/sop" element={<div className="p-6 text-text-muted">Halaman SOP - Dalam pengembangan</div>} />
            <Route path="/laporan" element={<ReportsPage />} />
            
            {/* Account */}
            <Route path="/profil" element={<SettingsPage />} />
          </Route>
        </Route>

        {/* Fallback */}
        <Route path="*" element={<Navigate to="/dashboard" replace />} />
      </Routes>
    </BrowserRouter>
  );
}
