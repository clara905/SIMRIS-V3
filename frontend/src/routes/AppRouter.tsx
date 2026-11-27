import { BrowserRouter, Routes, Route, Navigate } from 'react-router-dom';
import { AuthLayout } from '@/layouts/AuthLayout';
import { DashboardLayout } from '@/layouts/DashboardLayout';
import { ProtectedRoute } from './ProtectedRoute';

import { LoginPage } from '@/pages/auth/LoginPage';
import { DashboardPage } from '@/pages/dashboard/DashboardPage';
import { AssetsPage } from '@/pages/assets/AssetsPage';
import { RiskAssessmentPage } from '@/pages/risk-assessment/RiskAssessmentPage';
import { RiskTreatmentPage } from '@/pages/risk-treatment/RiskTreatmentPage';
import { 
  ReportsPage, 
  AssetReport, 
  RiskReport, 
  TreatmentReport, 
  DocumentationReport 
} from '@/pages/reports';
import { SettingsPage } from '@/pages/settings/SettingsPage';
import { DocumentationDashboard } from '@/pages/documentation/DocumentationDashboard';
import { DocumentLibrary } from '@/pages/documentation/DocumentLibrary';
import { DocumentDetail } from '@/pages/documentation/DocumentDetail';
import { SOPList } from '@/pages/sop/SOPList';
import { SOPDetail } from '@/pages/sop/SOPDetail';

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
            <Route path="/dokumentasi" element={<DocumentationDashboard />} />
            <Route path="/dokumentasi/library" element={<DocumentLibrary />} />
            <Route path="/dokumentasi/library/:id" element={<DocumentDetail />} />
            <Route path="/sop" element={<SOPList />} />
            <Route path="/sop/:id" element={<SOPDetail />} />
            <Route path="/laporan" element={<ReportsPage />} />
            <Route path="/laporan/aset" element={<AssetReport />} />
            <Route path="/laporan/risiko" element={<RiskReport />} />
            <Route path="/laporan/penanganan" element={<TreatmentReport />} />
            <Route path="/laporan/dokumentasi" element={<DocumentationReport />} />
            
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
