import { useEffect } from 'react';
import { useForm } from 'react-hook-form';
import { zodResolver } from '@hookform/resolvers/zod';
import { z } from 'zod';
import { Modal } from '@/components/ui/Modal';
import { Input } from '@/components/ui/Input';
import { Select } from '@/components/ui/Select';
import { Button } from '@/components/ui/Button';
import { assetService } from '@/services/asset.service';
import type { Asset, AssetCategory, AssetCondition, AssetStatus, CIALevel } from '@/types/risk.types';

// ─── Validation Schema ───────────────────────────────────────────────────────

const schema = z.object({
  kode: z.string().min(1, 'Kode wajib diisi.'),
  nama: z.string().min(1, 'Nama wajib diisi.'),
  kategori: z.string().min(1, 'Kategori wajib dipilih.'),
  pemilik: z.string().min(1, 'Pemilik wajib diisi.'),
  lokasi: z.string().min(1, 'Lokasi wajib diisi.'),
  tanggal_perolehan: z.string().min(1, 'Tanggal perolehan wajib diisi.'),
  nilai_aset: z.coerce.number().min(0, 'Nilai aset minimal 0.'),
  kondisi: z.string().min(1, 'Kondisi wajib dipilih.'),
  status: z.string().min(1, 'Status wajib dipilih.'),
  pic: z.string().optional(),
  deskripsi: z.string().optional(),
  kerahasiaan: z.coerce.number().min(1).max(5),
  integritas: z.coerce.number().min(1).max(5),
  ketersediaan: z.coerce.number().min(1).max(5),
});

type FormValues = z.infer<typeof schema>;

// ─── Options ─────────────────────────────────────────────────────────────────

const CIA_OPTIONS = [1, 2, 3, 4, 5].map((v) => ({ 
  value: v, 
  label: `${v} — ${['', 'Sangat Rendah', 'Rendah', 'Sedang', 'Tinggi', 'Sangat Tinggi'][v]}` 
}));

const KATEGORI_OPTIONS = [
  { value: '', label: 'Pilih kategori...' },
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
  { value: 'Aktif', label: 'Aktif' },
  { value: 'Pemeliharaan', label: 'Pemeliharaan' },
  { value: 'Tidak Aktif', label: 'Tidak Aktif' },
];

const KONDISI_OPTIONS = [
  { value: 'Baik', label: 'Baik' },
  { value: 'Rusak Ringan', label: 'Rusak Ringan' },
  { value: 'Rusak Berat', label: 'Rusak Berat' },
];

// ─── Component ───────────────────────────────────────────────────────────────

interface AssetModalProps {
  open: boolean;
  onClose: () => void;
  initialData: Asset | null;
  onSaved: () => void;
}

export function AssetModal({ open, onClose, initialData, onSaved }: AssetModalProps) {
  const isEdit = initialData !== null;

  const { register, handleSubmit, reset, setError, formState: { errors, isSubmitting } } = useForm<FormValues>({
    resolver: zodResolver(schema),
    defaultValues: { 
      status: 'Aktif',
      kondisi: 'Baik',
      kerahasiaan: 3, 
      integritas: 3, 
      ketersediaan: 3, 
      nilai_aset: 0,
      tanggal_perolehan: new Date().toISOString().split('T')[0],
    },
  });

  useEffect(() => {
    if (open && initialData) {
      reset({
        kode: initialData.kode,
        nama: initialData.nama,
        kategori: initialData.kategori,
        pemilik: initialData.pemilik,
        lokasi: initialData.lokasi,
        tanggal_perolehan: initialData.tanggal_perolehan,
        nilai_aset: initialData.nilai_aset,
        kondisi: initialData.kondisi,
        status: initialData.status,
        pic: initialData.pic ?? '',
        deskripsi: initialData.deskripsi ?? '',
        kerahasiaan: initialData.kerahasiaan,
        integritas: initialData.integritas,
        ketersediaan: initialData.ketersediaan,
      });
    } else if (open) {
      reset({ 
        status: 'Aktif',
        kondisi: 'Baik',
        kerahasiaan: 3, 
        integritas: 3, 
        ketersediaan: 3, 
        nilai_aset: 0,
        tanggal_perolehan: new Date().toISOString().split('T')[0],
      });
    }
  }, [open, initialData, reset]);

  const onSubmit = async (data: FormValues) => {
    try {
      const payload = {
        ...data,
        kategori: data.kategori as AssetCategory,
        kondisi: data.kondisi as AssetCondition,
        status: data.status as AssetStatus,
        kerahasiaan: data.kerahasiaan as CIALevel,
        integritas: data.integritas as CIALevel,
        ketersediaan: data.ketersediaan as CIALevel,
        pic: data.pic || null,
        unit_id: null,
        deskripsi: data.deskripsi || null,
      };

      if (isEdit && initialData) {
        await assetService.update(initialData.id, payload);
      } else {
        await assetService.create(payload);
      }
      onSaved();
    } catch (error) {
      if (error instanceof Error && error.message.includes('sudah digunakan')) {
        setError('kode', { message: error.message });
      } else {
        throw error;
      }
    }
  };

  return (
    <Modal
      open={open}
      onClose={onClose}
      title={isEdit ? 'Edit Aset' : 'Tambah Aset Baru'}
      size="lg"
      footer={
        <>
          <Button variant="secondary" onClick={onClose} disabled={isSubmitting}>
            Batal
          </Button>
          <Button form="asset-form" type="submit" loading={isSubmitting}>
            {isEdit ? 'Simpan Perubahan' : 'Tambah Aset'}
          </Button>
        </>
      }
    >
      <form id="asset-form" onSubmit={handleSubmit(onSubmit)} className="grid grid-cols-1 gap-4 sm:grid-cols-2">
        {/* Row 1 */}
        <Input 
          label="Kode Aset" 
          required 
          error={errors.kode?.message} 
          {...register('kode')} 
          placeholder="AST-001" 
        />
        <Input 
          label="Nama Aset" 
          required 
          error={errors.nama?.message} 
          {...register('nama')} 
          placeholder="Laptop Dell Latitude" 
        />

        {/* Row 2 */}
        <Select 
          label="Kategori" 
          required 
          options={KATEGORI_OPTIONS}
          error={errors.kategori?.message} 
          {...register('kategori')} 
        />
        <Input 
          label="Lokasi" 
          required 
          error={errors.lokasi?.message} 
          {...register('lokasi')} 
          placeholder="Ruang Kerja IT" 
        />

        {/* Row 3 */}
        <Input 
          label="Tanggal Perolehan" 
          type="date"
          required 
          error={errors.tanggal_perolehan?.message} 
          {...register('tanggal_perolehan')} 
        />
        <Input 
          label="Nilai Perolehan (Rp)" 
          type="number" 
          required 
          error={errors.nilai_aset?.message} 
          {...register('nilai_aset')}
          placeholder="15000000" 
        />

        {/* Row 4 */}
        <Select 
          label="Kondisi Aset" 
          required 
          options={KONDISI_OPTIONS}
          error={errors.kondisi?.message} 
          {...register('kondisi')} 
        />
        <Select 
          label="Status Aset" 
          required 
          options={STATUS_OPTIONS}
          error={errors.status?.message} 
          {...register('status')} 
        />

        {/* Row 5 */}
        <Input 
          label="Pemilik / Unit" 
          required 
          error={errors.pemilik?.message} 
          {...register('pemilik')} 
          placeholder="Bidang TI" 
        />
        <Input 
          label="PIC / Penanggung Jawab" 
          error={errors.pic?.message} 
          {...register('pic')} 
          placeholder="Nama PIC (opsional)" 
        />

        {/* CIA Assessment */}
        <div className="sm:col-span-2">
          <p className="mb-2 text-sm font-medium text-text-primary">Penilaian CIA (Confidentiality, Integrity, Availability)</p>
        </div>
        
        <Select 
          label="Kerahasiaan (Confidentiality)" 
          options={CIA_OPTIONS} 
          error={errors.kerahasiaan?.message} 
          {...register('kerahasiaan')} 
        />
        <Select 
          label="Integritas (Integrity)" 
          options={CIA_OPTIONS} 
          error={errors.integritas?.message} 
          {...register('integritas')} 
        />
        <Select 
          label="Ketersediaan (Availability)" 
          options={CIA_OPTIONS} 
          error={errors.ketersediaan?.message} 
          {...register('ketersediaan')} 
        />

        {/* Description */}
        <div className="sm:col-span-2">
          <Input 
            label="Deskripsi" 
            error={errors.deskripsi?.message} 
            {...register('deskripsi')} 
            placeholder="Deskripsi singkat aset (opsional)" 
          />
        </div>
      </form>
    </Modal>
  );
}
