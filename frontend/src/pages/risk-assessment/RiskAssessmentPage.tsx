import { useEffect, useState, useCallback } from 'react';
import { Plus, Pencil, Trash2 } from 'lucide-react';
import { riskService } from '@/services/risk.service';
import type { RiskAssessment } from '@/types/risk.types';
import type { RiskLevel } from '@/types/common.types';
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
  aset_nama: z.string().min(1),
  ancaman_nama: z.string().min(1),
  kerentanan_nama: z.string().min(1),
  kemungkinan: z.coerce.number().min(1).max(5),
  dampak: z.coerce.number().min(1).max(5),
  tanggal_penilaian: z.string().min(1),
  penilai: z.string().min(1),
  status: z.string(),
});
type FormValues = z.infer<typeof schema>;

function levelVariant(l: RiskLevel): BadgeVariant { return l; }
function levelLabel(l: RiskLevel) { return { critical: 'Kritis', high: 'Tinggi', medium: 'Sedang', low: 'Rendah' }[l]; }
function statusVariant(s: RiskAssessment['status']): BadgeVariant { return { draft: 'neutral', disetujui: 'success', ditolak: 'high' }[s] as BadgeVariant; }
function statusLabel(s: RiskAssessment['status']) { return { draft: 'Draft', disetujui: 'Disetujui', ditolak: 'Ditolak' }[s]; }

const SCALE_OPTIONS = [1, 2, 3, 4, 5].map((v) => ({ value: v, label: `${v}` }));

export function RiskAssessmentPage() {
  const [items, setItems] = useState<RiskAssessment[]>([]);
  const [meta, setMeta] = useState<{ current_page: number; last_page: number; total: number; per_page: number; from: number | null; to: number | null }>({ current_page: 1, last_page: 1, total: 0, per_page: 10, from: 1, to: 1 });
  const [loading, setLoading] = useState(true);
  const [search, setSearch] = useState('');
  const [level, setLevel] = useState('');
  const [status, setStatus] = useState('');
  const [page, setPage] = useState(1);
  const [modalOpen, setModalOpen] = useState(false);
  const [editTarget, setEditTarget] = useState<RiskAssessment | null>(null);
  const [deleteTarget, setDeleteTarget] = useState<RiskAssessment | null>(null);
  const [deleting, setDeleting] = useState(false);
  const [previewScore, setPreviewScore] = useState<{ skor: number; level: RiskLevel } | null>(null);

  const { register, handleSubmit, reset, watch, formState: { errors, isSubmitting } } = useForm<FormValues>({
    resolver: zodResolver(schema),
    defaultValues: { status: 'draft', kemungkinan: 3, dampak: 3 },
  });

  const watchKemungkinan = watch('kemungkinan');
  const watchDampak = watch('dampak');

  useEffect(() => {
    const k = Number(watchKemungkinan);
    const d = Number(watchDampak);
    if (k && d) {
      const skor = k * d;
      const lv: RiskLevel = skor >= 20 ? 'critical' : skor >= 12 ? 'high' : skor >= 6 ? 'medium' : 'low';
      setPreviewScore({ skor, level: lv });
    }
  }, [watchKemungkinan, watchDampak]);

  const fetchData = useCallback(() => {
    setLoading(true);
    riskService.listAssessments({ q: search, level: level || undefined, status: status || undefined, page })
      .then((r) => { setItems(r.data); setMeta(r.meta); })
      .finally(() => setLoading(false));
  }, [search, level, status, page]);

  useEffect(() => { fetchData(); }, [fetchData]);

  const openAdd = () => { setEditTarget(null); reset({ status: 'draft', kemungkinan: 3, dampak: 3 }); setModalOpen(true); };
  const openEdit = (item: RiskAssessment) => {
    setEditTarget(item);
    reset({ aset_nama: item.aset_nama, ancaman_nama: item.ancaman_nama, kerentanan_nama: item.kerentanan_nama, kemungkinan: item.kemungkinan, dampak: item.dampak, tanggal_penilaian: item.tanggal_penilaian, penilai: item.penilai, status: item.status });
    setModalOpen(true);
  };

  const onSubmit = async (data: FormValues) => {
    const payload = {
      ...data,
      aset_id: editTarget?.aset_id ?? 0,
      ancaman_id: editTarget?.ancaman_id ?? 0,
      kerentanan_id: editTarget?.kerentanan_id ?? 0,
      kemungkinan: data.kemungkinan as RiskAssessment['kemungkinan'],
      dampak: data.dampak as RiskAssessment['dampak'],
      status: data.status as RiskAssessment['status'],
    };
    if (editTarget) await riskService.updateAssessment(editTarget.id, payload);
    else await riskService.createAssessment(payload);
    setModalOpen(false);
    fetchData();
  };

  const handleDelete = async () => {
    if (!deleteTarget) return;
    setDeleting(true);
    await riskService.deleteAssessment(deleteTarget.id);
    setDeleting(false);
    setDeleteTarget(null);
    fetchData();
  };

  return (
    <div className="space-y-5">
      <PageHeader title="Penilaian Risiko" description="Identifikasi dan penilaian risiko berdasarkan aset, ancaman, dan kerentanan"
        action={<Button leftIcon={<Plus className="h-4 w-4" />} onClick={openAdd}>Penilaian Baru</Button>}
      />

      <div className="grid grid-cols-2 gap-4 sm:grid-cols-4">
        {[
          { label: 'Kritis', variant: 'critical' as BadgeVariant, count: items.filter((i) => i.level_risiko === 'critical').length },
          { label: 'Tinggi', variant: 'high' as BadgeVariant, count: items.filter((i) => i.level_risiko === 'high').length },
          { label: 'Sedang', variant: 'medium' as BadgeVariant, count: items.filter((i) => i.level_risiko === 'medium').length },
          { label: 'Rendah', variant: 'low' as BadgeVariant, count: items.filter((i) => i.level_risiko === 'low').length },
        ].map((s) => (
          <Card key={s.label} padding="sm" className="text-center">
            <p className="text-2xl font-bold text-text-primary">{s.count}</p>
            <div className="mt-1 flex justify-center"><Badge variant={s.variant}>{s.label}</Badge></div>
          </Card>
        ))}
      </div>

      <Card padding="none">
        <div className="flex flex-wrap gap-3 border-b border-border px-5 py-4">
          <SearchInput value={search} onChange={(v) => { setSearch(v); setPage(1); }} placeholder="Cari aset atau ancaman..." className="w-64" />
          <Select options={[{ value: '', label: 'Semua Level' }, { value: 'critical', label: 'Kritis' }, { value: 'high', label: 'Tinggi' }, { value: 'medium', label: 'Sedang' }, { value: 'low', label: 'Rendah' }]} value={level} onChange={(e) => { setLevel(e.target.value); setPage(1); }} className="w-36 h-9 text-sm" />
          <Select options={[{ value: '', label: 'Semua Status' }, { value: 'draft', label: 'Draft' }, { value: 'disetujui', label: 'Disetujui' }, { value: 'ditolak', label: 'Ditolak' }]} value={status} onChange={(e) => { setStatus(e.target.value); setPage(1); }} className="w-36 h-9 text-sm" />
        </div>

        {loading ? <PageLoader /> : items.length === 0 ? (
          <EmptyState title="Tidak ada penilaian risiko" action={{ label: 'Penilaian Baru', onClick: openAdd }} />
        ) : (
          <div className="overflow-x-auto">
            <table className="w-full text-sm">
              <thead>
                <tr className="border-b border-border bg-surface-muted">
                  <th className="px-5 py-3 text-left text-xs font-semibold uppercase tracking-wider text-text-secondary">Aset</th>
                  <th className="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wider text-text-secondary">Ancaman / Kerentanan</th>
                  <th className="px-4 py-3 text-center text-xs font-semibold uppercase tracking-wider text-text-secondary">K × D = Skor</th>
                  <th className="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wider text-text-secondary">Level</th>
                  <th className="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wider text-text-secondary">Status</th>
                  <th className="px-4 py-3 text-right text-xs font-semibold uppercase tracking-wider text-text-secondary">Aksi</th>
                </tr>
              </thead>
              <tbody className="divide-y divide-border">
                {items.map((item) => (
                  <tr key={item.id} className="hover:bg-surface-muted/50">
                    <td className="px-5 py-3.5">
                      <p className="font-medium text-text-primary">{item.aset_nama}</p>
                      <p className="text-xs text-text-muted">{item.tanggal_penilaian} · {item.penilai}</p>
                    </td>
                    <td className="px-4 py-3.5">
                      <p className="text-text-primary">{item.ancaman_nama}</p>
                      <p className="text-xs text-text-muted">{item.kerentanan_nama}</p>
                    </td>
                    <td className="px-4 py-3.5 text-center font-mono text-text-secondary">
                      {item.kemungkinan} × {item.dampak} = <strong>{item.skor_risiko}</strong>
                    </td>
                    <td className="px-4 py-3.5"><Badge variant={levelVariant(item.level_risiko)}>{levelLabel(item.level_risiko)}</Badge></td>
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

      <Modal open={modalOpen} onClose={() => setModalOpen(false)} title={editTarget ? 'Edit Penilaian' : 'Penilaian Risiko Baru'} size="lg"
        footer={<><Button variant="secondary" onClick={() => setModalOpen(false)} disabled={isSubmitting}>Batal</Button><Button form="assessment-form" type="submit" loading={isSubmitting}>{editTarget ? 'Simpan' : 'Buat Penilaian'}</Button></>}
      >
        <form id="assessment-form" onSubmit={handleSubmit(onSubmit)} className="space-y-4">
          <div className="grid grid-cols-1 gap-4 sm:grid-cols-2">
            <Input label="Nama Aset" required error={errors.aset_nama?.message} {...register('aset_nama')} />
            <Input label="Nama Ancaman" required error={errors.ancaman_nama?.message} {...register('ancaman_nama')} />
            <div className="sm:col-span-2">
              <Input label="Nama Kerentanan" required error={errors.kerentanan_nama?.message} {...register('kerentanan_nama')} />
            </div>
            <Select label="Kemungkinan (1–5)" required options={SCALE_OPTIONS} error={errors.kemungkinan?.message} {...register('kemungkinan')} />
            <Select label="Dampak (1–5)" required options={SCALE_OPTIONS} error={errors.dampak?.message} {...register('dampak')} />
          </div>

          {previewScore && (
            <div className="flex items-center gap-3 rounded-md border border-border bg-surface-muted p-3">
              <div className="text-sm text-text-secondary">Skor Risiko: <strong className="text-text-primary">{previewScore.skor}</strong></div>
              <Badge variant={levelVariant(previewScore.level)}>{levelLabel(previewScore.level)}</Badge>
            </div>
          )}

          <div className="grid grid-cols-1 gap-4 sm:grid-cols-2">
            <Input label="Tanggal Penilaian" type="date" required error={errors.tanggal_penilaian?.message} {...register('tanggal_penilaian')} />
            <Input label="Penilai" required error={errors.penilai?.message} {...register('penilai')} />
            <Select label="Status" required options={[{ value: 'draft', label: 'Draft' }, { value: 'disetujui', label: 'Disetujui' }, { value: 'ditolak', label: 'Ditolak' }]} error={errors.status?.message} {...register('status')} />
          </div>
        </form>
      </Modal>

      <ConfirmDialog open={!!deleteTarget} title="Hapus Penilaian" message={`Yakin menghapus penilaian risiko untuk "${deleteTarget?.aset_nama}"?`} confirmLabel="Hapus" loading={deleting} onConfirm={handleDelete} onCancel={() => setDeleteTarget(null)} />
    </div>
  );
}
