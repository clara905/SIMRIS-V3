import { useEffect, useState, useCallback } from 'react';
import { AlertTriangle, Download, Eye, TrendingUp } from 'lucide-react';
import { reportsService, type RiskReportSummary } from '@/services/reports.service';
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

const LEVEL_OPTIONS = [
  { value: '', label: 'Semua Level' },
  { value: 'Ekstrem', label: 'Ekstrem' },
  { value: 'Tinggi', label: 'Tinggi' },
  { value: 'Sedang', label: 'Sedang' },
  { value: 'Rendah', label: 'Rendah' },
  { value: 'Sangat Rendah', label: 'Sangat Rendah' },
];

// ─── Helper Functions ────────────────────────────────────────────────────────

function getRiskLevelBadge(level: string) {
  if (level === 'Ekstrem') return <Badge variant="critical" dot>Ekstrem</Badge>;
  if (level === 'Tinggi') return <Badge variant="high" dot>Tinggi</Badge>;
  if (level === 'Sedang') return <Badge variant="medium" dot>Sedang</Badge>;
  if (level === 'Rendah') return <Badge variant="low" dot>Rendah</Badge>;
  if (level === 'Sangat Rendah') return <Badge variant="success" dot>Sangat Rendah</Badge>;
  return <Badge variant="neutral">{level}</Badge>;
}

function getRiskLevelColor(level: string): string {
  if (level === 'Ekstrem') return '#762127';
  if (level === 'Tinggi') return '#A65316';
  if (level === 'Sedang') return '#8A6B00';
  if (level === 'Rendah') return '#16A34A';
  if (level === 'Sangat Rendah') return '#065F46';
  return '#9CA3AF';
}

// ─── Component ───────────────────────────────────────────────────────────────

export function RiskReport() {
  const [summary, setSummary] = useState<RiskReportSummary | null>(null);
  const [risks, setRisks] = useState<any[]>([]);
  const [loading, setLoading] = useState(true);
  const [error, setError] = useState<string | null>(null);
  const [search, setSearch] = useState('');
  const [level, setLevel] = useState('');

  const fetchData = useCallback(async () => {
    setLoading(true);
    setError(null);
    try {
      const [summaryData, riskData] = await Promise.all([
        reportsService.getRiskReportSummary(),
        reportsService.getRiskReportData({ level, search }),
      ]);
      setSummary(summaryData);
      setRisks(riskData);
    } catch (err) {
      console.error('[RiskReport] Failed to fetch risk report data:', err);
      setError('Gagal memuat data laporan risiko. Silakan coba lagi.');
    } finally {
      setLoading(false);
    }
  }, [level, search]);

  useEffect(() => {
    fetchData();
  }, [fetchData]);

  const handleSearch = (value: string) => {
    setSearch(value);
  };

  const handleLevelChange = (value: string) => {
    setLevel(value);
  };

  const handleExport = () => {
    alert('Fitur ekspor akan tersedia setelah integrasi backend.\n\nData yang akan diekspor:\n' +
      `- Total Risiko: ${summary?.totalRisks}\n` +
      `- Risiko Ekstrem: ${summary?.byLevel['Ekstrem'] || 0}\n` +
      `- Format: PDF / Excel`);
  };

  const hasFilters = search || level;

  // Error display
  if (error) {
    return (
      <div className="space-y-5">
        <PageHeader
          title="Laporan Risiko"
          description="Asesmen dan evaluasi risiko keamanan informasi"
          breadcrumb={
            <Breadcrumb
              items={[
                { label: 'Laporan', to: '/laporan' },
                { label: 'Risiko' },
              ]}
            />
          }
        />
        <div className="flex min-h-[400px] items-center justify-center">
          <Card padding="md" className="max-w-md text-center">
            <AlertTriangle className="mx-auto h-12 w-12 text-status-error" />
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
  const levelData = summary ? [
    { level: 'Ekstrem', count: summary.byLevel['Ekstrem'] || 0, color: '#762127' },
    { level: 'Tinggi', count: summary.byLevel['Tinggi'] || 0, color: '#A65316' },
    { level: 'Sedang', count: summary.byLevel['Sedang'] || 0, color: '#8A6B00' },
    { level: 'Rendah', count: summary.byLevel['Rendah'] || 0, color: '#16A34A' },
    { level: 'Sangat Rendah', count: summary.byLevel['Sangat Rendah'] || 0, color: '#065F46' },
  ].filter(item => item.count > 0) : [];

  return (
    <div className="space-y-5">
      <PageHeader
        title="Laporan Risiko"
        description="Asesmen dan evaluasi risiko keamanan informasi"
        breadcrumb={
          <Breadcrumb
            items={[
              { label: 'Laporan', to: '/laporan' },
              { label: 'Risiko' },
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
            label="Total Risiko"
            value={summary.totalRisks}
            icon={AlertTriangle}
            supporting="Risiko teridentifikasi"
            variant="warning"
          />
          <StatCard
            label="Risiko Ekstrem"
            value={summary.byLevel['Ekstrem'] || 0}
            icon={AlertTriangle}
            supporting="Prioritas tertinggi"
            variant="critical"
          />
          <StatCard
            label="Risiko Tinggi"
            value={summary.byLevel['Tinggi'] || 0}
            icon={AlertTriangle}
            supporting="Perlu tindakan segera"
            variant="warning"
          />
          <StatCard
            label="Rata-rata Likelihood"
            value={summary.avgLikelihood.toFixed(1)}
            icon={TrendingUp}
            supporting="Skala 1-5"
            variant="info"
          />
        </div>
      )}

      {/* Chart */}
      {summary && (
        <Card padding="md">
          <CardHeader title="Distribusi Risiko Berdasarkan Level" description="Jumlah risiko per tingkat severity" className="mb-4" />
          <div className="h-72">
            <ResponsiveContainer width="100%" height="100%">
              <BarChart data={levelData} barSize={60} margin={{ top: 4, right: 8, left: -16, bottom: 0 }}>
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
                  {levelData.map((entry, i) => <Cell key={i} fill={entry.color} />)}
                </Bar>
              </BarChart>
            </ResponsiveContainer>
          </div>
        </Card>
      )}

      {/* Average Scores */}
      {summary && (
        <Card padding="md">
          <CardHeader title="Rata-rata Penilaian Risiko" className="mb-4" />
          <div className="grid grid-cols-1 gap-4 sm:grid-cols-2">
            <div className="text-center p-6 rounded-lg bg-amber-50 border border-amber-200">
              <p className="text-xs text-amber-800 font-semibold mb-2">RATA-RATA LIKELIHOOD</p>
              <p className="text-5xl font-bold text-amber-900">{summary.avgLikelihood.toFixed(1)}</p>
              <p className="text-xs text-amber-700 mt-2">dari skala 1-5</p>
            </div>
            <div className="text-center p-6 rounded-lg bg-red-50 border border-red-200">
              <p className="text-xs text-red-800 font-semibold mb-2">RATA-RATA IMPACT</p>
              <p className="text-5xl font-bold text-red-900">{summary.avgImpact.toFixed(1)}</p>
              <p className="text-xs text-red-700 mt-2">dari skala 1-5</p>
            </div>
          </div>
        </Card>
      )}

      {/* Filters */}
      <Card padding="md">
        <div className="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
          <div className="flex-1 max-w-md">
            <SearchInput
              placeholder="Cari risiko..."
              value={search}
              onChange={handleSearch}
            />
          </div>
          <div className="flex flex-wrap gap-2">
            <Select
              value={level}
              onChange={handleLevelChange}
              options={LEVEL_OPTIONS}
              className="w-40"
            />
            {hasFilters && (
              <Button
                size="sm"
                variant="outline"
                onClick={() => {
                  setSearch('');
                  setLevel('');
                }}
              >
                Reset
              </Button>
            )}
          </div>
        </div>
      </Card>

      {/* Risk Table */}
      <Card padding="none">
        {loading ? (
          <div className="flex items-center justify-center py-12">
            <PageLoader />
          </div>
        ) : risks.length === 0 ? (
          <EmptyState
            icon={AlertTriangle}
            title="Tidak ada risiko"
            description={
              hasFilters
                ? 'Tidak ada risiko yang sesuai dengan filter. Coba ubah filter pencarian.'
                : 'Belum ada risiko yang teridentifikasi.'
            }
          />
        ) : (
          <>
            <TableContainer>
              <Table>
                <TableHead>
                  <TableRow>
                    <Th>Kode & Skenario</Th>
                    <Th>Aset Terkait</Th>
                    <Th align="center">Likelihood</Th>
                    <Th align="center">Impact</Th>
                    <Th>Level Risiko</Th>
                    <Th align="right">Aksi</Th>
                  </TableRow>
                </TableHead>
                <TableBody>
                  {risks.map((risk) => (
                    <TableRow
                      key={risk.id}
                      className="hover:bg-surface-muted/50"
                    >
                      <Td primary>
                        <div>
                          <p className="font-medium text-text-primary">{risk.skenario}</p>
                          <p className="text-xs text-text-muted">{risk.kode}</p>
                        </div>
                      </Td>
                      <Td secondary>
                        <div className="flex flex-wrap gap-1">
                          {risk.aset.map((asetId: string, idx: number) => (
                            <Badge key={idx} variant="neutral" size="sm">
                              {asetId}
                            </Badge>
                          ))}
                        </div>
                      </Td>
                      <Td align="center">
                        <div className="inline-flex items-center justify-center h-8 w-8 rounded-full bg-amber-100 text-amber-900 font-semibold text-sm">
                          {risk.kemungkinan}
                        </div>
                      </Td>
                      <Td align="center">
                        <div className="inline-flex items-center justify-center h-8 w-8 rounded-full bg-red-100 text-red-900 font-semibold text-sm">
                          {risk.dampak}
                        </div>
                      </Td>
                      <Td>{getRiskLevelBadge(risk.level)}</Td>
                      <Td align="right">
                        <button
                          onClick={() => {
                            alert(`Detail risiko ${risk.kode} akan ditampilkan di sini.\nIntegrasi dengan modul Risk Assessment.`);
                          }}
                          className="rounded p-1.5 text-text-muted hover:bg-surface-muted hover:text-brand"
                          title="Lihat detail"
                        >
                          <Eye className="h-4 w-4" />
                        </button>
                      </Td>
                    </TableRow>
                  ))}
                </TableBody>
              </Table>
            </TableContainer>

            {/* Result Info */}
            <div className="border-t border-border px-5 py-4">
              <p className="text-sm text-text-secondary">
                Menampilkan {risks.length} risiko
                {hasFilters && ' (terfilter)'}
              </p>
            </div>
          </>
        )}
      </Card>

      {/* Info Banner */}
      <Card padding="md" className="border-l-[3px] border-l-amber-600 bg-amber-50/50">
        <div className="flex items-start gap-3">
          <AlertTriangle className="h-5 w-5 shrink-0 text-amber-700" />
          <div className="min-w-0 flex-1">
            <p className="text-sm font-medium text-amber-900">
              Data Risiko dari Asesmen Aktif
            </p>
            <p className="mt-1 text-xs text-amber-800">
              Laporan ini menampilkan risiko yang teridentifikasi dari modul Risk Assessment. 
              Nilai likelihood dan impact menggunakan skala 1-5 sesuai ISO 31000:2018.
            </p>
          </div>
        </div>
      </Card>
    </div>
  );
}
