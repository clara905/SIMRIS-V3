import { useEffect, useState, useCallback } from 'react';
import { useNavigate } from 'react-router-dom';
import { Server, Download, Eye, TrendingUp, Package, AlertCircle } from 'lucide-react';
import { reportsService, type AssetReportSummary, type AssetReportFilters } from '@/services/reports.service';
import type { Asset } from '@/types/risk.types';
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
  PieChart as RechartsPieChart, 
  Pie, 
  Cell, 
  ResponsiveContainer, 
  Tooltip, 
  Legend 
} from 'recharts';

// ─── Filter Options ──────────────────────────────────────────────────────────

const CATEGORY_OPTIONS = [
  { value: '', label: 'Semua Kategori' },
  { value: 'Hardware', label: 'Hardware' },
  { value: 'Software', label: 'Software' },
  { value: 'Data', label: 'Data' },
  { value: 'Jaringan', label: 'Jaringan' },
  { value: 'SDM', label: 'SDM' },
  { value: 'Layanan', label: 'Layanan' },
  { value: 'Fasilitas', label: 'Fasilitas' },
  { value: 'Lainnya', label: 'Lainnya' },
];

const CONDITION_OPTIONS = [
  { value: '', label: 'Semua Kondisi' },
  { value: 'Baik', label: 'Baik' },
  { value: 'Rusak Ringan', label: 'Rusak Ringan' },
  { value: 'Rusak Berat', label: 'Rusak Berat' },
];

const STATUS_OPTIONS = [
  { value: '', label: 'Semua Status' },
  { value: 'Aktif', label: 'Aktif' },
  { value: 'Pemeliharaan', label: 'Pemeliharaan' },
  { value: 'Tidak Aktif', label: 'Tidak Aktif' },
];

// ─── Helper Functions ────────────────────────────────────────────────────────

function statusBadge(status: Asset['status']) {
  if (status === 'Aktif') return <Badge variant="success" dot>Aktif</Badge>;
  if (status === 'Pemeliharaan') return <Badge variant="warning" dot>Pemeliharaan</Badge>;
  return <Badge variant="neutral" dot>Tidak Aktif</Badge>;
}

function kondisiBadge(kondisi: Asset['kondisi']) {
  if (kondisi === 'Baik') return <Badge variant="success">Baik</Badge>;
  if (kondisi === 'Rusak Ringan') return <Badge variant="warning">Rusak Ringan</Badge>;
  return <Badge variant="critical">Rusak Berat</Badge>;
}

function formatCurrency(value: number): string {
  return new Intl.NumberFormat('id-ID', {
    style: 'currency',
    currency: 'IDR',
    minimumFractionDigits: 0,
  }).format(value);
}

function ciaLabel(val: number): string {
  const labels = ['', 'Sangat Rendah', 'Rendah', 'Sedang', 'Tinggi', 'Sangat Tinggi'];
  return labels[val] ?? val.toString();
}

// ─── Component ───────────────────────────────────────────────────────────────

export function AssetReport() {
  const navigate = useNavigate();
  const [summary, setSummary] = useState<AssetReportSummary | null>(null);
  const [assets, setAssets] = useState<Asset[]>([]);
  const [loading, setLoading] = useState(true);
  const [error, setError] = useState<string | null>(null);
  const [search, setSearch] = useState('');
  const [category, setCategory] = useState('');
  const [condition, setCondition] = useState('');
  const [status, setStatus] = useState('');

  const fetchData = useCallback(async () => {
    setLoading(true);
    setError(null);
    try {
      const [summaryData, assetData] = await Promise.all([
        reportsService.getAssetReportSummary(),
        reportsService.getAssetReportData({ search, category, condition, status }),
      ]);
      setSummary(summaryData);
      setAssets(assetData);
    } catch (err) {
      console.error('[AssetReport] Failed to fetch asset report data:', err);
      setError('Gagal memuat data laporan aset. Silakan coba lagi.');
    } finally {
      setLoading(false);
    }
  }, [search, category, condition, status]);

  useEffect(() => {
    fetchData();
  }, [fetchData]);

  const handleSearch = (value: string) => {
    setSearch(value);
  };

  const handleCategoryChange = (value: string) => {
    setCategory(value);
  };

  const handleConditionChange = (value: string) => {
    setCondition(value);
  };

  const handleStatusChange = (value: string) => {
    setStatus(value);
  };

  const handleViewAsset = (asset: Asset) => {
    navigate(`/aset/${asset.id}`);
  };

  const handleExport = () => {
    alert('Fitur ekspor akan tersedia setelah integrasi backend.\n\nData yang akan diekspor:\n' +
      `- Total Aset: ${summary?.totalAssets}\n` +
      `- Kategori: ${Object.keys(summary?.byCategory || {}).length} kategori\n` +
      `- Format: PDF / Excel`);
  };

  const hasFilters = search || category || condition || status;

  // Error display
  if (error) {
    return (
      <div className="space-y-5">
        <PageHeader
          title="Laporan Aset"
          description="Inventaris dan klasifikasi aset keamanan informasi"
          breadcrumb={
            <Breadcrumb
              items={[
                { label: 'Laporan', to: '/laporan' },
                { label: 'Aset' },
              ]}
            />
          }
        />
        <div className="flex min-h-[400px] items-center justify-center">
          <Card padding="md" className="max-w-md text-center">
            <AlertCircle className="mx-auto h-12 w-12 text-status-error" />
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
  const categoryData = summary ? Object.entries(summary.byCategory).map(([key, value]) => ({
    name: key,
    value,
  })) : [];

  const conditionData = summary ? Object.entries(summary.byCondition).map(([key, value]) => ({
    name: key,
    value,
    color: key === 'Baik' ? '#16A34A' : key === 'Rusak Ringan' ? '#D97706' : '#DC2626',
  })) : [];

  return (
    <div className="space-y-5">
      <PageHeader
        title="Laporan Aset"
        description="Inventaris dan klasifikasi aset keamanan informasi"
        breadcrumb={
          <Breadcrumb
            items={[
              { label: 'Laporan', to: '/laporan' },
              { label: 'Aset' },
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
            label="Total Aset"
            value={summary.totalAssets}
            icon={Server}
            supporting="Aset terdaftar"
            variant="info"
          />
          <StatCard
            label="Nilai Total Aset"
            value={formatCurrency(summary.totalValue)}
            icon={Package}
            supporting="Nilai investasi"
          />
          <StatCard
            label="Aset Kondisi Baik"
            value={summary.byCondition['Baik'] || 0}
            icon={TrendingUp}
            supporting="Kondisi optimal"
            variant="success"
          />
          <StatCard
            label="Butuh Perhatian"
            value={(summary.byCondition['Rusak Ringan'] || 0) + (summary.byCondition['Rusak Berat'] || 0)}
            icon={AlertCircle}
            supporting="Perlu maintenance"
            variant="warning"
          />
        </div>
      )}

      {/* Charts */}
      {summary && (
        <div className="grid grid-cols-1 gap-6 lg:grid-cols-2">
          <Card padding="md">
            <CardHeader title="Distribusi Kategori Aset" className="mb-4" />
            <div className="h-64">
              <ResponsiveContainer width="100%" height="100%">
                <RechartsPieChart>
                  <Pie
                    data={categoryData}
                    dataKey="value"
                    nameKey="name"
                    cx="50%"
                    cy="50%"
                    outerRadius={90}
                    innerRadius={55}
                    fill="#762127"
                  >
                    {categoryData.map((entry, index) => (
                      <Cell key={`cell-${index}`} fill={`hsl(${index * 45}, 60%, 50%)`} />
                    ))}
                  </Pie>
                  <Tooltip contentStyle={{ borderRadius: 8, border: '1px solid #E5E7EB', fontSize: 12 }} />
                  <Legend iconType="circle" iconSize={8} wrapperStyle={{ fontSize: 12 }} />
                </RechartsPieChart>
              </ResponsiveContainer>
            </div>
          </Card>

          <Card padding="md">
            <CardHeader title="Kondisi Aset" className="mb-4" />
            <div className="h-64">
              <ResponsiveContainer width="100%" height="100%">
                <RechartsPieChart>
                  <Pie
                    data={conditionData}
                    dataKey="value"
                    nameKey="name"
                    cx="50%"
                    cy="50%"
                    outerRadius={90}
                    innerRadius={55}
                  >
                    {conditionData.map((entry, index) => (
                      <Cell key={`cell-${index}`} fill={entry.color} />
                    ))}
                  </Pie>
                  <Tooltip contentStyle={{ borderRadius: 8, border: '1px solid #E5E7EB', fontSize: 12 }} />
                  <Legend iconType="circle" iconSize={8} wrapperStyle={{ fontSize: 12 }} />
                </RechartsPieChart>
              </ResponsiveContainer>
            </div>
          </Card>
        </div>
      )}

      {/* Summary Info */}
      {summary && (
        <Card padding="md">
          <CardHeader title="Klasifikasi Informasi (CIA Triad)" className="mb-4" />
          <div className="grid grid-cols-1 gap-4 sm:grid-cols-3">
            <div className="text-center">
              <p className="text-xs text-text-muted mb-1">Rata-rata Kerahasiaan (C)</p>
              <p className="text-3xl font-bold text-brand">{summary.avgCIA.C.toFixed(1)}</p>
              <p className="text-xs text-text-secondary mt-1">{ciaLabel(Math.round(summary.avgCIA.C))}</p>
            </div>
            <div className="text-center border-l border-r border-border">
              <p className="text-xs text-text-muted mb-1">Rata-rata Integritas (I)</p>
              <p className="text-3xl font-bold text-brand">{summary.avgCIA.I.toFixed(1)}</p>
              <p className="text-xs text-text-secondary mt-1">{ciaLabel(Math.round(summary.avgCIA.I))}</p>
            </div>
            <div className="text-center">
              <p className="text-xs text-text-muted mb-1">Rata-rata Ketersediaan (A)</p>
              <p className="text-3xl font-bold text-brand">{summary.avgCIA.A.toFixed(1)}</p>
              <p className="text-xs text-text-secondary mt-1">{ciaLabel(Math.round(summary.avgCIA.A))}</p>
            </div>
          </div>
        </Card>
      )}

      {/* Filters */}
      <Card padding="md">
        <div className="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
          <div className="flex-1 max-w-md">
            <SearchInput
              placeholder="Cari aset..."
              value={search}
              onChange={handleSearch}
            />
          </div>
          <div className="flex flex-wrap gap-2">
            <Select
              value={category}
              onChange={handleCategoryChange}
              options={CATEGORY_OPTIONS}
              className="w-40"
            />
            <Select
              value={condition}
              onChange={handleConditionChange}
              options={CONDITION_OPTIONS}
              className="w-40"
            />
            <Select
              value={status}
              onChange={handleStatusChange}
              options={STATUS_OPTIONS}
              className="w-40"
            />
            {hasFilters && (
              <Button
                size="sm"
                variant="outline"
                onClick={() => {
                  setSearch('');
                  setCategory('');
                  setCondition('');
                  setStatus('');
                }}
              >
                Reset
              </Button>
            )}
          </div>
        </div>
      </Card>

      {/* Asset Table */}
      <Card padding="none">
        {loading ? (
          <div className="flex items-center justify-center py-12">
            <PageLoader />
          </div>
        ) : assets.length === 0 ? (
          <EmptyState
            icon={Server}
            title="Tidak ada aset"
            description={
              hasFilters
                ? 'Tidak ada aset yang sesuai dengan filter. Coba ubah filter pencarian.'
                : 'Belum ada aset yang terdaftar.'
            }
          />
        ) : (
          <>
            <TableContainer>
              <Table>
                <TableHead>
                  <TableRow>
                    <Th>Kode & Nama</Th>
                    <Th>Kategori</Th>
                    <Th>Pemilik</Th>
                    <Th>Lokasi</Th>
                    <Th>Kondisi</Th>
                    <Th>Status</Th>
                    <Th>CIA</Th>
                    <Th align="right">Aksi</Th>
                  </TableRow>
                </TableHead>
                <TableBody>
                  {assets.map((asset) => (
                    <TableRow
                      key={asset.id}
                      onClick={() => handleViewAsset(asset)}
                      className="cursor-pointer hover:bg-surface-muted/50"
                    >
                      <Td primary>
                        <div>
                          <p className="font-medium text-text-primary">{asset.nama}</p>
                          <p className="text-xs text-text-muted">{asset.kode}</p>
                        </div>
                      </Td>
                      <Td secondary>{asset.kategori}</Td>
                      <Td secondary>{asset.pemilik}</Td>
                      <Td secondary>{asset.lokasi}</Td>
                      <Td>{kondisiBadge(asset.kondisi)}</Td>
                      <Td>{statusBadge(asset.status)}</Td>
                      <Td secondary>
                        <div className="text-xs">
                          C:{asset.kerahasiaan} I:{asset.integritas} A:{asset.ketersediaan}
                        </div>
                      </Td>
                      <Td align="right">
                        <button
                          onClick={(e) => {
                            e.stopPropagation();
                            handleViewAsset(asset);
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
                Menampilkan {assets.length} aset
                {hasFilters && ' (terfilter)'}
              </p>
            </div>
          </>
        )}
      </Card>
    </div>
  );
}
