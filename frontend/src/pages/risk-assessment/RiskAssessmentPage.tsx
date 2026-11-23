import { useEffect, useState, useCallback } from 'react';
import { Plus, Eye, Pencil, Trash2, FileText, AlertCircle, CheckCircle2, Clock } from 'lucide-react';
import { asesmenService, type AsesmenSummary } from '@/services/asesmen.service';
import type { AsesmenRisiko } from '@/types/risk.types';
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


// ─── Helper Functions ────────────────────────────────────────────────────────

function statusBadge(status: AsesmenRisiko['status']) {
  if (status === 'active') return <Badge variant="success">Aktif</Badge>;
  if (status === 'draft') return <Badge variant="neutral">Draft</Badge>;
  if (status === 'completed') return <Badge variant="info">Selesai</Badge>;
  return <Badge variant="neutral">Arsip</Badge>;
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

export function RiskAssessmentPage() {
  const [summary, setSummary] = useState<AsesmenSummary | null>(null);
  const [result, setResult] = useState<PaginatedResponse<AsesmenRisiko> | null>(null);
  const [loading, setLoading] = useState(true);
  const [search, setSearch] = useState('');
  const [status, setStatus] = useState('');
  const [page, setPage] = useState(1);
  const [deleteTarget, setDeleteTarget] = useState<AsesmenRisiko | null>(null);
  const [deleting, setDeleting] = useState(false);

  const fetchData = useCallback(() => {
    setLoading(true);
    Promise.all([
      asesmenService.getSummary(),
      asesmenService.list({
        q: search,
        status: status || undefined,
        page,
      }),
    ])
      .then(([summaryData, assesmenData]) => {
        setSummary(summaryData);
        setResult(assesmenData);
      })
      .finally(() => setLoading(false));
  }, [search, status, page]);

  useEffect(() => {
    fetchData();
  }, [fetchData]);

  const handleDelete = async () => {
    if (!deleteTarget) return;
    setDeleting(true);
    try {
      await asesmenService.delete(deleteTarget.id);
      setDeleteTarget(null);
      fetchData();
    } catch (error) {
      console.error('Delete failed:', error);
    } finally {
      setDeleting(false);
    }
  };

  const hasActiveFilters = search || status;

  const resetFilters = () => {
    setSearch('');
    setStatus('');
    setPage(1);
  };

  // Count risks per assessment (from mock data)
  const getRiskCount = (asesmen_id: number) => {
    // This would come from service in real implementation
    if (asesmen_id === 1) return 2;
    if (asesmen_id === 2) return 1;
    if (asesmen_id === 3) return 0;
    return 0;
  };

  return (
    <div className="space-y-5">
      {/* Page Header */}
      <PageHeader
        title="Asesmen Risiko"
        description="Kelola asesmen risiko keamanan informasi untuk aset dan sistem organisasi."
        action={
          <Button leftIcon={<Plus className="h-4 w-4" />} onClick={() => alert('Fitur dalam pengembangan')}>
            Buat Asesmen Baru
          </Button>
        }
      />

      {/* Summary Cards */}
      {!loading && summary && (
        <div className="grid grid-cols-1 gap-4 sm:grid-cols-2 xl:grid-cols-4">
          <StatCard
            label="Total Asesmen"
            value={summary.totalAsesmen}
            icon={FileText}
            supporting="Semua asesmen terdaftar"
          />
          <StatCard
            label="Draft"
            value={summary.draft}
            icon={Clock}
            supporting="Belum dimulai"
            variant="default"
          />
          <StatCard
            label="Aktif"
            value={summary.active}
            icon={AlertCircle}
            supporting="Sedang berjalan"
            variant="warning"
          />
          <StatCard
            label="Selesai"
            value={summary.completed}
            icon={CheckCircle2}
            supporting="Telah diselesaikan"
            variant="success"
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
              onChange={(v) => {
                setSearch(v);
                setPage(1);
              }}
              placeholder="Cari kode atau nama asesmen..."
              className="w-64"
            />
            <Select
              options={[
                { value: '', label: 'Semua Status' },
                { value: 'draft', label: 'Draft' },
                { value: 'active', label: 'Aktif' },
                { value: 'completed', label: 'Selesai' },
                { value: 'archived', label: 'Arsip' },
              ]}
              value={status}
              onChange={(e) => {
                setStatus(e.target.value);
                setPage(1);
              }}
              className="w-40 h-9 text-sm"
            />
            {hasActiveFilters && (
              <Button
                variant="secondary"
                size="sm"
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
            title={hasActiveFilters ? 'Tidak ada asesmen yang sesuai' : 'Belum ada asesmen terdaftar'}
            description={
              hasActiveFilters
                ? 'Coba ubah filter pencarian Anda.'
                : 'Tambahkan asesmen baru untuk memulai penilaian risiko.'
            }
            action={{
              label: hasActiveFilters ? 'Reset Filter' : 'Buat Asesmen Baru',
              onClick: hasActiveFilters ? resetFilters : () => alert('Fitur dalam pengembangan'),
            }}
          />
        ) : (
          <div className="overflow-x-auto">
            <table className="w-full text-sm">
              <thead>
                <tr className="border-b border-border bg-surface-muted">
                  <th className="px-5 py-3 text-left text-xs font-semibold uppercase tracking-wider text-text-secondary">
                    Kode / Nama Asesmen
                  </th>
                  <th className="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wider text-text-secondary">
                    Seri / Metode
                  </th>
                  <th className="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wider text-text-secondary">
                    Unit
                  </th>
                  <th className="px-4 py-3 text-center text-xs font-semibold uppercase tracking-wider text-text-secondary">
                    Jumlah Risiko
                  </th>
                  <th className="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wider text-text-secondary">
                    Status
                  </th>
                  <th className="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wider text-text-secondary">
                    Terakhir Diperbarui
                  </th>
                  <th className="px-4 py-3 text-right text-xs font-semibold uppercase tracking-wider text-text-secondary">
                    Aksi
                  </th>
                </tr>
              </thead>
              <tbody className="divide-y divide-border">
                {result.data.map((asesmen) => (
                  <tr key={asesmen.id} className="hover:bg-surface-muted/50 transition-colors">
                    <td className="px-5 py-3.5">
                      <p className="font-medium text-text-primary">{asesmen.nama}</p>
                      <p className="text-xs text-text-muted">{asesmen.kode}</p>
                    </td>
                    <td className="px-4 py-3.5">
                      <p className="text-text-secondary">{asesmen.seri_asesmen_nama}</p>
                      <p className="text-xs text-text-muted">{asesmen.metode_nama}</p>
                    </td>
                    <td className="px-4 py-3.5 text-text-secondary">{asesmen.unit_nama}</td>
                    <td className="px-4 py-3.5 text-center">
                      <span className="inline-flex items-center justify-center rounded-full bg-brand-muted px-2 py-0.5 text-xs font-medium text-brand">
                        {getRiskCount(asesmen.id)} risiko
                      </span>
                    </td>
                    <td className="px-4 py-3.5">{statusBadge(asesmen.status)}</td>
                    <td className="px-4 py-3.5 text-text-secondary">{formatDate(asesmen.diperbarui_pada)}</td>
                    <td className="px-4 py-3.5 text-right">
                      <div className="flex items-center justify-end gap-1">
                        <button
                          onClick={() => alert('Fitur detail dalam pengembangan')}
                          className="rounded p-1.5 text-text-muted hover:bg-surface-muted hover:text-text-primary transition-colors"
                          title="Lihat Detail"
                        >
                          <Eye className="h-4 w-4" />
                        </button>
                        <button
                          onClick={() => alert('Fitur edit dalam pengembangan')}
                          className="rounded p-1.5 text-text-muted hover:bg-surface-muted hover:text-text-primary transition-colors"
                          title="Edit"
                        >
                          <Pencil className="h-4 w-4" />
                        </button>
                        <button
                          onClick={() => setDeleteTarget(asesmen)}
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

      {/* Delete Confirmation */}
      <ConfirmDialog
        open={!!deleteTarget}
        title="Hapus Asesmen Risiko"
        message={`Yakin ingin menghapus asesmen "${deleteTarget?.nama}" (${deleteTarget?.kode})? Semua risiko terkait akan ikut terhapus. Tindakan ini tidak dapat dibatalkan.`}
        confirmLabel="Hapus"
        loading={deleting}
        onConfirm={handleDelete}
        onCancel={() => setDeleteTarget(null)}
      />
    </div>
  );
}
