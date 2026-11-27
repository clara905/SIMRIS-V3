import { useEffect, useState, useCallback } from 'react';
import { ShieldCheck, Download, Eye, Clock, CheckCircle, AlertTriangle } from 'lucide-react';
import { reportsService, type TreatmentReportSummary } from '@/services/reports.service';
import { PageHeader, Breadcrumb } from '@/components/common/PageHeader';
import { SearchInput } from '@/components/common/SearchInput';
import { Button } from '@/components/ui/Button';
import { Badge } from '@/components/ui/Badge';
import { Card, CardHeader } from '@/components/ui/Card';
import { StatCard } from '@/components/ui/StatCard';
import { Select } from '@/components/ui/Select';
import { PageLoader } from '@/components/ui/LoadingSpinner';
import { EmptyState } from '@/components/ui/EmptyState';
import { Table, TableHead, TableBody, TableRow, Th, Td, TableContainer } from '@/components/ui/Table';
import {
  BarChart,
  Bar,
  XAxis,
  YAxis,
  CartesianGrid,
  Tooltip,
  ResponsiveContainer,
  Cell,
} from 'recharts';

// ─── Filter Options ──────────────────────────────────────────────────────────

const STATUS_OPTIONS = [
  { value: '', label: 'Semua Status' },
  { value: 'selesai', label: 'Selesai' },
  { value: 'sedang_berjalan', label: 'Sedang Berjalan' },
  { value: 'belum_mulai', label: 'Belum Mulai' },
];

// ─── Helper Functions ────────────────────────────────────────────────────────

function getStatusBadge(status: string) {
  if (status === 'selesai') return <Badge variant="success" dot>Selesai</Badge>;
  if (status === 'sedang_berjalan') return <Badge variant="info" dot>Sedang Berjalan</Badge>;
  if (status === 'belum_mulai') return <Badge variant="neutral" dot>Belum Mulai</Badge>;
  if (status === 'terlambat') return <Badge variant="critical" dot>Terlambat</Badge>;
  return <Badge variant="neutral">{status}</Badge>;
}

function getStrategyBadge(strategy: string) {
  if (strategy === 'mitigasi') return <Badge variant="info">Mitigasi</Badge>;
  if (strategy === 'transfer') return <Badge variant="neutral">Transfer</Badge>;
  if (strategy === 'hindari') return <Badge variant="warning">Hindari</Badge>;
  if (strategy === 'terima') return <Badge variant="muted">Terima</Badge>;
  return <Badge variant="neutral">{strategy}</Badge>;
}

function formatDate(dateString: string): string {
  const date = new Date(dateString);
  return date.toLocaleDateString('id-ID', {
    year: 'numeric',
    month: 'short',
    day: 'numeric',
  });
}

// ─── Component ───────────────────────────────────────────────────────────────

export function TreatmentReport() {
  const [summary, setSummary] = useState<TreatmentReportSummary | null>(null);
  const [treatments, setTreatments] = useState<any[]>([]);
  const [loading, setLoading] = useState(true);
  const [error, setError] = useState<string | null>(null);
  const [search, setSearch] = useState('');
  const [status, setStatus] = useState('');

  const fetchData = useCallback(async () => {
    setLoading(true);
    setError(null);
    try {
      const [summaryData, treatmentData] = await Promise.all([
        reportsService.getTreatmentReportSummary(),
        reportsService.getTreatmentReportData({ status, search }),
      ]);
      setSummary(summaryData);
      setTreatments(treatmentData);
    } catch (err) {
      console.error('[TreatmentReport] Failed to fetch treatment report data:', err);
      setError('Gagal memuat data laporan penanganan. Silakan coba lagi.');
    } finally {
      setLoading(false);
    }
  }, [status, search]);

  useEffect(() => {
    fetchData();
  }, [fetchData]);

  const handleSearch = (value: string) => {
    setSearch(value);
  };

  const handleStatusChange = (value: string) => {
    setStatus(value);
  };

  const handleExport = () => {
    alert('Fitur ekspor akan tersedia setelah integrasi backend.\n\nData yang akan diekspor:\n' +
      `- Total Treatment: ${summary?.totalTreatments}\n` +
      `- Rata-rata Progress: ${summary?.avgProgress.toFixed(1)}%\n` +
      `- Format: PDF / Excel`);
  };

  const hasFilters = search || status;

  // Error display
  if (error) {
    return (
      <div className="space-y-5">
        <PageHeader
          title="Laporan Penanganan Risiko"
          description="Progres dan status treatment plan"
          breadcrumb={
            <Breadcrumb
              items={[
                { label: 'Laporan', to: '/laporan' },
                { label: 'Penanganan' },
              ]}
            />
          }
        />
        <div className="flex min-h-[400px] items-center justify-center">
          <Card padding="md" className="max-w-md text-center">
            <ShieldCheck className="mx-auto h-12 w-12 text-status-error" />
            <h3 className="mt-4 text-lg font-semibold text-text-primary">Gagal Memuat Laporan</h3>
            <p className="mt-2 text-sm text-text-secondary">{error}</p>
            <Button
              variant="primary"
              className="mt-4"
              onClick={() => fetchData()}
            >
              Coba Lagi
            </Button>
          </Card>
        </div>
      </div>
    );
  }

  // Chart data
  const statusData = summary ? [
    { status: 'Selesai', count: summary.byStatus.selesai || 0, color: '#16A34A' },
    { status: 'Berjalan', count: summary.byStatus.sedang_berjalan || 0, color: '#2563EB' },
    { status: 'Belum Mulai', count: summary.byStatus.belum_dimulai || 0, color: '#9CA3AF' },
    { status: 'Terlambat', count: summary.byStatus.terlambat || 0, color: '#DC2626' },
  ] : [];

  const strategyData = summary ? Object.entries(summary.byStrategy).map(([key, value]) => ({
    strategi: key.charAt(0).toUpperCase() + key.slice(1),
    jumlah: value,
  })) : [];

  return (
    <div className="space-y-5">
      <PageHeader
        title="Laporan Penanganan Risiko"
        description="Progres dan status rencana penanganan risiko"
        breadcrumb={
          <Breadcrumb
            items={[
              { label: 'Laporan', to: '/laporan' },
              { label: 'Penanganan' },
            ]}
          />
        }
        action={
          <Button
            variant="primary"
            leftIcon={<Download className="h-4 w-4" />}
            onClick={handleExport}
          >
            Ekspor Laporan
          </Button>
        }
      />

      {/* Summary Stats */}
      {summary && (
        <div className="grid grid-cols-1 gap-4 sm:grid-cols-2 xl:grid-cols-4">
          <StatCard
            label="Total Rencana"
            value={summary.totalTreatments}
            icon={ShieldCheck}
            supporting="Rencana penanganan"
          />
          <StatCard
            label="Selesai"
            value={summary.byStatus.selesai || 0}
            icon={CheckCircle}
            supporting="Treatment berhasil"
            variant="success"
          />
          <StatCard
            label="Sedang Berjalan"
            value={summary.byStatus.sedang_berjalan || 0}
            icon={Clock}
            supporting="Dalam progress"
            variant="info"
          />
          <StatCard
            label="Terlambat"
            value={summary.overdueTreatments}
            icon={AlertTriangle}
            supporting="Melewati target"
            variant="critical"
          />
        </div>
      )}

      {/* Charts */}
      {summary && (
        <div className="grid grid-cols-1 gap-6 lg:grid-cols-2">
          <Card padding="md">
            <CardHeader title="Distribusi Status Treatment" description="Jumlah treatment per status" className="mb-4" />
            <div className="h-64">
              <ResponsiveContainer width="100%" height="100%">
                <BarChart data={statusData} barSize={60} margin={{ top: 4, right: 8, left: -16, bottom: 0 }}>
                  <CartesianGrid strokeDasharray="3 3" stroke="#E5E7EB" vertical={false} />
                  <XAxis
                    dataKey="status"
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
                  <Bar dataKey="count" name="Jumlah" radius={[4, 4, 0, 0]}>
                    {statusData.map((entry, i) => <Cell key={i} fill={entry.color} />)}
                  </Bar>
                </BarChart>
              </ResponsiveContainer>
            </div>
          </Card>

          <Card padding="md">
            <CardHeader title="Strategi Penanganan" description="Distribusi strategi treatment" className="mb-4" />
            <div className="h-64">
              <ResponsiveContainer width="100%" height="100%">
                <BarChart data={strategyData} barSize={60} margin={{ top: 4, right: 8, left: -16, bottom: 0 }}>
                  <CartesianGrid strokeDasharray="3 3" stroke="#E5E7EB" vertical={false} />
                  <XAxis
                    dataKey="strategi"
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
                  <Bar dataKey="jumlah" name="Jumlah" radius={[4, 4, 0, 0]} fill="#762127" />
                </BarChart>
              </ResponsiveContainer>
            </div>
          </Card>
        </div>
      )}

      {/* Progress Summary */}
      {summary && (
        <Card padding="md">
          <CardHeader title="Rata-rata Progress Penanganan" className="mb-4" />
          <div className="text-center p-8">
            <div className="inline-flex items-center justify-center h-32 w-32 rounded-full bg-gradient-to-br from-brand to-brand-dark">
              <span className="text-5xl font-bold text-white">{summary.avgProgress.toFixed(0)}%</span>
            </div>
            <p className="mt-4 text-sm text-text-secondary">
              Rata-rata progress dari seluruh rencana penanganan
            </p>
          </div>
        </Card>
      )}

      {/* Filters */}
      <Card padding="md">
        <div className="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
          <div className="flex-1 max-w-md">
            <SearchInput
              placeholder="Cari treatment..."
              value={search}
              onChange={handleSearch}
            />
          </div>
          <div className="flex flex-wrap gap-2">
            <Select
              value={status}
              onChange={handleStatusChange}
              options={STATUS_OPTIONS}
              className="w-48"
            />
            {hasFilters && (
              <Button
                size="sm"
                variant="outline"
                onClick={() => {
                  setSearch('');
                  setStatus('');
                }}
              >
                Reset
              </Button>
            )}
          </div>
        </div>
      </Card>

      {/* Treatment Table */}
      <Card padding="none">
        {loading ? (
          <div className="flex items-center justify-center py-12">
            <PageLoader />
          </div>
        ) : treatments.length === 0 ? (
          <EmptyState
            icon={ShieldCheck}
            title="Tidak ada rencana penanganan"
            description={
              hasFilters
                ? 'Tidak ada treatment yang sesuai dengan filter. Coba ubah filter pencarian.'
                : 'Belum ada rencana penanganan yang dibuat.'
            }
          />
        ) : (
          <>
            <TableContainer>
              <Table>
                <TableHead>
                  <TableRow>
                    <Th>Risiko & Treatment</Th>
                    <Th>Strategi</Th>
                    <Th>PIC</Th>
                    <Th>Target</Th>
                    <Th align="center">Progress</Th>
                    <Th>Status</Th>
                    <Th align="right">Aksi</Th>
                  </TableRow>
                </TableHead>
                <TableBody>
                  {treatments.map((treatment) => {
                    const isOverdue = new Date(treatment.tgl_target) < new Date() && treatment.status !== 'selesai';
                    
                    return (
                      <TableRow
                        key={treatment.id_rencana}
                        className="hover:bg-surface-muted/50"
                      >
                        <Td primary>
                          <div>
                            <p className="font-medium text-text-primary">{treatment.deskripsi}</p>
                            <p className="text-xs text-text-muted">{treatment.id_risiko}</p>
                          </div>
                        </Td>
                        <Td>{getStrategyBadge(treatment.strategi)}</Td>
                        <Td secondary>{treatment.pic}</Td>
                        <Td secondary>
                          <div className="flex items-center gap-1">
                            {isOverdue && <AlertTriangle className="h-3 w-3 text-red-600" />}
                            {formatDate(treatment.tgl_target)}
                          </div>
                        </Td>
                        <Td align="center">
                          <div className="flex items-center gap-2">
                            <div className="flex-1 h-2 bg-surface-muted rounded-full overflow-hidden">
                              <div
                                className="h-full bg-brand transition-all"
                                style={{ width: `${treatment.progres || 0}%` }}
                              />
                            </div>
                            <span className="text-xs font-medium text-text-primary w-10 text-right">
                              {treatment.progres || 0}%
                            </span>
                          </div>
                        </Td>
                        <Td>{getStatusBadge(treatment.status)}</Td>
                        <Td align="right">
                          <button
                            onClick={() => {
                              alert(`Detail treatment ${treatment.id_rencana} akan ditampilkan di sini.\nIntegrasi dengan modul Risk Treatment.`);
                            }}
                            className="rounded p-1.5 text-text-muted hover:bg-surface-muted hover:text-brand"
                            title="Lihat detail"
                          >
                            <Eye className="h-4 w-4" />
                          </button>
                        </Td>
                      </TableRow>
                    );
                  })}
                </TableBody>
              </Table>
            </TableContainer>

            {/* Result Info */}
            <div className="border-t border-border px-5 py-4">
              <p className="text-sm text-text-secondary">
                Menampilkan {treatments.length} rencana penanganan
                {hasFilters && ' (terfilter)'}
              </p>
            </div>
          </>
        )}
      </Card>

      {/* Info Banner */}
      <Card padding="md" className="border-l-[3px] border-l-green-600 bg-green-50/50">
        <div className="flex items-start gap-3">
          <ShieldCheck className="h-5 w-5 shrink-0 text-green-700" />
          <div className="min-w-0 flex-1">
            <p className="text-sm font-medium text-green-900">
              Data Treatment dari Risk Treatment Module
            </p>
            <p className="mt-1 text-xs text-green-800">
              Laporan ini menampilkan rencana penanganan yang dibuat dari modul Risk Treatment. 
              Progress dihitung berdasarkan milestone yang telah dicapai.
            </p>
          </div>
        </div>
      </Card>
    </div>
  );
}
