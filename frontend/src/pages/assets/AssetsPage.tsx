import { useEffect, useState, useCallback } from 'react';
import { Plus, Eye, Pencil, Trash2, X } from 'lucide-react';
import { assetService, type AssetSummary } from '@/services/asset.service';
import type { Asset, AssetCategory, AssetStatus, AssetCondition } from '@/types/risk.types';
import type { PaginatedResponse } from '@/types/common.types';
import { PageHeader } from '@/components/common/PageHeader';
import { SearchInput } from '@/components/common/SearchInput';
import { Button } from '@/components/ui/Button';
import { Badge } from '@/components/ui/Badge';
import { Card } from '@/components/ui/Card';
import { StatCard } from '@/components/ui/StatCard';
import { Pagination } from '@/components/ui/Pagination';
import { PageLoader } from '@/components/ui/LoadingSpinner';
import { EmptyState } from '@/components/ui/EmptyState';
import { ConfirmDialog } from '@/components/ui/ConfirmDialog';
import { Select } from '@/components/ui/Select';
import { AssetModal } from './AssetModal';
import { 
  Server, 
  CheckCircle, 
  Wrench, 
  XCircle 
} from 'lucide-react';

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

const STATUS_OPTIONS = [
  { value: '', label: 'Semua Status' },
  { value: 'Aktif', label: 'Aktif' },
  { value: 'Pemeliharaan', label: 'Pemeliharaan' },
  { value: 'Tidak Aktif', label: 'Tidak Aktif' },
];

const KONDISI_OPTIONS = [
  { value: '', label: 'Semua Kondisi' },
  { value: 'Baik', label: 'Baik' },
  { value: 'Rusak Ringan', label: 'Rusak Ringan' },
  { value: 'Rusak Berat', label: 'Rusak Berat' },
];

// ─── Helper Functions ────────────────────────────────────────────────────────

function statusBadge(status: AssetStatus) {
  if (status === 'Aktif') return <Badge variant="success">Aktif</Badge>;
  if (status === 'Pemeliharaan') return <Badge variant="warning">Pemeliharaan</Badge>;
  return <Badge variant="neutral">Tidak Aktif</Badge>;
}

function kondisiBadge(kondisi: AssetCondition) {
  if (kondisi === 'Baik') return <Badge variant="success">Baik</Badge>;
  if (kondisi === 'Rusak Ringan') return <Badge variant="warning">Rusak Ringan</Badge>;
  return <Badge variant="critical">Rusak Berat</Badge>;
}

function ciaLabel(val: number) {
  const labels = ['', 'Sangat Rendah', 'Rendah', 'Sedang', 'Tinggi', 'Sangat Tinggi'];
  return labels[val] ?? val;
}

function formatDate(dateString: string): string {
  const date = new Date(dateString);
  return date.toLocaleDateString('id-ID', { 
    year: 'numeric', 
    month: 'short', 
    day: 'numeric' 
  });
}

function formatCurrency(value: number): string {
  return new Intl.NumberFormat('id-ID', {
    style: 'currency',
    currency: 'IDR',
    minimumFractionDigits: 0,
  }).format(value);
}

// ─── Component ───────────────────────────────────────────────────────────────

export function AssetsPage() {
  const [summary, setSummary] = useState<AssetSummary | null>(null);
  const [result, setResult] = useState<PaginatedResponse<Asset> | null>(null);
  const [loading, setLoading] = useState(true);
  const [search, setSearch] = useState('');
  const [category, setCategory] = useState('');
  const [status, setStatus] = useState('');
  const [kondisi, setKondisi] = useState('');
  const [page, setPage] = useState(1);
  const [modalOpen, setModalOpen] = useState(false);
  const [editTarget, setEditTarget] = useState<Asset | null>(null);
  const [deleteTarget, setDeleteTarget] = useState<Asset | null>(null);
  const [deleting, setDeleting] = useState(false);
  const [viewTarget, setViewTarget] = useState<Asset | null>(null);

  const fetchData = useCallback(() => {
    setLoading(true);
    Promise.all([
      assetService.getSummary(),
      assetService.list({ 
        q: search, 
        kategori: category || undefined, 
        status: status || undefined,
        kondisi: kondisi || undefined,
        page 
      })
    ])
      .then(([summaryData, assetsData]) => {
        setSummary(summaryData);
        setResult(assetsData);
      })
      .finally(() => setLoading(false));
  }, [search, category, status, kondisi, page]);

  useEffect(() => { 
    fetchData(); 
  }, [fetchData]);

  const handleDelete = async () => {
    if (!deleteTarget) return;
    setDeleting(true);
    try {
      await assetService.delete(deleteTarget.id);
      setDeleteTarget(null);
      fetchData();
    } catch (error) {
      console.error('Delete failed:', error);
    } finally {
      setDeleting(false);
    }
  };

  const hasActiveFilters = search || category || status || kondisi;

  const resetFilters = () => {
    setSearch('');
    setCategory('');
    setStatus('');
    setKondisi('');
    setPage(1);
  };

  return (
    <div className="space-y-5">
      {/* Page Header */}
      <PageHeader
        title="Manajemen Aset"
        description="Kelola dan pantau data aset yang menjadi tanggung jawab Anda."
        action={
          <Button 
            leftIcon={<Plus className="h-4 w-4" />} 
            onClick={() => { setEditTarget(null); setModalOpen(true); }}
          >
            Tambah Aset
          </Button>
        }
      />

      {/* Summary Cards */}
      {!loading && summary && (
        <div className="grid grid-cols-1 gap-4 sm:grid-cols-2 xl:grid-cols-4">
          <StatCard
            label="Total Aset"
            value={summary.totalAset}
            icon={Server}
            supporting="Semua aset terdaftar"
          />
          <StatCard
            label="Aset Aktif"
            value={summary.asetAktif}
            icon={CheckCircle}
            supporting="Dalam kondisi operasional"
            variant="success"
          />
          <StatCard
            label="Dalam Pemeliharaan"
            value={summary.asetPemeliharaan}
            icon={Wrench}
            supporting="Sedang diperbaiki"
            variant="warning"
          />
          <StatCard
            label="Tidak Aktif"
            value={summary.asetTidakAktif}
            icon={XCircle}
            supporting="Tidak digunakan"
            variant="default"
          />
        </div>
      )}

      {/* Table Card */}
      <Card padding="none">
        {/* Filters */}
        <div className="border-b border-border px-5 py-4">
          <div className="flex flex-wrap items-center gap-3">
            <SearchInput 
              value={search} 
              onChange={(v) => { setSearch(v); setPage(1); }} 
              placeholder="Cari nama atau kode aset..." 
              className="w-64" 
            />
            <Select 
              options={CATEGORY_OPTIONS} 
              value={category} 
              onChange={(e) => { setCategory(e.target.value); setPage(1); }} 
              className="w-44 h-9 text-sm" 
            />
            <Select 
              options={STATUS_OPTIONS} 
              value={status} 
              onChange={(e) => { setStatus(e.target.value); setPage(1); }} 
              className="w-40 h-9 text-sm" 
            />
            <Select 
              options={KONDISI_OPTIONS} 
              value={kondisi} 
              onChange={(e) => { setKondisi(e.target.value); setPage(1); }} 
              className="w-40 h-9 text-sm" 
            />
            {hasActiveFilters && (
              <Button 
                variant="secondary" 
                size="sm" 
                leftIcon={<X className="h-3.5 w-3.5" />}
                onClick={resetFilters}
              >
                Reset Filter
              </Button>
            )}
          </div>
        </div>

        {/* Table */}
        {loading ? (
          <PageLoader />
        ) : !result || result.data.length === 0 ? (
          <EmptyState
            title={hasActiveFilters ? "Tidak ada aset yang sesuai" : "Belum ada aset terdaftar"}
            description={hasActiveFilters ? "Coba ubah filter pencarian Anda." : "Tambahkan aset baru untuk memulai."}
            action={{ 
              label: hasActiveFilters ? 'Reset Filter' : 'Tambah Aset', 
              onClick: hasActiveFilters ? resetFilters : () => { setEditTarget(null); setModalOpen(true); } 
            }}
          />
        ) : (
          <div className="overflow-x-auto">
            <table className="w-full text-sm">
              <thead>
                <tr className="border-b border-border bg-surface-muted">
                  <th className="px-5 py-3 text-left text-xs font-semibold uppercase tracking-wider text-text-secondary">
                    Kode / Nama
                  </th>
                  <th className="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wider text-text-secondary">
                    Kategori
                  </th>
                  <th className="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wider text-text-secondary">
                    Lokasi
                  </th>
                  <th className="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wider text-text-secondary">
                    Kondisi
                  </th>
                  <th className="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wider text-text-secondary">
                    Status
                  </th>
                  <th className="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wider text-text-secondary">
                    Tanggal Perolehan
                  </th>
                  <th className="px-4 py-3 text-right text-xs font-semibold uppercase tracking-wider text-text-secondary">
                    Aksi
                  </th>
                </tr>
              </thead>
              <tbody className="divide-y divide-border">
                {result.data.map((asset) => (
                  <tr key={asset.id} className="hover:bg-surface-muted/50 transition-colors">
                    <td className="px-5 py-3.5">
                      <p className="font-medium text-text-primary">{asset.nama}</p>
                      <p className="text-xs text-text-muted">{asset.kode}</p>
                    </td>
                    <td className="px-4 py-3.5 text-text-secondary">{asset.kategori}</td>
                    <td className="px-4 py-3.5 text-text-secondary">{asset.lokasi}</td>
                    <td className="px-4 py-3.5">{kondisiBadge(asset.kondisi)}</td>
                    <td className="px-4 py-3.5">{statusBadge(asset.status)}</td>
                    <td className="px-4 py-3.5 text-text-secondary">{formatDate(asset.tanggal_perolehan)}</td>
                    <td className="px-4 py-3.5 text-right">
                      <div className="flex items-center justify-end gap-1">
                        <button 
                          onClick={() => setViewTarget(asset)} 
                          className="rounded p-1.5 text-text-muted hover:bg-surface-muted hover:text-text-primary transition-colors" 
                          title="Detail"
                        >
                          <Eye className="h-4 w-4" />
                        </button>
                        <button 
                          onClick={() => { setEditTarget(asset); setModalOpen(true); }} 
                          className="rounded p-1.5 text-text-muted hover:bg-surface-muted hover:text-text-primary transition-colors" 
                          title="Edit"
                        >
                          <Pencil className="h-4 w-4" />
                        </button>
                        <button 
                          onClick={() => setDeleteTarget(asset)} 
                          className="rounded p-1.5 text-text-muted hover:bg-red-50 hover:text-status-error transition-colors" 
                          title="Hapus"
                        >
                          <Trash2 className="h-4 w-4" />
                        </button>
                      </div>
                    </td>
                  </tr>
                ))}
              </tbody>
            </table>
          </div>
        )}

        {/* Pagination */}
        {result && result.meta.last_page > 1 && (
          <div className="border-t border-border px-5 py-3">
            <Pagination {...result.meta} onPageChange={setPage} />
          </div>
        )}
      </Card>

      {/* Detail Modal */}
      {viewTarget && (
        <div className="fixed inset-0 z-50 flex items-center justify-center p-4">
          <div className="absolute inset-0 bg-black/40" onClick={() => setViewTarget(null)} />
          <div className="relative w-full max-w-2xl rounded-xl border border-border bg-white shadow-dropdown">
            <div className="flex items-center justify-between border-b border-border px-6 py-4">
              <div>
                <h2 className="text-lg font-semibold text-text-primary">{viewTarget.nama}</h2>
                <p className="text-sm text-text-muted">{viewTarget.kode}</p>
              </div>
              <button 
                onClick={() => setViewTarget(null)} 
                className="rounded p-1.5 text-text-muted hover:bg-surface-muted transition-colors"
              >
                <X className="h-4 w-4" />
              </button>
            </div>
            <div className="max-h-[70vh] overflow-y-auto px-6 py-5">
              <div className="grid grid-cols-1 gap-6 sm:grid-cols-2">
                {/* Basic Info */}
                <div>
                  <h3 className="mb-3 text-sm font-semibold text-text-primary">Informasi Dasar</h3>
                  <dl className="space-y-2.5 text-sm">
                    <div>
                      <dt className="font-medium text-text-secondary">Kategori</dt>
                      <dd className="mt-0.5 text-text-primary">{viewTarget.kategori}</dd>
                    </div>
                    <div>
                      <dt className="font-medium text-text-secondary">Pemilik / Unit</dt>
                      <dd className="mt-0.5 text-text-primary">{viewTarget.pemilik}</dd>
                    </div>
                    <div>
                      <dt className="font-medium text-text-secondary">Lokasi</dt>
                      <dd className="mt-0.5 text-text-primary">{viewTarget.lokasi}</dd>
                    </div>
                    <div>
                      <dt className="font-medium text-text-secondary">PIC / Penanggung Jawab</dt>
                      <dd className="mt-0.5 text-text-primary">{viewTarget.pic || '—'}</dd>
                    </div>
                  </dl>
                </div>

                {/* Status & Value */}
                <div>
                  <h3 className="mb-3 text-sm font-semibold text-text-primary">Status & Nilai</h3>
                  <dl className="space-y-2.5 text-sm">
                    <div>
                      <dt className="font-medium text-text-secondary">Kondisi</dt>
                      <dd className="mt-0.5">{kondisiBadge(viewTarget.kondisi)}</dd>
                    </div>
                    <div>
                      <dt className="font-medium text-text-secondary">Status</dt>
                      <dd className="mt-0.5">{statusBadge(viewTarget.status)}</dd>
                    </div>
                    <div>
                      <dt className="font-medium text-text-secondary">Tanggal Perolehan</dt>
                      <dd className="mt-0.5 text-text-primary">{formatDate(viewTarget.tanggal_perolehan)}</dd>
                    </div>
                    <div>
                      <dt className="font-medium text-text-secondary">Nilai Perolehan</dt>
                      <dd className="mt-0.5 font-medium text-text-primary">{formatCurrency(viewTarget.nilai_aset)}</dd>
                    </div>
                  </dl>
                </div>

                {/* CIA Assessment */}
                <div className="sm:col-span-2">
                  <h3 className="mb-3 text-sm font-semibold text-text-primary">Penilaian CIA</h3>
                  <dl className="grid grid-cols-3 gap-4 text-sm">
                    <div>
                      <dt className="font-medium text-text-secondary">Kerahasiaan</dt>
                      <dd className="mt-0.5">
                        <span className="text-text-primary font-medium">{viewTarget.kerahasiaan}</span>
                        <span className="text-xs text-text-muted ml-1">— {ciaLabel(viewTarget.kerahasiaan)}</span>
                      </dd>
                    </div>
                    <div>
                      <dt className="font-medium text-text-secondary">Integritas</dt>
                      <dd className="mt-0.5">
                        <span className="text-text-primary font-medium">{viewTarget.integritas}</span>
                        <span className="text-xs text-text-muted ml-1">— {ciaLabel(viewTarget.integritas)}</span>
                      </dd>
                    </div>
                    <div>
                      <dt className="font-medium text-text-secondary">Ketersediaan</dt>
                      <dd className="mt-0.5">
                        <span className="text-text-primary font-medium">{viewTarget.ketersediaan}</span>
                        <span className="text-xs text-text-muted ml-1">— {ciaLabel(viewTarget.ketersediaan)}</span>
                      </dd>
                    </div>
                  </dl>
                </div>

                {/* Description */}
                {viewTarget.deskripsi && (
                  <div className="sm:col-span-2">
                    <h3 className="mb-2 text-sm font-semibold text-text-primary">Deskripsi</h3>
                    <p className="text-sm text-text-secondary leading-relaxed">{viewTarget.deskripsi}</p>
                  </div>
                )}
              </div>
            </div>
          </div>
        </div>
      )}

      {/* Add/Edit Modal */}
      <AssetModal
        open={modalOpen}
        onClose={() => setModalOpen(false)}
        initialData={editTarget}
        onSaved={() => { setModalOpen(false); fetchData(); }}
      />

      {/* Delete Confirmation */}
      <ConfirmDialog
        open={!!deleteTarget}
        title="Hapus Aset"
        message={`Yakin ingin menghapus aset "${deleteTarget?.nama}" (${deleteTarget?.kode})? Tindakan ini tidak dapat dibatalkan.`}
        confirmLabel="Hapus"
        loading={deleting}
        onConfirm={handleDelete}
        onCancel={() => setDeleteTarget(null)}
      />
    </div>
  );
}
