import { useState } from 'react';
import { FileText, BarChart2, PieChart, TrendingUp, FileDown, Info } from 'lucide-react';
import {
  BarChart, Bar, XAxis, YAxis, CartesianGrid, Tooltip, ResponsiveContainer,
  PieChart as RechartsPieChart, Pie, Cell, Legend,
} from 'recharts';
import { PageHeader } from '@/components/common/PageHeader';
import { Card, CardHeader } from '@/components/ui/Card';
import { Button } from '@/components/ui/Button';
import { Badge } from '@/components/ui/Badge';

const riskByLevel = [
  { level: 'Kritis', jumlah: 7, color: '#762127' },
  { level: 'Tinggi', jumlah: 11, color: '#DC2626' },
  { level: 'Sedang', jumlah: 13, color: '#D97706' },
  { level: 'Rendah', jumlah: 7, color: '#16A34A' },
];

const treatmentProgress = [
  { name: 'Selesai', value: 8, color: '#16A34A' },
  { name: 'Berjalan', value: 7, color: '#2563EB' },
  { name: 'Belum Mulai', value: 5, color: '#9CA3AF' },
  { name: 'Terlambat', value: 3, color: '#DC2626' },
];

const assetByCategory = [
  { kategori: 'Hardware', jumlah: 42 },
  { kategori: 'Software', jumlah: 35 },
  { kategori: 'Data', jumlah: 28 },
  { kategori: 'Jaringan', jumlah: 18 },
  { kategori: 'SDM', jumlah: 12 },
  { kategori: 'Lainnya', jumlah: 7 },
];

const REPORT_TYPES = [
  { id: 'risk-summary', label: 'Ringkasan Risiko', icon: BarChart2, desc: 'Distribusi dan tren risiko per periode' },
  { id: 'asset-summary', label: 'Ringkasan Aset', icon: PieChart, desc: 'Inventaris aset berdasarkan kategori dan status' },
  { id: 'treatment-progress', label: 'Progres Penanganan', icon: TrendingUp, desc: 'Status dan progres rencana penanganan risiko' },
  { id: 'full-report', label: 'Laporan Lengkap', icon: FileText, desc: 'Laporan komprehensif seluruh modul' },
];

export function ReportsPage() {
  const [dateFrom, setDateFrom] = useState('2026-10-01');
  const [dateTo, setDateTo] = useState('2026-11-30');

  return (
    <div className="space-y-6">
      <PageHeader title="Laporan" description="Ringkasan dan ekspor data manajemen risiko" />

      {/* Export notice */}
      <div className="flex items-start gap-3 rounded-md border border-blue-200 bg-blue-50 px-4 py-3 text-sm text-blue-800">
        <Info className="mt-0.5 h-4 w-4 shrink-0" />
        <div>
          <strong>Fitur ekspor PDF/Excel</strong> sedang dalam pengembangan dan akan tersedia setelah integrasi backend selesai.
          Saat ini Anda dapat melihat pratinjau laporan di bawah ini.
        </div>
      </div>

      {/* Date range */}
      <Card padding="md">
        <CardHeader title="Filter Periode" description="Atur rentang tanggal untuk seluruh laporan" className="mb-4" />
        <div className="flex flex-wrap items-end gap-4">
          <div className="flex flex-col gap-1">
            <label className="text-sm font-medium text-text-primary">Dari</label>
            <input type="date" value={dateFrom} onChange={(e) => setDateFrom(e.target.value)} className="h-9 rounded-md border border-border px-3 text-sm focus:outline-none focus:ring-2 focus:ring-brand" />
          </div>
          <div className="flex flex-col gap-1">
            <label className="text-sm font-medium text-text-primary">Sampai</label>
            <input type="date" value={dateTo} onChange={(e) => setDateTo(e.target.value)} className="h-9 rounded-md border border-border px-3 text-sm focus:outline-none focus:ring-2 focus:ring-brand" />
          </div>
          <Button variant="secondary" size="md">Terapkan Filter</Button>
        </div>
      </Card>

      {/* Report types */}
      <div className="grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-4">
        {REPORT_TYPES.map((r) => (
          <div key={r.id} className="flex flex-col gap-3 rounded-lg border border-border bg-white p-4 shadow-card">
            <div className="flex h-10 w-10 items-center justify-center rounded-lg bg-brand-muted">
              <r.icon className="h-5 w-5 text-brand" />
            </div>
            <div className="flex-1">
              <p className="text-sm font-semibold text-text-primary">{r.label}</p>
              <p className="mt-0.5 text-xs text-text-secondary">{r.desc}</p>
            </div>
            <Button
              variant="outline"
              size="sm"
              leftIcon={<FileDown className="h-3.5 w-3.5" />}
              disabled
              className="opacity-60 cursor-not-allowed"
              title="Fitur belum tersedia"
            >
              Ekspor
            </Button>
          </div>
        ))}
      </div>

      {/* Charts preview */}
      <div className="grid grid-cols-1 gap-6 lg:grid-cols-2">
        <Card padding="md">
          <CardHeader title="Distribusi Risiko" description={`Periode: ${dateFrom} — ${dateTo}`} className="mb-4" />
          <div className="h-56">
            <ResponsiveContainer width="100%" height="100%">
              <BarChart data={riskByLevel} barSize={36} margin={{ top: 4, right: 8, left: -16, bottom: 0 }}>
                <CartesianGrid strokeDasharray="3 3" stroke="#E5E7EB" vertical={false} />
                <XAxis dataKey="level" tick={{ fontSize: 12, fill: '#6B7280' }} axisLine={false} tickLine={false} />
                <YAxis tick={{ fontSize: 12, fill: '#6B7280' }} axisLine={false} tickLine={false} allowDecimals={false} />
                <Tooltip contentStyle={{ borderRadius: 8, border: '1px solid #E5E7EB', fontSize: 12 }} cursor={{ fill: '#F5F6F8' }} />
                <Bar dataKey="jumlah" name="Jumlah" radius={[4, 4, 0, 0]}>
                  {riskByLevel.map((entry, i) => <Cell key={i} fill={entry.color} />)}
                </Bar>
              </BarChart>
            </ResponsiveContainer>
          </div>
        </Card>

        <Card padding="md">
          <CardHeader title="Status Penanganan Risiko" className="mb-4" />
          <div className="h-56">
            <ResponsiveContainer width="100%" height="100%">
              <RechartsPieChart>
                <Pie data={treatmentProgress} dataKey="value" nameKey="name" cx="50%" cy="45%" outerRadius={80} innerRadius={48}>
                  {treatmentProgress.map((entry, i) => <Cell key={i} fill={entry.color} />)}
                </Pie>
                <Legend iconType="circle" iconSize={8} wrapperStyle={{ fontSize: 12 }} />
                <Tooltip contentStyle={{ borderRadius: 8, border: '1px solid #E5E7EB', fontSize: 12 }} />
              </RechartsPieChart>
            </ResponsiveContainer>
          </div>
        </Card>

        <Card padding="md" className="lg:col-span-2">
          <CardHeader title="Aset Berdasarkan Kategori" className="mb-4" />
          <div className="h-56">
            <ResponsiveContainer width="100%" height="100%">
              <BarChart data={assetByCategory} barSize={36} margin={{ top: 4, right: 8, left: -16, bottom: 0 }}>
                <CartesianGrid strokeDasharray="3 3" stroke="#E5E7EB" vertical={false} />
                <XAxis dataKey="kategori" tick={{ fontSize: 12, fill: '#6B7280' }} axisLine={false} tickLine={false} />
                <YAxis tick={{ fontSize: 12, fill: '#6B7280' }} axisLine={false} tickLine={false} allowDecimals={false} />
                <Tooltip contentStyle={{ borderRadius: 8, border: '1px solid #E5E7EB', fontSize: 12 }} cursor={{ fill: '#F5F6F8' }} />
                <Bar dataKey="jumlah" name="Jumlah Aset" radius={[4, 4, 0, 0]} fill="#8F2D35" />
              </BarChart>
            </ResponsiveContainer>
          </div>
        </Card>
      </div>

      {/* Summary table */}
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
              {[
                { label: 'Total Aset Terdaftar', value: '142', status: 'info' as const, statusLabel: 'Stabil' },
                { label: 'Total Risiko Teridentifikasi', value: '38', status: 'warning' as const, statusLabel: 'Perlu Perhatian' },
                { label: 'Risiko Level Kritis', value: '7', status: 'critical' as const, statusLabel: 'Kritis' },
                { label: 'Rencana Penanganan Aktif', value: '15', status: 'info' as const, statusLabel: 'Berjalan' },
                { label: 'Rencana Melewati Tenggat', value: '3', status: 'high' as const, statusLabel: 'Terlambat' },
                { label: 'Rencana Selesai', value: '8', status: 'success' as const, statusLabel: 'Selesai' },
              ].map((row) => (
                <tr key={row.label} className="hover:bg-surface-muted/50">
                  <td className="px-5 py-3.5 font-medium text-text-primary">{row.label}</td>
                  <td className="px-4 py-3.5 font-bold text-text-primary">{row.value}</td>
                  <td className="px-4 py-3.5"><Badge variant={row.status}>{row.statusLabel}</Badge></td>
                </tr>
              ))}
            </tbody>
          </table>
        </div>
      </Card>
    </div>
  );
}
