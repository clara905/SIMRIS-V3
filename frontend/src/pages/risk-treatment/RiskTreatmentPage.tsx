import { useEffect, useState, useCallback } from 'react';
import { Plus, Pencil, Trash2 } from 'lucide-react';
import { riskService } from '@/services/risk.service';
import type { RiskTreatment, TreatmentStrategy, TreatmentStatus } from '@/types/risk.types';
import type { BadgeVariant } from '@/types/badge.types';
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
import { Modal } from '@/components/ui/Modal';
import { Input } from '@/components/ui/Input';
import { useForm } from 'react-hook-form';
import { zodResolver } from '@hookform/resolvers/zod';
import { z } from 'zod';

const schema = z.object({
  risiko_ringkasan: z.string().min(1),
  strategi: z.string().min(1),
  rencana: z.string().min(1),
  penanggung_jawab: z.string().min(1),
  tenggat_waktu: z.string().min(1),
  status: z.string().min(1),
  progres: z.coerce.number().min(0).max(100),
  catatan: z.string().optional(),
});
type FormValues = z.infer<typeof schema>;

function statusVariant(s: TreatmentStatus): BadgeVariant {
  return { belum_dimulai: 'neutral', sedang_berjalan: 'info', selesai: 'success', terlambat: 'critical' }[s] as BadgeVariant;
}
function statusLabel(s: TreatmentStatus) {
  return { belum_dimulai: 'Belum Dimulai', sedang_berjalan: 'Sedang Berjalan', selesai: 'Selesai', terlambat: 'Terlambat' }[s];
}
function strategiLabel(s: TreatmentStrategy) {
  return { mitigasi: 'Mitigasi', hindari: 'Hindari', transfer: 'Transfer', terima: 'Terima' }[s];
}

function ProgressBar({ value }: { value: number }) {
  const color = value >= 100 ? 'bg-green-500' : value >= 60 ? 'bg-blue-500' : value >= 30 ? 'bg-amber-400' : 'bg-red-500';
  return (
    <div className="w-24">
      <div className="mb-0.5 flex items-center justify-between gap-1">
        <span className="text-xs text-text-muted">{value}%</span>
      </div>
      <div className="h-1.5 w-full rounded-full bg-border">
        <div className={`h-1.5 rounded-full ${color}`} style={{ width: `${value}%` }} />
      </div>
    </div>
  );
}

export function RiskTreatmentPage() {
  const [items, setItems] = useState<RiskTreatment[]>([]);
  const [meta, setMeta] = useState<{ current_page: number; last_page: number; total: number; per_page: number; from: number | null; to: number | null }>({ current_page: 1, last_page: 1, total: 0, per_page: 10, from: 1, to: 1 });
  const [loading, setLoading] = useState(true);
  const [search, setSearch] = useState('');
  const [status, setStatus] = useState('');
  const [page, setPage] = useState(1);
  const [modalOpen, setModalOpen] = useState(false);
  const [editTarget, setEditTarget] = useState<RiskTreatment | null>(null);
  const [deleteTarget, setDeleteTarget] = useState<RiskTreatment | null>(null);
  const [deleting, setDeleting] = useState(false);

  const { register, handleSubmit, reset, formState: { errors, isSubmitting } } = useForm<FormValues>({
    resolver: zodResolver(schema),
    defaultValues: { status: 'belum_dimulai', strategi: 'mitigasi', progres: 0 },
  });

  const fetchData = useCallback(() => {
    setLoading(true);
    riskService.listTreatments({ q: search, status: status || undefined, page })
      .then((r) => { setItems(r.data); setMeta(r.meta); })
      .finally(() => setLoading(false));
  }, [search, status, page]);

  useEffect(() => { fetchData(); }, [fetchData]);

  const openAdd = () => { setEditTarget(null); reset({ status: 'belum_dimulai', strategi: 'mitigasi', progres: 0 }); setModalOpen(true); };
  const openEdit = (item: RiskTreatment) => {
    setEditTarget(item);
    reset({ risiko_ringkasan: item.risiko_ringkasan, strategi: item.strategi, rencana: item.rencana, penanggung_jawab: item.penanggung_jawab, tenggat_waktu: item.tenggat_waktu, status: item.status, progres: item.progres, catatan: item.catatan ?? '' });
    setModalOpen(true);
  };

  const onSubmit = async (data: FormValues) => {
    const payload = { ...data, penilaian_risiko_id: editTarget?.penilaian_risiko_id ?? 0, strategi: data.strategi as TreatmentStrategy, status: data.status as TreatmentStatus, catatan: data.catatan ?? null };
    if (editTarget) await riskService.updateTreatment(editTarget.id, payload);
    else await riskService.createTreatment(payload);
    setModalOpen(false);
    fetchData();
  };

  const handleDelete = async () => {
    if (!deleteTarget) return;
    setDeleting(true);
    await riskService.deleteTreatment(deleteTarget.id);
    setDeleting(false);
    setDeleteTarget(null);
    fetchData();
  };

  return (
    <div className="space-y-5">
      <PageHeader title="Penanganan Risiko" description="Rencana dan progres penanganan risiko yang telah diidentifikasi"
        action={<Button leftIcon={<Plus className="h-4 w-4" />} onClick={openAdd}>Rencana Baru</Button>}
      />

      <Card padding="none">
        <div className="flex flex-wrap gap-3 border-b border-border px-5 py-4">
          <SearchInput value={search} onChange={(v) => { setSearch(v); setPage(1); }} placeholder="Cari risiko..." className="w-64" />
          <Select options={[{ value: '', label: 'Semua Status' }, { value: 'belum_dimulai', label: 'Belum Dimulai' }, { value: 'sedang_berjalan', label: 'Sedang Berjalan' }, { value: 'selesai', label: 'Selesai' }, { value: 'terlambat', label: 'Terlambat' }]} value={status} onChange={(e) => { setStatus(e.target.value); setPage(1); }} className="w-44 h-9 text-sm" />
        </div>

        {loading ? <PageLoader /> : items.length === 0 ? (
          <EmptyState title="Tidak ada rencana penanganan" action={{ label: 'Tambah Rencana', onClick: openAdd }} />
        ) : (
          <div className="overflow-x-auto">
            <table className="w-full text-sm">
              <thead>
                <tr className="border-b border-border bg-surface-muted">
                  <th className="px-5 py-3 text-left text-xs font-semibold uppercase tracking-wider text-text-secondary">Risiko</th>
                  <th className="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wider text-text-secondary">Strategi</th>
                  <th className="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wider text-text-secondary">Penanggung Jawab</th>
                  <th className="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wider text-text-secondary">Tenggat</th>
                  <th className="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wider text-text-secondary">Progres</th>
                  <th className="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wider text-text-secondary">Status</th>
                  <th className="px-4 py-3 text-right text-xs font-semibold uppercase tracking-wider text-text-secondary">Aksi</th>
                </tr>
              </thead>
              <tbody className="divide-y divide-border">
                {items.map((item) => (
                  <tr key={item.id} className="hover:bg-surface-muted/50">
                    <td className="px-5 py-3.5">
                      <p className="font-medium text-text-primary">{item.risiko_ringkasan}</p>
                      {item.catatan && <p className="text-xs text-text-muted truncate max-w-[200px]">{item.catatan}</p>}
                    </td>
                    <td className="px-4 py-3.5"><Badge variant="neutral">{strategiLabel(item.strategi)}</Badge></td>
                    <td className="px-4 py-3.5 text-text-secondary">{item.penanggung_jawab}</td>
                    <td className="px-4 py-3.5 text-text-secondary">{item.tenggat_waktu}</td>
                    <td className="px-4 py-3.5"><ProgressBar value={item.progres} /></td>
                    <td className="px-4 py-3.5"><Badge variant={statusVariant(item.status)}>{statusLabel(item.status)}</Badge></td>
                    <td className="px-4 py-3.5 text-right">
                      <div className="flex justify-end gap-1">
                        <button onClick={() => openEdit(item)} className="rounded p-1.5 text-text-muted hover:bg-surface-muted"><Pencil className="h-4 w-4" /></button>
                        <button onClick={() => setDeleteTarget(item)} className="rounded p-1.5 text-text-muted hover:bg-red-50 hover:text-status-error"><Trash2 className="h-4 w-4" /></button>
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

      <Modal open={modalOpen} onClose={() => setModalOpen(false)} title={editTarget ? 'Edit Penanganan' : 'Rencana Penanganan Baru'} size="lg"
        footer={<><Button variant="secondary" onClick={() => setModalOpen(false)} disabled={isSubmitting}>Batal</Button><Button form="treatment-form" type="submit" loading={isSubmitting}>{editTarget ? 'Simpan' : 'Buat Rencana'}</Button></>}
      >
        <form id="treatment-form" onSubmit={handleSubmit(onSubmit)} className="grid grid-cols-1 gap-4 sm:grid-cols-2">
          <div className="sm:col-span-2">
            <Input label="Ringkasan Risiko" required error={errors.risiko_ringkasan?.message} {...register('risiko_ringkasan')} />
          </div>
          <div className="sm:col-span-2">
            <Input label="Rencana Penanganan" required error={errors.rencana?.message} {...register('rencana')} />
          </div>
          <Select label="Strategi" required options={[{ value: 'mitigasi', label: 'Mitigasi' }, { value: 'hindari', label: 'Hindari' }, { value: 'transfer', label: 'Transfer' }, { value: 'terima', label: 'Terima' }]} error={errors.strategi?.message} {...register('strategi')} />
          <Input label="Penanggung Jawab" required error={errors.penanggung_jawab?.message} {...register('penanggung_jawab')} />
          <Input label="Tenggat Waktu" type="date" required error={errors.tenggat_waktu?.message} {...register('tenggat_waktu')} />
          <Input label="Progres (%)" type="number" required error={errors.progres?.message} {...register('progres')} />
          <Select label="Status" required options={[{ value: 'belum_dimulai', label: 'Belum Dimulai' }, { value: 'sedang_berjalan', label: 'Sedang Berjalan' }, { value: 'selesai', label: 'Selesai' }, { value: 'terlambat', label: 'Terlambat' }]} error={errors.status?.message} {...register('status')} />
          <div className="sm:col-span-2">
            <Input label="Catatan" error={errors.catatan?.message} {...register('catatan')} />
          </div>
        </form>
      </Modal>

      <ConfirmDialog open={!!deleteTarget} title="Hapus Rencana" message={`Yakin menghapus rencana penanganan "${deleteTarget?.risiko_ringkasan}"?`} confirmLabel="Hapus" loading={deleting} onConfirm={handleDelete} onCancel={() => setDeleteTarget(null)} />
    </div>
  );
}
