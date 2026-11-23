/**
 * Risk Treatment Page (Penanganan Risiko)
 * 
 * ERD-Compliant implementation following SIMRIS Consolidated ERD:
 * - Displays RENCANA_PENANGANAN (Treatment Plans) for STAFF's Satker
 * - Shows relationships to RISIKO, PROGRES_PENANGANAN, RISIKO_KONTROL, PENANGANAN_SOP
 * - Maintains entity separation as per ERD structure
 * 
 * This is NOT a flattened risk treatment view - each entity is properly separated
 */

import { useEffect, useState, useCallback } from 'react';
import { Plus, Pencil, Trash2, Eye, FileText, Shield, ClipboardCheck, TrendingUp, AlertTriangle } from 'lucide-react';
import { rencanaPenangananService, progresService, kontrolService, sopService } from '@/services/penanganan.service';
import { risikoService } from '@/services/asesmen.service';
import type {
  RencanaPenanganan,
  ProgresPenanganan,
  RisikoKontrol,
  PenangananSOP,
  Risiko,
  StrategiPenanganan,
  StatusRencana
} from '@/types/risk.types';
import type { BadgeVariant } from '@/types/badge.types';
import { PageHeader } from '@/components/common/PageHeader';
import { SearchInput } from '@/components/common/SearchInput';
import { Button } from '@/components/ui/Button';
import { Badge } from '@/components/ui/Badge';
import { Card } from '@/components/ui/Card';
import { PageLoader } from '@/components/ui/LoadingSpinner';
import { EmptyState } from '@/components/ui/EmptyState';
import { ConfirmDialog } from '@/components/ui/ConfirmDialog';
import { Select } from '@/components/ui/Select';
import { Modal } from '@/components/ui/Modal';
import { Input } from '@/components/ui/Input';
import { useForm } from 'react-hook-form';
import { zodResolver } from '@hookform/resolvers/zod';
import { z } from 'zod';

// ============================================================================
// Type Definitions & Helpers
// ============================================================================

const schema = z.object({
  id_risiko: z.string().min(1, 'Risiko harus dipilih'),
  strategi: z.string().min(1, 'Strategi harus dipilih'),
  deskripsi: z.string().min(1, 'Deskripsi tidak boleh kosong'),
  tgl_mulai: z.string().min(1, 'Tanggal mulai harus diisi'),
  tgl_target: z.string().min(1, 'Tanggal target harus diisi'),
  pic: z.string().min(1, 'PIC harus diisi'),
  anggaran: z.coerce.number().min(0, 'Anggaran tidak boleh negatif'),
  status: z.string().min(1, 'Status harus dipilih'),
  catatan: z.string().optional(),
});
type FormValues = z.infer<typeof schema>;

interface TreatmentWithRelations extends RencanaPenanganan {
  risiko?: Risiko;
  progress_items?: ProgresPenanganan[];
  latest_progress?: ProgresPenanganan;
  controls?: RisikoKontrol[];
  sops?: PenangananSOP[];
  computed_progress: number; // Calculated from progress entries
}

function statusVariant(s: StatusRencana): BadgeVariant {
  return {
    belum_mulai: 'neutral',
    sedang_berjalan: 'info',
    selesai: 'success',
    tertunda: 'warning'
  }[s] as BadgeVariant;
}

function statusLabel(s: StatusRencana) {
  return {
    belum_mulai: 'Belum Mulai',
    sedang_berjalan: 'Sedang Berjalan',
    selesai: 'Selesai',
    tertunda: 'Tertunda'
  }[s];
}

function strategiLabel(s: StrategiPenanganan) {
  return {
    mitigasi: 'Mitigasi',
    hindari: 'Hindari',
    transfer: 'Transfer',
    terima: 'Terima'
  }[s];
}

function formatCurrency(value: number): string {
  return new Intl.NumberFormat('id-ID', {
    style: 'currency',
    currency: 'IDR',
    minimumFractionDigits: 0,
  }).format(value);
}

function formatDate(dateString: string): string {
  return new Date(dateString).toLocaleDateString('id-ID', {
    day: 'numeric',
    month: 'short',
    year: 'numeric'
  });
}

function calculateProgress(progressEntries: ProgresPenanganan[]): number {
  if (progressEntries.length === 0) return 0;
  const latest = progressEntries.sort((a, b) => 
    new Date(b.tgl_progres).getTime() - new Date(a.tgl_progres).getTime()
  )[0];
  return latest?.persen_selesai || 0;
}

function ProgressBar({ value, showLabel = true }: { value: number; showLabel?: boolean }) {
  const color = value >= 100 
    ? 'bg-green-500' 
    : value >= 60 
    ? 'bg-blue-500' 
    : value >= 30 
    ? 'bg-amber-400' 
    : 'bg-red-500';
  
  return (
    <div className="w-28">
      {showLabel && (
        <div className="mb-1 flex items-center justify-between gap-1">
          <span className="text-xs font-medium text-text-secondary">{value}%</span>
        </div>
      )}
      <div className="h-2 w-full rounded-full bg-border">
        <div 
          className={`h-2 rounded-full transition-all ${color}`} 
          style={{ width: `${Math.min(value, 100)}%` }} 
        />
      </div>
    </div>
  );
}

// ============================================================================
// Detail View Component
// ============================================================================

interface DetailViewProps {
  plan: TreatmentWithRelations;
  onClose: () => void;
}

function TreatmentDetailView({ plan, onClose }: DetailViewProps) {
  return (
    <Modal 
      open={true} 
      onClose={onClose} 
      title="Detail Rencana Penanganan" 
      size="xl"
      footer={
        <Button variant="secondary" onClick={onClose}>Tutup</Button>
      }
    >
      <div className="space-y-6">
        {/* Basic Information */}
        <div>
          <h3 className="mb-3 text-sm font-semibold text-text-primary">Informasi Dasar</h3>
          <div className="grid grid-cols-2 gap-4 rounded-lg border border-border bg-surface-muted/30 p-4">
            <div>
              <p className="text-xs text-text-muted">ID Rencana</p>
              <p className="mt-1 text-sm font-medium text-text-primary">{plan.id_rencana}</p>
            </div>
            <div>
              <p className="text-xs text-text-muted">Status</p>
              <div className="mt-1">
                <Badge variant={statusVariant(plan.status)}>{statusLabel(plan.status)}</Badge>
              </div>
            </div>
            <div>
              <p className="text-xs text-text-muted">Strategi</p>
              <div className="mt-1">
                <Badge variant="neutral">{strategiLabel(plan.strategi)}</Badge>
              </div>
            </div>
            <div>
              <p className="text-xs text-text-muted">PIC</p>
              <p className="mt-1 text-sm text-text-primary">{plan.pic}</p>
            </div>
            <div>
              <p className="text-xs text-text-muted">Tanggal Mulai</p>
              <p className="mt-1 text-sm text-text-primary">{formatDate(plan.tgl_mulai)}</p>
            </div>
            <div>
              <p className="text-xs text-text-muted">Tanggal Target</p>
              <p className="mt-1 text-sm text-text-primary">{formatDate(plan.tgl_target)}</p>
            </div>
            <div>
              <p className="text-xs text-text-muted">Anggaran</p>
              <p className="mt-1 text-sm font-medium text-text-primary">{formatCurrency(plan.anggaran)}</p>
            </div>
            <div>
              <p className="text-xs text-text-muted">Progres</p>
              <div className="mt-1">
                <ProgressBar value={plan.computed_progress} />
              </div>
            </div>
          </div>
        </div>

        {/* Risiko Information */}
        {plan.risiko && (
          <div>
            <h3 className="mb-3 text-sm font-semibold text-text-primary">Risiko Terkait</h3>
            <div className="rounded-lg border border-border bg-surface-muted/30 p-4">
              <div className="flex items-start justify-between gap-4">
                <div className="flex-1">
                  <p className="text-sm font-medium text-text-primary">{plan.risiko.nama_risiko}</p>
                  <p className="mt-1 text-xs text-text-muted">{plan.risiko.deskripsi}</p>
                  <div className="mt-2 flex items-center gap-2">
                    <Badge variant="neutral" size="sm">ID: {plan.risiko.id_risiko}</Badge>
                    <Badge variant="neutral" size="sm">{plan.risiko.kategori}</Badge>
                  </div>
                </div>
              </div>
            </div>
          </div>
        )}

        {/* Description */}
        <div>
          <h3 className="mb-3 text-sm font-semibold text-text-primary">Deskripsi Rencana</h3>
          <div className="rounded-lg border border-border bg-surface-muted/30 p-4">
            <p className="text-sm text-text-secondary">{plan.deskripsi}</p>
            {plan.catatan && (
              <div className="mt-3 border-t border-border pt-3">
                <p className="text-xs font-medium text-text-muted">Catatan:</p>
                <p className="mt-1 text-sm text-text-secondary">{plan.catatan}</p>
              </div>
            )}
          </div>
        </div>

        {/* Progress Timeline */}
        {plan.progress_items && plan.progress_items.length > 0 && (
          <div>
            <h3 className="mb-3 flex items-center gap-2 text-sm font-semibold text-text-primary">
              <TrendingUp className="h-4 w-4" />
              Riwayat Progres ({plan.progress_items.length})
            </h3>
            <div className="space-y-2">
              {plan.progress_items
                .sort((a, b) => new Date(b.tgl_progres).getTime() - new Date(a.tgl_progres).getTime())
                .map((prog) => (
                  <div key={prog.id_progres} className="rounded-lg border border-border bg-surface-muted/30 p-3">
                    <div className="flex items-start justify-between gap-3">
                      <div className="flex-1">
                        <div className="flex items-center gap-2">
                          <ProgressBar value={prog.persen_selesai} showLabel={false} />
                          <span className="text-sm font-medium text-text-primary">{prog.persen_selesai}%</span>
                        </div>
                        <p className="mt-2 text-sm text-text-secondary">{prog.deskripsi}</p>
                        {prog.status_kendala === 'terjadi' && (
                          <div className="mt-2 flex items-start gap-2 rounded bg-status-warning/10 p-2">
                            <AlertTriangle className="h-4 w-4 flex-shrink-0 text-status-warning" />
                            <div>
                              <p className="text-xs font-medium text-status-warning">Kendala</p>
                              <p className="mt-0.5 text-xs text-text-secondary">{prog.deskripsi_kendala}</p>
                            </div>
                          </div>
                        )}
                      </div>
                      <span className="text-xs text-text-muted">{formatDate(prog.tgl_progres)}</span>
                    </div>
                  </div>
                ))}
            </div>
          </div>
        )}

        {/* Security Controls */}
        {plan.controls && plan.controls.length > 0 && (
          <div>
            <h3 className="mb-3 flex items-center gap-2 text-sm font-semibold text-text-primary">
              <Shield className="h-4 w-4" />
              Kontrol Keamanan ({plan.controls.length})
            </h3>
            <div className="grid gap-2">
              {plan.controls.map((ctrl) => (
                <div key={ctrl.id_risiko_kontrol} className="rounded-lg border border-border bg-surface-muted/30 p-3">
                  <div className="flex items-center justify-between">
                    <div className="flex-1">
                      <p className="text-sm font-medium text-text-primary">Control: {ctrl.id_kontrol}</p>
                      <div className="mt-1 flex items-center gap-2">
                        <Badge 
                          variant={
                            ctrl.status_implementasi === 'implemented' ? 'success' :
                            ctrl.status_implementasi === 'in_progress' ? 'info' :
                            'neutral'
                          }
                          size="sm"
                        >
                          {ctrl.status_implementasi === 'implemented' ? 'Implemented' :
                           ctrl.status_implementasi === 'in_progress' ? 'In Progress' :
                           'Planned'}
                        </Badge>
                        {ctrl.efektivitas && (
                          <Badge 
                            variant={
                              ctrl.efektivitas === 'high' ? 'success' :
                              ctrl.efektivitas === 'medium' ? 'warning' :
                              'critical'
                            }
                            size="sm"
                          >
                            Efektivitas: {ctrl.efektivitas}
                          </Badge>
                        )}
                      </div>
                    </div>
                    <span className="text-xs text-text-muted">{ctrl.penanggung_jawab}</span>
                  </div>
                </div>
              ))}
            </div>
          </div>
        )}

        {/* SOPs */}
        {plan.sops && plan.sops.length > 0 && (
          <div>
            <h3 className="mb-3 flex items-center gap-2 text-sm font-semibold text-text-primary">
              <ClipboardCheck className="h-4 w-4" />
              SOP Terkait ({plan.sops.length})
            </h3>
            <div className="grid gap-2">
              {plan.sops.map((sopLink) => (
                <div key={sopLink.id_penanganan_sop} className="rounded-lg border border-border bg-surface-muted/30 p-3">
                  <div className="flex items-start justify-between">
                    <div className="flex-1">
                      <p className="text-sm font-medium text-text-primary">SOP: {sopLink.id_sop}</p>
                      <p className="mt-1 text-xs text-text-secondary">{sopLink.relevansi}</p>
                      <div className="mt-2">
                        <Badge 
                          variant={
                            sopLink.status_adopsi === 'diterapkan' ? 'success' :
                            sopLink.status_adopsi === 'dalam_review' ? 'info' :
                            'neutral'
                          }
                          size="sm"
                        >
                          {sopLink.status_adopsi === 'diterapkan' ? 'Diterapkan' :
                           sopLink.status_adopsi === 'dalam_review' ? 'Dalam Review' :
                           'Direncanakan'}
                        </Badge>
                      </div>
                    </div>
                  </div>
                </div>
              ))}
            </div>
          </div>
        )}
      </div>
    </Modal>
  );
}

// ============================================================================
// Main Component
// ============================================================================

export function RiskTreatmentPage() {
  const [plans, setPlans] = useState<TreatmentWithRelations[]>([]);
  const [loading, setLoading] = useState(true);
  const [search, setSearch] = useState('');
  const [statusFilter, setStatusFilter] = useState('');
  const [availableRisks, setAvailableRisks] = useState<Risiko[]>([]);
  const [modalOpen, setModalOpen] = useState(false);
  const [detailView, setDetailView] = useState<TreatmentWithRelations | null>(null);
  const [editTarget, setEditTarget] = useState<RencanaPenanganan | null>(null);
  const [deleteTarget, setDeleteTarget] = useState<RencanaPenanganan | null>(null);
  const [deleting, setDeleting] = useState(false);

  // Summary statistics
  const [summary, setSummary] = useState({
    total: 0,
    belum_mulai: 0,
    sedang_berjalan: 0,
    selesai: 0,
    terlambat: 0
  });

  const { register, handleSubmit, reset, formState: { errors, isSubmitting } } = useForm<FormValues>({
    resolver: zodResolver(schema),
    defaultValues: { 
      status: 'belum_mulai', 
      strategi: 'mitigasi', 
      anggaran: 0 
    },
  });

  const fetchData = useCallback(async () => {
    setLoading(true);
    try {
      // Fetch treatment plans
      const plansData = await rencanaPenangananService.getAll();
      
      // Fetch related data for each plan
      const enrichedPlans: TreatmentWithRelations[] = await Promise.all(
        plansData.map(async (plan) => {
          const [risiko, progressItems, controls, sops] = await Promise.all([
            risikoService.getById(plan.id_risiko),
            progresService.getByRencana(plan.id_rencana),
            kontrolService.getByRisiko(plan.id_risiko),
            sopService.getByRencana(plan.id_rencana)
          ]);

          return {
            ...plan,
            risiko: risiko || undefined,
            progress_items: progressItems,
            latest_progress: progressItems[0] || undefined,
            controls,
            sops,
            computed_progress: calculateProgress(progressItems)
          };
        })
      );

      setPlans(enrichedPlans);

      // Fetch summary statistics
      const summaryData = await rencanaPenangananService.getSummary();
      setSummary(summaryData);

      // Fetch available risks for form
      const risks = await risikoService.getAll();
      setAvailableRisks(risks);
    } finally {
      setLoading(false);
    }
  }, []);

  useEffect(() => { 
    fetchData(); 
  }, [fetchData]);

  // Filter logic
  const filteredPlans = plans.filter((plan) => {
    const matchesSearch = !search || 
      plan.deskripsi.toLowerCase().includes(search.toLowerCase()) ||
      plan.risiko?.nama_risiko.toLowerCase().includes(search.toLowerCase()) ||
      plan.pic.toLowerCase().includes(search.toLowerCase());
    
    const matchesStatus = !statusFilter || plan.status === statusFilter;
    
    return matchesSearch && matchesStatus;
  });

  const openAdd = () => { 
    setEditTarget(null); 
    reset({ 
      status: 'belum_mulai', 
      strategi: 'mitigasi', 
      anggaran: 0,
      id_risiko: availableRisks[0]?.id_risiko || '',
      deskripsi: '',
      tgl_mulai: '',
      tgl_target: '',
      pic: '',
      catatan: ''
    }); 
    setModalOpen(true); 
  };

  const openEdit = (plan: RencanaPenanganan) => {
    setEditTarget(plan);
    reset({
      id_risiko: plan.id_risiko,
      strategi: plan.strategi,
      deskripsi: plan.deskripsi,
      tgl_mulai: plan.tgl_mulai,
      tgl_target: plan.tgl_target,
      pic: plan.pic,
      anggaran: plan.anggaran,
      status: plan.status,
      catatan: plan.catatan || ''
    });
    setModalOpen(true);
  };

  const onSubmit = async (data: FormValues) => {
    try {
      if (editTarget) {
        await rencanaPenangananService.update(editTarget.id_rencana, {
          ...data,
          strategi: data.strategi as StrategiPenanganan,
          status: data.status as StatusRencana,
          catatan: data.catatan || null
        });
      } else {
        await rencanaPenangananService.create({
          ...data,
          strategi: data.strategi as StrategiPenanganan,
          status: data.status as StatusRencana,
          catatan: data.catatan || null
        });
      }
      setModalOpen(false);
      await fetchData();
    } catch (error) {
      console.error('Error saving treatment plan:', error);
    }
  };

  const handleDelete = async () => {
    if (!deleteTarget) return;
    setDeleting(true);
    try {
      await rencanaPenangananService.delete(deleteTarget.id_rencana);
      setDeleteTarget(null);
      await fetchData();
    } finally {
      setDeleting(false);
    }
  };

  const openDetail = (plan: TreatmentWithRelations) => {
    setDetailView(plan);
  };

  return (
    <div className="space-y-5">
      <PageHeader 
        title="Penanganan Risiko" 
        description="Rencana penanganan risiko berdasarkan hasil asesmen dan penilaian"
        action={
          <Button leftIcon={<Plus className="h-4 w-4" />} onClick={openAdd}>
            Rencana Baru
          </Button>
        }
      />

      {/* Summary Cards */}
      <div className="grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-5">
        <Card padding="md">
          <div className="flex items-center gap-3">
            <div className="rounded-lg bg-primary-50 p-2.5">
              <FileText className="h-5 w-5 text-primary-600" />
            </div>
            <div>
              <p className="text-xs text-text-muted">Total Rencana</p>
              <p className="text-2xl font-bold text-text-primary">{summary.total}</p>
            </div>
          </div>
        </Card>

        <Card padding="md">
          <div className="flex items-center gap-3">
            <div className="rounded-lg bg-gray-100 p-2.5">
              <AlertTriangle className="h-5 w-5 text-gray-600" />
            </div>
            <div>
              <p className="text-xs text-text-muted">Belum Mulai</p>
              <p className="text-2xl font-bold text-text-primary">{summary.belum_mulai}</p>
            </div>
          </div>
        </Card>

        <Card padding="md">
          <div className="flex items-center gap-3">
            <div className="rounded-lg bg-blue-50 p-2.5">
              <TrendingUp className="h-5 w-5 text-blue-600" />
            </div>
            <div>
              <p className="text-xs text-text-muted">Sedang Berjalan</p>
              <p className="text-2xl font-bold text-text-primary">{summary.sedang_berjalan}</p>
            </div>
          </div>
        </Card>

        <Card padding="md">
          <div className="flex items-center gap-3">
            <div className="rounded-lg bg-green-50 p-2.5">
              <Shield className="h-5 w-5 text-green-600" />
            </div>
            <div>
              <p className="text-xs text-text-muted">Selesai</p>
              <p className="text-2xl font-bold text-text-primary">{summary.selesai}</p>
            </div>
          </div>
        </Card>

        <Card padding="md">
          <div className="flex items-center gap-3">
            <div className="rounded-lg bg-red-50 p-2.5">
              <AlertTriangle className="h-5 w-5 text-red-600" />
            </div>
            <div>
              <p className="text-xs text-text-muted">Terlambat</p>
              <p className="text-2xl font-bold text-text-primary">{summary.terlambat}</p>
            </div>
          </div>
        </Card>
      </div>

      {/* Main Table */}
      <Card padding="none">
        <div className="flex flex-wrap gap-3 border-b border-border px-5 py-4">
          <SearchInput 
            value={search} 
            onChange={(v) => setSearch(v)} 
            placeholder="Cari rencana atau risiko..." 
            className="w-64" 
          />
          <Select 
            options={[
              { value: '', label: 'Semua Status' }, 
              { value: 'belum_mulai', label: 'Belum Mulai' }, 
              { value: 'sedang_berjalan', label: 'Sedang Berjalan' }, 
              { value: 'selesai', label: 'Selesai' }, 
              { value: 'tertunda', label: 'Tertunda' }
            ]} 
            value={statusFilter} 
            onChange={(e) => setStatusFilter(e.target.value)} 
            className="w-44 h-9 text-sm" 
          />
          <div className="ml-auto text-sm text-text-muted">
            Menampilkan {filteredPlans.length} dari {plans.length} rencana
          </div>
        </div>

        {loading ? (
          <PageLoader />
        ) : filteredPlans.length === 0 ? (
          <EmptyState 
            title={search || statusFilter ? "Tidak ada rencana yang sesuai" : "Belum ada rencana penanganan"} 
            description={search || statusFilter ? "Coba ubah filter pencarian" : "Mulai dengan membuat rencana penanganan risiko"}
            action={!search && !statusFilter ? { label: 'Tambah Rencana', onClick: openAdd } : undefined} 
          />
        ) : (
          <div className="overflow-x-auto">
            <table className="w-full text-sm">
              <thead>
                <tr className="border-b border-border bg-surface-muted">
                  <th className="px-5 py-3 text-left text-xs font-semibold uppercase tracking-wider text-text-secondary">
                    Risiko & Rencana
                  </th>
                  <th className="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wider text-text-secondary">
                    Strategi
                  </th>
                  <th className="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wider text-text-secondary">
                    PIC
                  </th>
                  <th className="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wider text-text-secondary">
                    Periode
                  </th>
                  <th className="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wider text-text-secondary">
                    Anggaran
                  </th>
                  <th className="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wider text-text-secondary">
                    Progres
                  </th>
                  <th className="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wider text-text-secondary">
                    Status
                  </th>
                  <th className="px-4 py-3 text-right text-xs font-semibold uppercase tracking-wider text-text-secondary">
                    Aksi
                  </th>
                </tr>
              </thead>
              <tbody className="divide-y divide-border">
                {filteredPlans.map((plan) => (
                  <tr key={plan.id_rencana} className="hover:bg-surface-muted/50">
                    <td className="px-5 py-3.5">
                      {plan.risiko && (
                        <p className="text-xs text-text-muted">{plan.risiko.nama_risiko}</p>
                      )}
                      <p className="mt-1 font-medium text-text-primary">{plan.deskripsi}</p>
                      <div className="mt-1 flex items-center gap-2">
                        {plan.progress_items && plan.progress_items.length > 0 && (
                          <span className="text-xs text-text-muted">
                            {plan.progress_items.length} update
                          </span>
                        )}
                        {plan.controls && plan.controls.length > 0 && (
                          <span className="text-xs text-text-muted">
                            • {plan.controls.length} kontrol
                          </span>
                        )}
                        {plan.sops && plan.sops.length > 0 && (
                          <span className="text-xs text-text-muted">
                            • {plan.sops.length} SOP
                          </span>
                        )}
                      </div>
                    </td>
                    <td className="px-4 py-3.5">
                      <Badge variant="neutral">{strategiLabel(plan.strategi)}</Badge>
                    </td>
                    <td className="px-4 py-3.5 text-text-secondary">
                      {plan.pic}
                    </td>
                    <td className="px-4 py-3.5 text-text-secondary">
                      <div className="text-xs">
                        <div>{formatDate(plan.tgl_mulai)}</div>
                        <div className="text-text-muted">s/d {formatDate(plan.tgl_target)}</div>
                      </div>
                    </td>
                    <td className="px-4 py-3.5 text-text-secondary">
                      <span className="text-xs">{formatCurrency(plan.anggaran)}</span>
                    </td>
                    <td className="px-4 py-3.5">
                      <ProgressBar value={plan.computed_progress} />
                    </td>
                    <td className="px-4 py-3.5">
                      <Badge variant={statusVariant(plan.status)}>
                        {statusLabel(plan.status)}
                      </Badge>
                    </td>
                    <td className="px-4 py-3.5 text-right">
                      <div className="flex justify-end gap-1">
                        <button 
                          onClick={() => openDetail(plan)} 
                          className="rounded p-1.5 text-text-muted hover:bg-surface-muted"
                          title="Lihat detail"
                        >
                          <Eye className="h-4 w-4" />
                        </button>
                        <button 
                          onClick={() => openEdit(plan)} 
                          className="rounded p-1.5 text-text-muted hover:bg-surface-muted"
                          title="Edit"
                        >
                          <Pencil className="h-4 w-4" />
                        </button>
                        <button 
                          onClick={() => setDeleteTarget(plan)} 
                          className="rounded p-1.5 text-text-muted hover:bg-red-50 hover:text-status-error"
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
      </Card>

      {/* Add/Edit Modal */}
      <Modal 
        open={modalOpen} 
        onClose={() => setModalOpen(false)} 
        title={editTarget ? 'Edit Rencana Penanganan' : 'Rencana Penanganan Baru'} 
        size="lg"
        footer={
          <>
            <Button variant="secondary" onClick={() => setModalOpen(false)} disabled={isSubmitting}>
              Batal
            </Button>
            <Button form="treatment-form" type="submit" loading={isSubmitting}>
              {editTarget ? 'Simpan Perubahan' : 'Buat Rencana'}
            </Button>
          </>
        }
      >
        <form id="treatment-form" onSubmit={handleSubmit(onSubmit)} className="grid grid-cols-1 gap-4 sm:grid-cols-2">
          <div className="sm:col-span-2">
            <Select 
              label="Risiko" 
              required 
              options={availableRisks.map(r => ({ 
                value: r.id_risiko, 
                label: `${r.nama_risiko} (${r.kategori})`
              }))}
              error={errors.id_risiko?.message} 
              {...register('id_risiko')} 
            />
          </div>

          <div className="sm:col-span-2">
            <Input 
              label="Deskripsi Rencana Penanganan" 
              required 
              error={errors.deskripsi?.message} 
              {...register('deskripsi')} 
              placeholder="Jelaskan rencana penanganan yang akan dilakukan"
            />
          </div>

          <Select 
            label="Strategi Penanganan" 
            required 
            options={[
              { value: 'mitigasi', label: 'Mitigasi - Mengurangi dampak/kemungkinan' }, 
              { value: 'hindari', label: 'Hindari - Menghindari risiko sepenuhnya' }, 
              { value: 'transfer', label: 'Transfer - Mengalihkan risiko ke pihak lain' }, 
              { value: 'terima', label: 'Terima - Menerima risiko tanpa tindakan' }
            ]} 
            error={errors.strategi?.message} 
            {...register('strategi')} 
          />

          <Input 
            label="PIC (Penanggung Jawab)" 
            required 
            error={errors.pic?.message} 
            {...register('pic')} 
            placeholder="Nama/ID PIC"
          />

          <Input 
            label="Tanggal Mulai" 
            type="date" 
            required 
            error={errors.tgl_mulai?.message} 
            {...register('tgl_mulai')} 
          />

          <Input 
            label="Tanggal Target Selesai" 
            type="date" 
            required 
            error={errors.tgl_target?.message} 
            {...register('tgl_target')} 
          />

          <Input 
            label="Anggaran (Rp)" 
            type="number" 
            required 
            error={errors.anggaran?.message} 
            {...register('anggaran')} 
            placeholder="0"
          />

          <Select 
            label="Status" 
            required 
            options={[
              { value: 'belum_mulai', label: 'Belum Mulai' }, 
              { value: 'sedang_berjalan', label: 'Sedang Berjalan' }, 
              { value: 'selesai', label: 'Selesai' }, 
              { value: 'tertunda', label: 'Tertunda' }
            ]} 
            error={errors.status?.message} 
            {...register('status')} 
          />

          <div className="sm:col-span-2">
            <Input 
              label="Catatan (Opsional)" 
              error={errors.catatan?.message} 
              {...register('catatan')} 
              placeholder="Catatan tambahan tentang rencana ini"
            />
          </div>
        </form>
      </Modal>

      {/* Detail View Modal */}
      {detailView && (
        <TreatmentDetailView 
          plan={detailView} 
          onClose={() => setDetailView(null)} 
        />
      )}

      {/* Delete Confirmation */}
      <ConfirmDialog 
        open={!!deleteTarget} 
        title="Hapus Rencana Penanganan" 
        message={`Yakin menghapus rencana penanganan "${deleteTarget?.deskripsi}"? Tindakan ini tidak dapat dibatalkan.`} 
        confirmLabel="Hapus" 
        loading={deleting} 
        onConfirm={handleDelete} 
        onCancel={() => setDeleteTarget(null)} 
      />
    </div>
  );
}
