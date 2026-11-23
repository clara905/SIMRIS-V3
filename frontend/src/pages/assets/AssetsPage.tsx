import { useEffect, useState, useCallback } from 'react';
import { Plus, Eye, Pencil, Trash2 } from 'lucide-react';
import { assetService } from '@/services/asset.service';
import type { Asset, AssetCategory } from '@/types/risk.types';
import type { PaginatedResponse } from '@/types/common.types';
import { PageHeader } from '@/components/common/PageHeader';
import { SearchInput } from '@/components/common/SearchInput';
import { Button } from '@/components/ui/Button';
import { Badge } from '@/components/ui/Badge';
import { Card } from '@/components/ui/Card';
import { Pagination } from '@/components/ui/Pagination';
import { PageLoader } from '@/components/ui/LoadingSpinner';
import { EmptyState } from '@/components/ui/EmptyState';
import { ConfirmDialog } from '@/components/ui/ConfirmDialog';
import { Select } from '@/components/ui/Select';
import { AssetModal } from './AssetModal';

const CATEGORY_OPTIONS = [
  { value: '', label: 'Semua Kategori' },
  ...(['Hardware', 'Software', 'Data', 'Jaringan', 'SDM', 'Layanan', 'Fasilitas', 'Lainnya'] as AssetCategory[]).map((c) => ({ value: c, label: c })),
];

const STATUS_OPTIONS = [
  { value: '', label: 'Semua Status' },
  { value: 'aktif', label: 'Aktif' },
  { value: 'nonaktif', label: 'Nonaktif' },
  { value: 'review', label: 'Review' },
];

function statusBadge(status: Asset['status']) {
  if (status === 'aktif') return <Badge variant="success">Aktif</Badge>;
  if (status === 'nonaktif') return <Badge variant="neutral">Nonaktif</Badge>;
  return <Badge variant="warning">Review</Badge>;
}

function ciaLabel(val: number) {
  const labels = ['', 'Sangat Rendah', 'Rendah', 'Sedang', 'Tinggi', 'Sangat Tinggi'];
  return labels[val] ?? val;
}

export function AssetsPage() {
  const [result, setResult] = useState<PaginatedResponse<Asset> | null>(null);
  const [loading, setLoading] = useState(true);
  const [search, setSearch] = useState('');
  const [category, setCategory] = useState('');
  const [status, setStatus] = useState('');
  const [page, setPage] = useState(1);
  const [modalOpen, setModalOpen] = useState(false);
  const [editTarget, setEditTarget] = useState<Asset | null>(null);
  const [deleteTarget, setDeleteTarget] = useState<Asset | null>(null);
  const [deleting, setDeleting] = useState(false);
  const [viewTarget, setViewTarget] = useState<Asset | null>(null);

  const fetchData = useCallback(() => {
    setLoading(true);
    assetService.list({ q: search, kategori: category || undefined, status: status || undefined, page })
      .then(setResult)
      .finally(() => setLoading(false));
  }, [search, category, status, page]);

  useEffect(() => { fetchData(); }, [fetchData]);

  const handleDelete = async () => {
    if (!deleteTarget) return;
    setDeleting(true);
    await assetService.delete(deleteTarget.id);
    setDeleting(false);
    setDeleteTarget(null);
    fetchData();
  };

  return (
    <div className="space-y-5">
      <PageHeader
        title="Manajemen Aset"
        description="Inventaris aset informasi organisasi"
        action={
          <Button leftIcon={<Plus className="h-4 w-4" />} onClick={() => { setEditTarget(null); setModalOpen(true); }}>
            Tambah Aset
          </Button>
        }
      />

      <Card padding="none">
        {/* Filters */}
        <div className="flex flex-wrap items-center gap-3 border-b border-border px-5 py-4">
          <SearchInput value={search} onChange={(v) => { setSearch(v); setPage(1); }} placeholder="Cari nama atau kode aset..." className="w-64" />
          <Select options={CATEGORY_OPTIONS} value={category} onChange={(e) => { setCategory(e.target.value); setPage(1); }} className="w-44 h-9 text-sm" />
          <Select options={STATUS_OPTIONS} value={status} onChange={(e) => { setStatus(e.target.value); setPage(1); }} className="w-36 h-9 text-sm" />
        </div>

        {/* Table */}
        {loading ? (
          <PageLoader />
        ) : !result || result.data.length === 0 ? (
          <EmptyState
            title="Tidak ada aset ditemukan"
            description="Tambahkan aset baru atau ubah filter pencarian."
            action={{ label: 'Tambah Aset', onClick: () => { setEditTarget(null); setModalOpen(true); } }}
          />
        ) : (
          <div className="overflow-x-auto">
            <table className="w-full text-sm">
              <thead>
                <tr className="border-b border-border bg-surface-muted">
                  <th className="px-5 py-3 text-left text-xs font-semibold uppercase tracking-wider text-text-secondary">Kode / Nama</th>
                  <th className="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wider text-text-secondary">Kategori</th>
                  <th className="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wider text-text-secondary">Pemilik</th>
                  <th className="px-4 py-3 text-center text-xs font-semibold uppercase tracking-wider text-text-secondary">K/I/A</th>
                  <th className="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wider text-text-secondary">Status</th>
                  <th className="px-4 py-3 text-right text-xs font-semibold uppercase tracking-wider text-text-secondary">Aksi</th>
                </tr>
              </thead>
              <tbody className="divide-y divide-border">
                {result.data.map((asset) => (
                  <tr key={asset.id} className="hover:bg-surface-muted/50 transition-colors">
                    <td className="px-5 py-3.5">
                      <p className="font-medium text-text-primary">{asset.nama}</p>
                      <p className="text-xs text-text-muted">{asset.kode} · {asset.lokasi}</p>
                    </td>
                    <td className="px-4 py-3.5 text-text-secondary">{asset.kategori}</td>
                    <td className="px-4 py-3.5 text-text-secondary">{asset.pemilik}</td>
                    <td className="px-4 py-3.5 text-center">
                      <span className="font-mono text-xs text-text-secondary">{asset.kerahasiaan}/{asset.integritas}/{asset.ketersediaan}</span>
                    </td>
                    <td className="px-4 py-3.5">{statusBadge(asset.status)}</td>
                    <td className="px-4 py-3.5 text-right">
                      <div className="flex items-center justify-end gap-1">
                        <button onClick={() => setViewTarget(asset)} className="rounded p-1.5 text-text-muted hover:bg-surface-muted hover:text-text-primary" title="Detail"><Eye className="h-4 w-4" /></button>
                        <button onClick={() => { setEditTarget(asset); setModalOpen(true); }} className="rounded p-1.5 text-text-muted hover:bg-surface-muted hover:text-text-primary" title="Edit"><Pencil className="h-4 w-4" /></button>
                        <button onClick={() => setDeleteTarget(asset)} className="rounded p-1.5 text-text-muted hover:bg-red-50 hover:text-status-error" title="Hapus"><Trash2 className="h-4 w-4" /></button>
                      </div>
                    </td>
                  </tr>
                ))}
              </tbody>
            </table>
          </div>
        )}

        {result && result.meta.last_page > 1 && (
          <div className="border-t border-border px-5 py-3">
            <Pagination {...result.meta} onPageChange={setPage} />
          </div>
        )}
      </Card>

      {/* Detail modal */}
      {viewTarget && (
        <div className="fixed inset-0 z-50 flex items-center justify-center p-4">
          <div className="absolute inset-0 bg-black/40" onClick={() => setViewTarget(null)} />
          <div className="relative w-full max-w-lg rounded-xl border border-border bg-white shadow-dropdown">
            <div className="flex items-center justify-between border-b border-border px-6 py-4">
              <div>
                <h2 className="text-base font-semibold">{viewTarget.nama}</h2>
                <p className="text-xs text-text-muted">{viewTarget.kode}</p>
              </div>
              <button onClick={() => setViewTarget(null)} className="rounded p-1 text-text-muted hover:bg-surface-muted">✕</button>
            </div>
            <div className="grid grid-cols-2 gap-x-6 gap-y-4 px-6 py-5 text-sm">
              {[
                ['Kategori', viewTarget.kategori],
                ['Pemilik', viewTarget.pemilik],
                ['Lokasi', viewTarget.lokasi],
                ['Status', viewTarget.status],
                ['Nilai Aset', `Rp ${viewTarget.nilai_aset.toLocaleString('id-ID')}`],
                ['Kerahasiaan', ciaLabel(viewTarget.kerahasiaan)],
                ['Integritas', ciaLabel(viewTarget.integritas)],
                ['Ketersediaan', ciaLabel(viewTarget.ketersediaan)],
              ].map(([label, val]) => (
                <div key={label}>
                  <p className="text-xs font-medium text-text-secondary">{label}</p>
                  <p className="mt-0.5 text-text-primary">{val}</p>
                </div>
              ))}
              {viewTarget.deskripsi && (
                <div className="col-span-2">
                  <p className="text-xs font-medium text-text-secondary">Deskripsi</p>
                  <p className="mt-0.5 text-text-primary">{viewTarget.deskripsi}</p>
                </div>
              )}
            </div>
          </div>
        </div>
      )}

      <AssetModal
        open={modalOpen}
        onClose={() => setModalOpen(false)}
        initialData={editTarget}
        onSaved={() => { setModalOpen(false); fetchData(); }}
      />

      <ConfirmDialog
        open={!!deleteTarget}
        title="Hapus Aset"
        message={`Yakin menghapus aset "${deleteTarget?.nama}"? Tindakan ini tidak dapat dibatalkan.`}
        confirmLabel="Hapus"
        loading={deleting}
        onConfirm={handleDelete}
        onCancel={() => setDeleteTarget(null)}
      />
    </div>
  );
}
