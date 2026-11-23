import { useEffect, useState, useCallback } from 'react';
import { Plus, Pencil, Trash2 } from 'lucide-react';
import { threatService } from '@/services/threat.service';
import type { Threat, ThreatCategory } from '@/types/risk.types';
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
  kode: z.string().min(1, 'Kode wajib diisi.'),
  nama: z.string().min(1, 'Nama wajib diisi.'),
  kategori: z.string().min(1, 'Kategori wajib dipilih.'),
  sumber: z.string().min(1, 'Sumber wajib diisi.'),
  deskripsi: z.string().optional(),
  status: z.string(),
});
type FormValues = z.infer<typeof schema>;

const KATEGORI_OPTIONS = [
  { value: '', label: 'Semua Kategori' },
  ...(['Teknis', 'Manusia', 'Lingkungan', 'Organisasi'] as ThreatCategory[]).map((c) => ({ value: c, label: c })),
];

function kategoriVariant(k: ThreatCategory) {
  const map: Record<ThreatCategory, 'info' | 'high' | 'warning' | 'neutral'> = {
    Teknis: 'info', Manusia: 'high', Lingkungan: 'warning', Organisasi: 'neutral',
  };
  return map[k];
}

export function ThreatsPage() {
  const [threats, setThreats] = useState<Threat[]>([]);
  const [meta, setMeta] = useState<{ current_page: number; last_page: number; total: number; per_page: number; from: number | null; to: number | null }>({ current_page: 1, last_page: 1, total: 0, per_page: 10, from: 1, to: 1 });
  const [loading, setLoading] = useState(true);
  const [search, setSearch] = useState('');
  const [kategori, setKategori] = useState('');
  const [page, setPage] = useState(1);
  const [modalOpen, setModalOpen] = useState(false);
  const [editTarget, setEditTarget] = useState<Threat | null>(null);
  const [deleteTarget, setDeleteTarget] = useState<Threat | null>(null);
  const [deleting, setDeleting] = useState(false);

  const { register, handleSubmit, reset, formState: { errors, isSubmitting } } = useForm<FormValues>({
    resolver: zodResolver(schema),
    defaultValues: { status: 'aktif' },
  });

  const fetchData = useCallback(() => {
    setLoading(true);
    threatService.list({ q: search, kategori: kategori || undefined, page })
      .then((r) => { setThreats(r.data); setMeta(r.meta); })
      .finally(() => setLoading(false));
  }, [search, kategori, page]);

  useEffect(() => { fetchData(); }, [fetchData]);

  const openAdd = () => { setEditTarget(null); reset({ status: 'aktif' }); setModalOpen(true); };
  const openEdit = (t: Threat) => { setEditTarget(t); reset({ kode: t.kode, nama: t.nama, kategori: t.kategori, sumber: t.sumber, deskripsi: t.deskripsi ?? '', status: t.status }); setModalOpen(true); };

  const onSubmit = async (data: FormValues) => {
    const payload = { ...data, kategori: data.kategori as ThreatCategory, status: data.status as Threat['status'], deskripsi: data.deskripsi ?? null };
    if (editTarget) await threatService.update(editTarget.id, payload);
    else await threatService.create(payload);
    setModalOpen(false);
    fetchData();
  };

  const handleDelete = async () => {
    if (!deleteTarget) return;
    setDeleting(true);
    await threatService.delete(deleteTarget.id);
    setDeleting(false);
    setDeleteTarget(null);
    fetchData();
  };

  return (
    <div className="space-y-5">
      <PageHeader title="Manajemen Ancaman" description="Daftar ancaman yang diidentifikasi terhadap aset informasi"
        action={<Button leftIcon={<Plus className="h-4 w-4" />} onClick={openAdd}>Tambah Ancaman</Button>}
      />

      <Card padding="none">
        <div className="flex flex-wrap items-center gap-3 border-b border-border px-5 py-4">
          <SearchInput value={search} onChange={(v) => { setSearch(v); setPage(1); }} placeholder="Cari nama ancaman..." className="w-64" />
          <Select options={KATEGORI_OPTIONS} value={kategori} onChange={(e) => { setKategori(e.target.value); setPage(1); }} className="w-44 h-9 text-sm" />
        </div>

        {loading ? <PageLoader /> : threats.length === 0 ? (
          <EmptyState title="Tidak ada ancaman ditemukan" action={{ label: 'Tambah Ancaman', onClick: openAdd }} />
        ) : (
          <div className="overflow-x-auto">
            <table className="w-full text-sm">
              <thead>
                <tr className="border-b border-border bg-surface-muted">
                  <th className="px-5 py-3 text-left text-xs font-semibold uppercase tracking-wider text-text-secondary">Kode / Nama</th>
                  <th className="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wider text-text-secondary">Kategori</th>
                  <th className="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wider text-text-secondary">Sumber</th>
                  <th className="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wider text-text-secondary">Status</th>
                  <th className="px-4 py-3 text-right text-xs font-semibold uppercase tracking-wider text-text-secondary">Aksi</th>
                </tr>
              </thead>
              <tbody className="divide-y divide-border">
                {threats.map((t) => (
                  <tr key={t.id} className="hover:bg-surface-muted/50 transition-colors">
                    <td className="px-5 py-3.5">
                      <p className="font-medium text-text-primary">{t.nama}</p>
                      <p className="text-xs text-text-muted">{t.kode}</p>
                    </td>
                    <td className="px-4 py-3.5"><Badge variant={kategoriVariant(t.kategori)}>{t.kategori}</Badge></td>
                    <td className="px-4 py-3.5 text-text-secondary">{t.sumber}</td>
                    <td className="px-4 py-3.5">
                      <Badge variant={t.status === 'aktif' ? 'success' : 'neutral'}>{t.status === 'aktif' ? 'Aktif' : 'Nonaktif'}</Badge>
                    </td>
                    <td className="px-4 py-3.5 text-right">
                      <div className="flex items-center justify-end gap-1">
                        <button onClick={() => openEdit(t)} className="rounded p-1.5 text-text-muted hover:bg-surface-muted" title="Edit"><Pencil className="h-4 w-4" /></button>
                        <button onClick={() => setDeleteTarget(t)} className="rounded p-1.5 text-text-muted hover:bg-red-50 hover:text-status-error" title="Hapus"><Trash2 className="h-4 w-4" /></button>
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

      <Modal open={modalOpen} onClose={() => setModalOpen(false)} title={editTarget ? 'Edit Ancaman' : 'Tambah Ancaman'}
        footer={<><Button variant="secondary" onClick={() => setModalOpen(false)} disabled={isSubmitting}>Batal</Button><Button form="threat-form" type="submit" loading={isSubmitting}>{editTarget ? 'Simpan' : 'Tambah'}</Button></>}
      >
        <form id="threat-form" onSubmit={handleSubmit(onSubmit)} className="grid grid-cols-1 gap-4 sm:grid-cols-2">
          <Input label="Kode" required error={errors.kode?.message} {...register('kode')} placeholder="THR-001" />
          <Input label="Nama Ancaman" required error={errors.nama?.message} {...register('nama')} />
          <Select label="Kategori" required options={[{ value: '', label: 'Pilih...' }, ...['Teknis', 'Manusia', 'Lingkungan', 'Organisasi'].map((c) => ({ value: c, label: c }))]} error={errors.kategori?.message} {...register('kategori')} />
          <Input label="Sumber" required error={errors.sumber?.message} {...register('sumber')} placeholder="Internal / Eksternal" />
          <Select label="Status" options={[{ value: 'aktif', label: 'Aktif' }, { value: 'nonaktif', label: 'Nonaktif' }]} error={errors.status?.message} {...register('status')} />
          <div className="sm:col-span-2">
            <Input label="Deskripsi" error={errors.deskripsi?.message} {...register('deskripsi')} />
          </div>
        </form>
      </Modal>

      <ConfirmDialog open={!!deleteTarget} title="Hapus Ancaman" message={`Yakin menghapus ancaman "${deleteTarget?.nama}"?`} confirmLabel="Hapus" loading={deleting} onConfirm={handleDelete} onCancel={() => setDeleteTarget(null)} />
    </div>
  );
}
