import { useEffect, useState, useCallback } from 'react';
import { Plus, Pencil, Trash2 } from 'lucide-react';
import { vulnerabilityService } from '@/services/vulnerability.service';
import type { Vulnerability } from '@/types/risk.types';
import type { RiskLevel } from '@/types/common.types';
import { PageHeader } from '@/components/common/PageHeader';
import { SearchInput } from '@/components/common/SearchInput';
import { Button } from '@/components/ui/Button';
import { Badge } from '@/components/ui/Badge';
import type { BadgeVariant } from '@/types/badge.types';
import { Card } from '@/components/ui/Card';
import { Pagination } from '@/components/ui/Pagination';
import { PageLoader } from '@/components/ui/LoadingSpinner';
import { EmptyState } from '@/components/ui/EmptyState';
import { ConfirmDialog } from '@/components/ui/ConfirmDialog';
import { Select } from '@/components/ui/Select';
import { Modal } from '@/components/ui/Modal';
import { Input } from '@/components/ui/Input';
import { useForm } from 'react-hook-form';
import { zodResolver } from '@hookform/resolvers/zod';
import { z } from 'zod';

const schema = z.object({
  kode: z.string().min(1),
  nama: z.string().min(1),
  deskripsi: z.string().optional(),
  aset_terkait: z.string().optional(),
  severitas: z.string().min(1),
  status: z.string().min(1),
});
type FormValues = z.infer<typeof schema>;

function severitasVariant(s: RiskLevel): BadgeVariant { return s; }
function severitasLabel(s: RiskLevel) { return { critical: 'Kritis', high: 'Tinggi', medium: 'Sedang', low: 'Rendah' }[s]; }

function statusVariant(s: Vulnerability['status']): BadgeVariant {
  return { terbuka: 'high', dimitigasi: 'success', diterima: 'warning', ditutup: 'neutral' }[s] as BadgeVariant;
}
function statusLabel(s: Vulnerability['status']) {
  return { terbuka: 'Terbuka', dimitigasi: 'Dimitigasi', diterima: 'Diterima', ditutup: 'Ditutup' }[s];
}

export function VulnerabilitiesPage() {
  const [vulns, setVulns] = useState<Vulnerability[]>([]);
  const [meta, setMeta] = useState<{ current_page: number; last_page: number; total: number; per_page: number; from: number | null; to: number | null }>({ current_page: 1, last_page: 1, total: 0, per_page: 10, from: 1, to: 1 });
  const [loading, setLoading] = useState(true);
  const [search, setSearch] = useState('');
  const [severitas, setSeveritas] = useState('');
  const [status, setStatus] = useState('');
  const [page, setPage] = useState(1);
  const [modalOpen, setModalOpen] = useState(false);
  const [editTarget, setEditTarget] = useState<Vulnerability | null>(null);
  const [deleteTarget, setDeleteTarget] = useState<Vulnerability | null>(null);
  const [deleting, setDeleting] = useState(false);

  const { register, handleSubmit, reset, formState: { errors, isSubmitting } } = useForm<FormValues>({
    resolver: zodResolver(schema),
    defaultValues: { status: 'terbuka', severitas: 'medium' },
  });

  const fetchData = useCallback(() => {
    setLoading(true);
    vulnerabilityService.list({ q: search, severitas: severitas || undefined, status: status || undefined, page })
      .then((r) => { setVulns(r.data); setMeta(r.meta); })
      .finally(() => setLoading(false));
  }, [search, severitas, status, page]);

  useEffect(() => { fetchData(); }, [fetchData]);

  const openAdd = () => { setEditTarget(null); reset({ status: 'terbuka', severitas: 'medium' }); setModalOpen(true); };
  const openEdit = (v: Vulnerability) => { setEditTarget(v); reset({ kode: v.kode, nama: v.nama, deskripsi: v.deskripsi ?? '', aset_terkait: v.aset_terkait ?? '', severitas: v.severitas, status: v.status }); setModalOpen(true); };

  const onSubmit = async (data: FormValues) => {
    const payload = { ...data, severitas: data.severitas as RiskLevel, status: data.status as Vulnerability['status'], deskripsi: data.deskripsi ?? null, aset_terkait: data.aset_terkait ?? null };
    if (editTarget) await vulnerabilityService.update(editTarget.id, payload);
    else await vulnerabilityService.create(payload);
    setModalOpen(false);
    fetchData();
  };

  const handleDelete = async () => {
    if (!deleteTarget) return;
    setDeleting(true);
    await vulnerabilityService.delete(deleteTarget.id);
    setDeleting(false);
    setDeleteTarget(null);
    fetchData();
  };

  return (
    <div className="space-y-5">
      <PageHeader title="Manajemen Kerentanan" description="Daftar kerentanan yang teridentifikasi pada sistem dan aset"
        action={<Button leftIcon={<Plus className="h-4 w-4" />} onClick={openAdd}>Tambah Kerentanan</Button>}
      />

      <Card padding="none">
        <div className="flex flex-wrap gap-3 border-b border-border px-5 py-4">
          <SearchInput value={search} onChange={(v) => { setSearch(v); setPage(1); }} placeholder="Cari kerentanan..." className="w-64" />
          <Select options={[{ value: '', label: 'Semua Severitas' }, { value: 'critical', label: 'Kritis' }, { value: 'high', label: 'Tinggi' }, { value: 'medium', label: 'Sedang' }, { value: 'low', label: 'Rendah' }]} value={severitas} onChange={(e) => { setSeveritas(e.target.value); setPage(1); }} className="w-40 h-9 text-sm" />
          <Select options={[{ value: '', label: 'Semua Status' }, { value: 'terbuka', label: 'Terbuka' }, { value: 'dimitigasi', label: 'Dimitigasi' }, { value: 'diterima', label: 'Diterima' }, { value: 'ditutup', label: 'Ditutup' }]} value={status} onChange={(e) => { setStatus(e.target.value); setPage(1); }} className="w-36 h-9 text-sm" />
        </div>

        {loading ? <PageLoader /> : vulns.length === 0 ? (
          <EmptyState title="Tidak ada kerentanan ditemukan" action={{ label: 'Tambah', onClick: openAdd }} />
        ) : (
          <div className="overflow-x-auto">
            <table className="w-full text-sm">
              <thead>
                <tr className="border-b border-border bg-surface-muted">
                  <th className="px-5 py-3 text-left text-xs font-semibold uppercase tracking-wider text-text-secondary">Kode / Nama</th>
                  <th className="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wider text-text-secondary">Aset Terkait</th>
                  <th className="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wider text-text-secondary">Severitas</th>
                  <th className="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wider text-text-secondary">Status</th>
                  <th className="px-4 py-3 text-right text-xs font-semibold uppercase tracking-wider text-text-secondary">Aksi</th>
                </tr>
              </thead>
              <tbody className="divide-y divide-border">
                {vulns.map((v) => (
                  <tr key={v.id} className="hover:bg-surface-muted/50">
                    <td className="px-5 py-3.5">
                      <p className="font-medium text-text-primary">{v.nama}</p>
                      <p className="text-xs text-text-muted">{v.kode}</p>
                    </td>
                    <td className="px-4 py-3.5 text-text-secondary">{v.aset_terkait ?? '—'}</td>
                    <td className="px-4 py-3.5"><Badge variant={severitasVariant(v.severitas)}>{severitasLabel(v.severitas)}</Badge></td>
                    <td className="px-4 py-3.5"><Badge variant={statusVariant(v.status)}>{statusLabel(v.status)}</Badge></td>
                    <td className="px-4 py-3.5 text-right">
                      <div className="flex justify-end gap-1">
                        <button onClick={() => openEdit(v)} className="rounded p-1.5 text-text-muted hover:bg-surface-muted"><Pencil className="h-4 w-4" /></button>
                        <button onClick={() => setDeleteTarget(v)} className="rounded p-1.5 text-text-muted hover:bg-red-50 hover:text-status-error"><Trash2 className="h-4 w-4" /></button>
                      </div>
                    </td>
                  </tr>
                ))}
              </tbody>
            </table>
          </div>
        )}

        {meta.last_page > 1 && (
          <div className="border-t border-border px-5 py-3">
            <Pagination {...meta} onPageChange={setPage} />
          </div>
        )}
      </Card>

      <Modal open={modalOpen} onClose={() => setModalOpen(false)} title={editTarget ? 'Edit Kerentanan' : 'Tambah Kerentanan'}
        footer={<><Button variant="secondary" onClick={() => setModalOpen(false)} disabled={isSubmitting}>Batal</Button><Button form="vuln-form" type="submit" loading={isSubmitting}>{editTarget ? 'Simpan' : 'Tambah'}</Button></>}
      >
        <form id="vuln-form" onSubmit={handleSubmit(onSubmit)} className="grid grid-cols-1 gap-4 sm:grid-cols-2">
          <Input label="Kode" required error={errors.kode?.message} {...register('kode')} />
          <Input label="Nama" required error={errors.nama?.message} {...register('nama')} />
          <Input label="Aset Terkait" error={errors.aset_terkait?.message} {...register('aset_terkait')} />
          <Select label="Severitas" required options={[{ value: 'critical', label: 'Kritis' }, { value: 'high', label: 'Tinggi' }, { value: 'medium', label: 'Sedang' }, { value: 'low', label: 'Rendah' }]} error={errors.severitas?.message} {...register('severitas')} />
          <Select label="Status" required options={[{ value: 'terbuka', label: 'Terbuka' }, { value: 'dimitigasi', label: 'Dimitigasi' }, { value: 'diterima', label: 'Diterima' }, { value: 'ditutup', label: 'Ditutup' }]} error={errors.status?.message} {...register('status')} />
          <div className="sm:col-span-2">
            <Input label="Deskripsi" error={errors.deskripsi?.message} {...register('deskripsi')} />
          </div>
        </form>
      </Modal>

      <ConfirmDialog open={!!deleteTarget} title="Hapus Kerentanan" message={`Yakin menghapus "${deleteTarget?.nama}"?`} confirmLabel="Hapus" loading={deleting} onConfirm={handleDelete} onCancel={() => setDeleteTarget(null)} />
    </div>
  );
}
