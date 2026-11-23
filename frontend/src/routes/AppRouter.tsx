import { BrowserRouter, Routes, Route, Navigate } from 'react-router-dom';
import { AuthLayout } from '@/layouts/AuthLayout';
import { DashboardLayout } from '@/layouts/DashboardLayout';
import { ProtectedRoute } from './ProtectedRoute';

import { LoginPage } from '@/pages/auth/LoginPage';
import { DashboardPage } from '@/pages/dashboard/DashboardPage';
import { AssetsPage } from '@/pages/assets/AssetsPage';
import { ThreatsPage } from '@/pages/threats/ThreatsPage';
import { VulnerabilitiesPage } from '@/pages/vulnerabilities/VulnerabilitiesPage';
import { RiskAssessmentPage } from '@/pages/risk-assessment/RiskAssessmentPage';
import { RiskTreatmentPage } from '@/pages/risk-treatment/RiskTreatmentPage';
import { ReportsPage } from '@/pages/reports/ReportsPage';
import { UsersPage } from '@/pages/users/UsersPage';
import { SettingsPage } from '@/pages/settings/SettingsPage';

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

        {/* Protected app routes */}
        <Route element={<ProtectedRoute />}>
          <Route element={<DashboardLayout />}>
            <Route path="/dashboard" element={<DashboardPage />} />
            <Route path="/assets" element={<AssetsPage />} />
            <Route path="/threats" element={<ThreatsPage />} />
            <Route path="/vulnerabilities" element={<VulnerabilitiesPage />} />
            <Route path="/risk-assessment" element={<RiskAssessmentPage />} />
            <Route path="/risk-treatment" element={<RiskTreatmentPage />} />
            <Route path="/reports" element={<ReportsPage />} />
            <Route path="/users" element={<UsersPage />} />
            <Route path="/settings" element={<SettingsPage />} />
          </Route>
        </Route>

        {/* Fallback */}
        <Route path="*" element={<Navigate to="/dashboard" replace />} />
      </Routes>
    </BrowserRouter>
  );
}
