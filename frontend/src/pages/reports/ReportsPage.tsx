import { useEffect, useState } from 'react';
import { Link } from 'react-router-dom';
import { 
  FileText, 
  BarChart2, 
  PieChart as PieChartIcon, 
  TrendingUp, 
  Server,
  ShieldCheck,
  AlertTriangle,
  CheckCircle,
  Clock,
  BookOpen,
} from 'lucide-react';
import {
  BarChart, Bar, XAxis, YAxis, CartesianGrid, Tooltip, ResponsiveContainer,
  PieChart as RechartsPieChart, Pie, Cell, Legend,
} from 'recharts';
import { reportsService, type ReportDashboardStats } from '@/services/reports.service';
import { PageHeader } from '@/components/common/PageHeader';
import { Card, CardHeader } from '@/components/ui/Card';
import { StatCard } from '@/components/ui/StatCard';
import { Button } from '@/components/ui/Button';
import { Badge } from '@/components/ui/Badge';
import { PageLoader } from '@/components/ui/LoadingSpinner';

export function ReportsPage() {
  const [stats, setStats] = useState<ReportDashboardStats | null>(null);
  const [loading, setLoading] = useState(true);
  const [error, setError] = useState<string | null>(null);

  useEffect(() => {
    reportsService.getDashboardStats()
      .then(setStats)
      .catch((err) => {
        console.error('[ReportsPage] Failed to fetch dashboard stats:', err);
        setError('Gagal memuat data laporan. Silakan muat ulang halaman.');
      })
      .finally(() => setLoading(false));
  }, []);

  if (loading) return <PageLoader />;

  if (error) {
    return (
      <div className="flex min-h-[400px] items-center justify-center">
        <Card padding="md" className="max-w-md text-center">
          <AlertTriangle className="mx-auto h-12 w-12 text-status-error" />
          <h3 className="mt-4 text-lg font-semibold text-text-primary">Gagal Memuat Laporan</h3>
          <p className="mt-2 text-sm text-text-secondary">{error}</p>
          <Button
            variant="primary"
            className="mt-4"
            onClick={() => window.location.reload()}
          >
            Muat Ulang Halaman
          </Button>
        </Card>
      </div>
    );
  }

  const riskByLevel = stats?.risksByLevel || [];
  const treatmentProgress = stats?.treatmentsByStatus || [];
  const assetByCategory = (stats?.assetsByCategory || []).map(item => ({
    kategori: item.category,
    jumlah: item.count,
  }));

  return (
    <div className="space-y-6">
      <PageHeader 
        title="Laporan & Analitik" 
        description="Ringkasan dan analisis manajemen risiko keamanan informasi"
      />

      {/* Summary Stats */}
      <div className="grid grid-cols-1 gap-4 sm:grid-cols-2 xl:grid-cols-4">
        <StatCard
          label="Total Aset"
          value={stats?.totalAssets || 0}
          icon={Server}
          supporting="Aset terdaftar dalam sistem"
          variant="info"
        />
        <StatCard
          label="Risiko Teridentifikasi"
          value={stats?.totalRisks || 0}
          icon={AlertTriangle}
          supporting={`${stats?.criticalRisks || 0} risiko kritis`}
          variant="warning"
        />
        <StatCard
          label="Rencana Penanganan"
          value={stats?.totalTreatments || 0}
          icon={ShieldCheck}
          supporting={`${stats?.inProgressTreatments || 0} sedang berjalan`}
        />
        <StatCard
          label="Penanganan Selesai"
          value={stats?.completedTreatments || 0}
          icon={CheckCircle}
          supporting="Treatment berhasil diselesaikan"
          variant="success"
        />
      </div>

      {/* Quick Links */}
      <div className="grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-4">
        <Link to="/laporan/aset" className="block">
          <div className="flex flex-col gap-3 rounded-lg border border-border bg-white p-4 shadow-card hover:shadow-md transition-shadow">
            <div className="flex h-10 w-10 items-center justify-center rounded-lg bg-blue-100">
              <Server className="h-5 w-5 text-blue-700" />
            </div>
            <div className="flex-1">
              <p className="text-sm font-semibold text-text-primary">Laporan Aset</p>
              <p className="mt-0.5 text-xs text-text-secondary">Inventaris dan klasifikasi aset</p>
            </div>
          </div>
        </Link>

        <Link to="/laporan/risiko" className="block">
          <div className="flex flex-col gap-3 rounded-lg border border-border bg-white p-4 shadow-card hover:shadow-md transition-shadow">
            <div className="flex h-10 w-10 items-center justify-center rounded-lg bg-amber-100">
              <AlertTriangle className="h-5 w-5 text-amber-700" />
            </div>
            <div className="flex-1">
              <p className="text-sm font-semibold text-text-primary">Laporan Risiko</p>
              <p className="mt-0.5 text-xs text-text-secondary">Asesmen dan evaluasi risiko</p>
            </div>
          </div>
        </Link>

        <Link to="/laporan/penanganan" className="block">
          <div className="flex flex-col gap-3 rounded-lg border border-border bg-white p-4 shadow-card hover:shadow-md transition-shadow">
            <div className="flex h-10 w-10 items-center justify-center rounded-lg bg-green-100">
              <ShieldCheck className="h-5 w-5 text-green-700" />
            </div>
            <div className="flex-1">
              <p className="text-sm font-semibold text-text-primary">Laporan Penanganan</p>
              <p className="mt-0.5 text-xs text-text-secondary">Progres dan status treatment</p>
            </div>
          </div>
        </Link>

        <Link to="/laporan/dokumentasi" className="block">
          <div className="flex flex-col gap-3 rounded-lg border border-border bg-white p-4 shadow-card hover:shadow-md transition-shadow">
            <div className="flex h-10 w-10 items-center justify-center rounded-lg bg-brand-muted">
              <BookOpen className="h-5 w-5 text-brand" />
            </div>
            <div className="flex-1">
              <p className="text-sm font-semibold text-text-primary">Laporan Dokumentasi</p>
              <p className="mt-0.5 text-xs text-text-secondary">Dokumen dan SOP</p>
            </div>
          </div>
        </Link>
      </div>

      {/* Charts */}
      <div className="grid grid-cols-1 gap-6 lg:grid-cols-2">
        <Card padding="md">
          <CardHeader title="Distribusi Risiko" description="Risiko berdasarkan tingkat severity" className="mb-4" />
          <div className="h-64">
            <ResponsiveContainer width="100%" height="100%">
              <BarChart data={riskByLevel} barSize={48} margin={{ top: 4, right: 8, left: -16, bottom: 0 }}>
                <CartesianGrid strokeDasharray="3 3" stroke="#E5E7EB" vertical={false} />
                <XAxis 
                  dataKey="level" 
                  tick={{ fontSize: 12, fill: '#6B7280' }} 
                  axisLine={false} 
                  tickLine={false} 
                />
                <YAxis 
                  tick={{ fontSize: 12, fill: '#6B7280' }} 
                  axisLine={false} 
                  tickLine={false} 
                  allowDecimals={false} 
                />
                <Tooltip 
                  contentStyle={{ borderRadius: 8, border: '1px solid #E5E7EB', fontSize: 12 }} 
                  cursor={{ fill: '#F5F6F8' }} 
                />
                <Bar dataKey="count" name="Jumlah Risiko" radius={[4, 4, 0, 0]}>
                  {riskByLevel.map((entry, i) => <Cell key={i} fill={entry.color} />)}
                </Bar>
              </BarChart>
            </ResponsiveContainer>
          </div>
        </Card>

        <Card padding="md">
          <CardHeader title="Status Penanganan Risiko" description="Distribusi status treatment plan" className="mb-4" />
          <div className="h-64">
            <ResponsiveContainer width="100%" height="100%">
              <RechartsPieChart>
                <Pie 
                  data={treatmentProgress} 
                  dataKey="count" 
                  nameKey="status" 
                  cx="50%" 
                  cy="50%" 
                  outerRadius={90} 
                  innerRadius={55}
                >
                  {treatmentProgress.map((entry, i) => <Cell key={i} fill={entry.color} />)}
                </Pie>
                <Legend iconType="circle" iconSize={8} wrapperStyle={{ fontSize: 12 }} />
                <Tooltip contentStyle={{ borderRadius: 8, border: '1px solid #E5E7EB', fontSize: 12 }} />
              </RechartsPieChart>
            </ResponsiveContainer>
          </div>
        </Card>

        <Card padding="md" className="lg:col-span-2">
          <CardHeader title="Aset Berdasarkan Kategori" description="Distribusi aset organisasi" className="mb-4" />
          <div className="h-64">
            <ResponsiveContainer width="100%" height="100%">
              <BarChart data={assetByCategory} barSize={42} margin={{ top: 4, right: 8, left: -16, bottom: 0 }}>
                <CartesianGrid strokeDasharray="3 3" stroke="#E5E7EB" vertical={false} />
                <XAxis 
                  dataKey="kategori" 
                  tick={{ fontSize: 12, fill: '#6B7280' }} 
                  axisLine={false} 
                  tickLine={false} 
                />
                <YAxis 
                  tick={{ fontSize: 12, fill: '#6B7280' }} 
                  axisLine={false} 
                  tickLine={false} 
                  allowDecimals={false} 
                />
                <Tooltip 
                  contentStyle={{ borderRadius: 8, border: '1px solid #E5E7EB', fontSize: 12 }} 
                  cursor={{ fill: '#F5F6F8' }} 
                />
                <Bar dataKey="jumlah" name="Jumlah Aset" radius={[4, 4, 0, 0]} fill="#762127" />
              </BarChart>
            </ResponsiveContainer>
          </div>
        </Card>
      </div>

      {/* Summary Table */}
      <Card padding="none">
        <div className="border-b border-border px-5 py-4">
          <CardHeader title="Ringkasan Eksekutif" description="Kondisi keamanan informasi organisasi" />
        </div>
        <div className="overflow-x-auto">
          <table className="w-full text-sm">
            <thead>
              <tr className="border-b border-border bg-surface-muted">
                <th className="px-5 py-3 text-left text-xs font-semibold uppercase tracking-wider text-text-secondary">Indikator</th>
                <th className="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wider text-text-secondary">Nilai</th>
                <th className="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wider text-text-secondary">Status</th>
              </tr>
            </thead>
            <tbody className="divide-y divide-border">
              <tr className="hover:bg-surface-muted/50">
                <td className="px-5 py-3.5 font-medium text-text-primary">Total Aset Terdaftar</td>
                <td className="px-4 py-3.5 font-bold text-text-primary">{stats?.totalAssets || 0}</td>
                <td className="px-4 py-3.5"><Badge variant="info">Stabil</Badge></td>
              </tr>
              <tr className="hover:bg-surface-muted/50">
                <td className="px-5 py-3.5 font-medium text-text-primary">Total Risiko Teridentifikasi</td>
                <td className="px-4 py-3.5 font-bold text-text-primary">{stats?.totalRisks || 0}</td>
                <td className="px-4 py-3.5"><Badge variant="warning">Perlu Perhatian</Badge></td>
              </tr>
              <tr className="hover:bg-surface-muted/50">
                <td className="px-5 py-3.5 font-medium text-text-primary">Risiko Level Kritis</td>
                <td className="px-4 py-3.5 font-bold text-text-primary">{stats?.criticalRisks || 0}</td>
                <td className="px-4 py-3.5"><Badge variant="critical">Kritis</Badge></td>
              </tr>
              <tr className="hover:bg-surface-muted/50">
                <td className="px-5 py-3.5 font-medium text-text-primary">Rencana Penanganan Aktif</td>
                <td className="px-4 py-3.5 font-bold text-text-primary">{stats?.totalTreatments || 0}</td>
                <td className="px-4 py-3.5"><Badge variant="info">Berjalan</Badge></td>
              </tr>
              <tr className="hover:bg-surface-muted/50">
                <td className="px-5 py-3.5 font-medium text-text-primary">Rencana Sedang Berjalan</td>
                <td className="px-4 py-3.5 font-bold text-text-primary">{stats?.inProgressTreatments || 0}</td>
                <td className="px-4 py-3.5"><Badge variant="info">Aktif</Badge></td>
              </tr>
              <tr className="hover:bg-surface-muted/50">
                <td className="px-5 py-3.5 font-medium text-text-primary">Rencana Selesai</td>
                <td className="px-4 py-3.5 font-bold text-text-primary">{stats?.completedTreatments || 0}</td>
                <td className="px-4 py-3.5"><Badge variant="success">Selesai</Badge></td>
              </tr>
              <tr className="hover:bg-surface-muted/50">
                <td className="px-5 py-3.5 font-medium text-text-primary">Total Dokumen</td>
                <td className="px-4 py-3.5 font-bold text-text-primary">{stats?.totalDocuments || 0}</td>
                <td className="px-4 py-3.5"><Badge variant="neutral">Terdokumentasi</Badge></td>
              </tr>
              <tr className="hover:bg-surface-muted/50">
                <td className="px-5 py-3.5 font-medium text-text-primary">SOP Aktif</td>
                <td className="px-4 py-3.5 font-bold text-text-primary">{stats?.activeSOPs || 0} / {stats?.totalSOPs || 0}</td>
                <td className="px-4 py-3.5"><Badge variant="success">Berlaku</Badge></td>
              </tr>
            </tbody>
          </table>
        </div>
      </Card>

      {/* Info Banner */}
      <Card padding="md" className="border-l-[3px] border-l-blue-600 bg-blue-50/50">
        <div className="flex items-start gap-3">
          <FileText className="h-5 w-5 shrink-0 text-blue-700" />
          <div className="min-w-0 flex-1">
            <p className="text-sm font-medium text-blue-900">
              Laporan Berbasis Data Real-Time
            </p>
            <p className="mt-1 text-xs text-blue-800">
              Semua statistik dan grafik di atas dihasilkan dari data aktual modul Asset, Risk Assessment, Treatment, dan Documentation. 
              Ekspor PDF/Excel akan tersedia setelah integrasi backend selesai.
            </p>
          </div>
        </div>
      </Card>
    </div>
  );
}
