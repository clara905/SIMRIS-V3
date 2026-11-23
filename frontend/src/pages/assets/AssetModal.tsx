import { useEffect } from 'react';
import { useForm } from 'react-hook-form';
import { zodResolver } from '@hookform/resolvers/zod';
import { z } from 'zod';
import { Modal } from '@/components/ui/Modal';
import { Input } from '@/components/ui/Input';
import { Select } from '@/components/ui/Select';
import { Button } from '@/components/ui/Button';
import { assetService } from '@/services/asset.service';
import type { Asset, AssetCategory, CIALevel } from '@/types/risk.types';

const schema = z.object({
  kode: z.string().min(1, 'Kode wajib diisi.'),
  nama: z.string().min(1, 'Nama wajib diisi.'),
  kategori: z.string().min(1, 'Kategori wajib dipilih.'),
  pemilik: z.string().min(1, 'Pemilik wajib diisi.'),
  lokasi: z.string().min(1, 'Lokasi wajib diisi.'),
  deskripsi: z.string().optional(),
  nilai_aset: z.coerce.number().min(0, 'Nilai aset minimal 0.'),
  kerahasiaan: z.coerce.number().min(1).max(5),
  integritas: z.coerce.number().min(1).max(5),
  ketersediaan: z.coerce.number().min(1).max(5),
  status: z.string(),
});

type FormValues = z.infer<typeof schema>;

const CIA_OPTIONS = [1, 2, 3, 4, 5].map((v) => ({ value: v, label: `${v} — ${['', 'Sangat Rendah', 'Rendah', 'Sedang', 'Tinggi', 'Sangat Tinggi'][v]}` }));

interface AssetModalProps {
  open: boolean;
  onClose: () => void;
  initialData: Asset | null;
  onSaved: () => void;
}

export function AssetModal({ open, onClose, initialData, onSaved }: AssetModalProps) {
  const isEdit = initialData !== null;

  const { register, handleSubmit, reset, formState: { errors, isSubmitting } } = useForm<FormValues>({
    resolver: zodResolver(schema),
    defaultValues: { status: 'aktif', kerahasiaan: 3, integritas: 3, ketersediaan: 3, nilai_aset: 0 },
  });

  useEffect(() => {
    if (open && initialData) {
      reset({
        kode: initialData.kode,
        nama: initialData.nama,
        kategori: initialData.kategori,
        pemilik: initialData.pemilik,
        lokasi: initialData.lokasi,
        deskripsi: initialData.deskripsi ?? '',
        nilai_aset: initialData.nilai_aset,
        kerahasiaan: initialData.kerahasiaan,
        integritas: initialData.integritas,
        ketersediaan: initialData.ketersediaan,
        status: initialData.status,
      });
    } else if (open) {
      reset({ status: 'aktif', kerahasiaan: 3, integritas: 3, ketersediaan: 3, nilai_aset: 0 });
    }
  }, [open, initialData, reset]);

  const onSubmit = async (data: FormValues) => {
    const payload = {
      ...data,
      kategori: data.kategori as AssetCategory,
      kerahasiaan: data.kerahasiaan as CIALevel,
      integritas: data.integritas as CIALevel,
      ketersediaan: data.ketersediaan as CIALevel,
      status: data.status as Asset['status'],
      unit_id: null,
      deskripsi: data.deskripsi ?? null,
    };

    if (isEdit && initialData) {
      await assetService.update(initialData.id, payload);
    } else {
      await assetService.create(payload);
    }
    onSaved();
  };

  return (
    <Modal
      open={open}
      onClose={onClose}
      title={isEdit ? 'Edit Aset' : 'Tambah Aset Baru'}
      size="lg"
      footer={
        <>
          <Button variant="secondary" onClick={onClose} disabled={isSubmitting}>Batal</Button>
          <Button form="asset-form" type="submit" loading={isSubmitting}>
            {isEdit ? 'Simpan Perubahan' : 'Tambah Aset'}
          </Button>
        </>
      }
    >
      <form id="asset-form" onSubmit={handleSubmit(onSubmit)} className="grid grid-cols-1 gap-4 sm:grid-cols-2">
        <Input label="Kode Aset" required error={errors.kode?.message} {...register('kode')} placeholder="AST-001" />
        <Input label="Nama Aset" required error={errors.nama?.message} {...register('nama')} placeholder="Server Aplikasi" />
        <Select label="Kategori" required options={[{ value: '', label: 'Pilih kategori...' }, ...(['Hardware', 'Software', 'Data', 'Jaringan', 'SDM', 'Layanan', 'Fasilitas', 'Lainnya'].map((c) => ({ value: c, label: c })))]} error={errors.kategori?.message} {...register('kategori')} />
        <Input label="Pemilik" required error={errors.pemilik?.message} {...register('pemilik')} placeholder="Bidang TI" />
        <Input label="Lokasi" required error={errors.lokasi?.message} {...register('lokasi')} placeholder="Data Center Lt.2" />
        <Input label="Nilai Aset (Rp)" type="number" required error={errors.nilai_aset?.message} {...register('nilai_aset')} />
        <Select label="Kerahasiaan" options={CIA_OPTIONS} error={errors.kerahasiaan?.message} {...register('kerahasiaan')} />
        <Select label="Integritas" options={CIA_OPTIONS} error={errors.integritas?.message} {...register('integritas')} />
        <Select label="Ketersediaan" options={CIA_OPTIONS} error={errors.ketersediaan?.message} {...register('ketersediaan')} />
        <Select label="Status" options={[{ value: 'aktif', label: 'Aktif' }, { value: 'nonaktif', label: 'Nonaktif' }, { value: 'review', label: 'Review' }]} error={errors.status?.message} {...register('status')} />
        <div className="sm:col-span-2">
          <Input label="Deskripsi" error={errors.deskripsi?.message} {...register('deskripsi')} placeholder="Deskripsi singkat aset (opsional)" />
        </div>
      </form>
    </Modal>
  );
}
