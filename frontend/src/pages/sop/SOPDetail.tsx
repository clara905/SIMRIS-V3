import { useEffect, useState } from 'react';
import { useParams, useNavigate, Link } from 'react-router-dom';
import {
  BookOpen,
  ChevronLeft,
  Calendar,
  User,
  FileText,
  CheckCircle,
  XCircle,
  AlertCircle,
  Shield,
  Clock,
  ShieldAlert,
  ShieldCheck,
} from 'lucide-react';
import { documentService } from '@/services/document.service';
import type { SOP, SOPVersi, SOPReview, SOPKontrol, RisikoSOP, PenangananSOP } from '@/types/document.types';
import { getSOPStatusBadge } from '@/types/document.types';
import { PageHeader, Breadcrumb } from '@/components/common/PageHeader';
import { Button } from '@/components/ui/Button';
import { Badge } from '@/components/ui/Badge';
import { Card, CardHeader } from '@/components/ui/Card';
import { PageLoader } from '@/components/ui/LoadingSpinner';
import { clsx } from '@/utils/clsx';

// ─── Helper Functions ────────────────────────────────────────────────────────

function formatDate(dateString: string): string {
  const date = new Date(dateString);
  return date.toLocaleDateString('id-ID', {
    weekday: 'long',
    year: 'numeric',
    month: 'long',
    day: 'numeric',
  });
}

function formatDateTime(dateString: string): string {
  const date = new Date(dateString);
  return date.toLocaleDateString('id-ID', {
    year: 'numeric',
    month: 'long',
    day: 'numeric',
    hour: '2-digit',
    minute: '2-digit',
  });
}

function getVersionStatusBadge(status: SOPVersi['status']) {
  if (status === 'aktif') return <Badge variant="success" dot>Aktif</Badge>;
  if (status === 'draft') return <Badge variant="neutral">Draft</Badge>;
  if (status === 'obsolete') return <Badge variant="muted">Obsolete</Badge>;
  return <Badge variant="neutral">{status}</Badge>;
}

function getReviewResultBadge(result: SOPReview['hasil_review']) {
  if (result === 'approved') return <Badge variant="success" dot>Disetujui</Badge>;
  if (result === 'revision_required') return <Badge variant="warning" dot>Perlu Revisi</Badge>;
  if (result === 'rejected') return <Badge variant="error" dot>Ditolak</Badge>;
  return <Badge variant="neutral">-</Badge>;
}

function getKontrolTipeBadge(tipe: string | undefined) {
  if (!tipe) return <Badge variant="neutral">-</Badge>;
  if (tipe === 'preventif') return <Badge variant="success">Preventif</Badge>;
  if (tipe === 'detektif') return <Badge variant="info">Detektif</Badge>;
  if (tipe === 'korektif') return <Badge variant="warning">Korektif</Badge>;
  return <Badge variant="neutral">{tipe}</Badge>;
}

// ─── Component ───────────────────────────────────────────────────────────────

type TabType = 'overview' | 'versions' | 'reviews' | 'controls' | 'risks' | 'treatments';

export function SOPDetail() {
  const { id } = useParams<{ id: string }>();
  const navigate = useNavigate();
  const [sop, setSOP] = useState<SOP | null>(null);
  const [versions, setVersions] = useState<SOPVersi[]>([]);
  const [reviews, setReviews] = useState<SOPReview[]>([]);
  const [controls, setControls] = useState<SOPKontrol[]>([]);
  const [risks, setRisks] = useState<RisikoSOP[]>([]);
  const [treatments, setTreatments] = useState<PenangananSOP[]>([]);
  const [loading, setLoading] = useState(true);
  const [activeTab, setActiveTab] = useState<TabType>('overview');

  useEffect(() => {
    if (!id) return;
    const sopId = parseInt(id);
    
    Promise.all([
      documentService.getSOP(sopId),
      documentService.getSOPVersions(sopId),
      documentService.getSOPReviews(sopId),
      documentService.getSOPControls(sopId),
      documentService.getSOPRisks(sopId), // Phase 7.1
      documentService.getSOPTreatments(sopId), // Phase 7.1
    ])
      .then(([sopData, versionsData, reviewsData, controlsData, risksData, treatmentsData]) => {
        setSOP(sopData);
        setVersions(versionsData);
        setReviews(reviewsData);
        setControls(controlsData);
        setRisks(risksData);
        setTreatments(treatmentsData);
      })
      .finally(() => setLoading(false));
  }, [id]);

  if (loading) return <PageLoader />;

  if (!sop) {
    return (
      <div className="space-y-5">
        <PageHeader
          title="SOP Tidak Ditemukan"
          description="SOP yang Anda cari tidak tersedia"
        />
        <Card padding="lg">
          <div className="text-center">
            <AlertCircle className="mx-auto h-12 w-12 text-red-600" />
            <p className="mt-4 text-text-secondary">SOP dengan ID {id} tidak ditemukan.</p>
            <Button className="mt-4" onClick={() => navigate('/sop')}>
              Kembali ke Daftar SOP
            </Button>
          </div>
        </Card>
      </div>
    );
  }

  const statusBadge = getSOPStatusBadge(sop.status);

  return (
    <div className="space-y-5">
      <PageHeader
        title={sop.judul}
        description={sop.kode}
        breadcrumb={
          <Breadcrumb
            items={[
              { label: 'SOP', to: '/sop' },
              { label: sop.kode },
            ]}
          />
        }
        action={
          <div className="flex gap-2">
            <Button
              variant="outline"
              leftIcon={<ChevronLeft className="h-4 w-4" />}
              onClick={() => navigate('/sop')}
            >
              Kembali
            </Button>
          </div>
        }
      />

      {/* Status Banner */}
      <Card padding="md" accent="brand">
        <div className="flex items-center justify-between">
          <div className="flex items-center gap-3">
            <div className="flex h-10 w-10 shrink-0 items-center justify-center rounded-lg bg-brand-muted">
              <BookOpen className="h-5 w-5 text-brand" />
            </div>
            <div>
              <p className="text-sm font-semibold text-text-primary">
                {statusBadge.label} • Versi {sop.versi_aktif || 'N/A'}
              </p>
              <p className="text-xs text-text-secondary">
                {sop.unit_pemilik} • Review terakhir: {sop.tanggal_terakhir_review ? formatDate(sop.tanggal_terakhir_review) : 'Belum ada'}
              </p>
            </div>
          </div>
          <Badge variant={statusBadge.variant as any} dot>
            {statusBadge.label}
          </Badge>
        </div>
      </Card>

      {/* Tabs */}
      <div className="border-b border-border">
        <div className="flex gap-1">
          {[
            { key: 'overview', label: 'Overview' },
            { key: 'versions', label: 'Riwayat Versi', count: versions.length },
            { key: 'reviews', label: 'Riwayat Review', count: reviews.length },
            { key: 'controls', label: 'Kontrol Keamanan', count: controls.length },
            { key: 'risks', label: 'Risiko Terkait', count: risks.length },
            { key: 'treatments', label: 'Penanganan Terkait', count: treatments.length },
          ].map((tab) => (
            <button
              key={tab.key}
              onClick={() => setActiveTab(tab.key as TabType)}
              className={clsx(
                'px-4 py-2.5 text-sm font-medium transition-colors border-b-2',
                activeTab === tab.key
                  ? 'border-brand text-brand'
                  : 'border-transparent text-text-secondary hover:text-text-primary hover:border-border'
              )}
            >
              {tab.label}
              {tab.count !== undefined && (
                <span className="ml-2 rounded-full bg-surface-muted px-2 py-0.5 text-xs">
                  {tab.count}
                </span>
              )}
            </button>
          ))}
        </div>
      </div>

      {/* Tab Content */}
      <div>
        {activeTab === 'overview' && (
          <div className="grid grid-cols-1 gap-6 lg:grid-cols-3">
            <div className="lg:col-span-2 space-y-6">
              <Card padding="none">
                <div className="border-b border-border px-5 py-4">
                  <CardHeader title="Informasi Umum" />
                </div>
                <div className="divide-y divide-border">
                  <div className="flex items-start gap-3 px-5 py-3">
                    <BookOpen className="h-5 w-5 shrink-0 text-text-muted mt-0.5" />
                    <div className="min-w-0 flex-1">
                      <p className="text-xs text-text-muted">Kode SOP</p>
                      <p className="mt-0.5 text-sm font-medium text-text-primary">{sop.kode}</p>
                    </div>
                  </div>

                  <div className="flex items-start gap-3 px-5 py-3">
                    <FileText className="h-5 w-5 shrink-0 text-text-muted mt-0.5" />
                    <div className="min-w-0 flex-1">
                      <p className="text-xs text-text-muted">Judul</p>
                      <p className="mt-0.5 text-sm font-medium text-text-primary">{sop.judul}</p>
                    </div>
                  </div>

                  {sop.deskripsi && (
                    <div className="flex items-start gap-3 px-5 py-3">
                      <FileText className="h-5 w-5 shrink-0 text-text-muted mt-0.5" />
                      <div className="min-w-0 flex-1">
                        <p className="text-xs text-text-muted">Deskripsi</p>
                        <p className="mt-0.5 text-sm text-text-primary">{sop.deskripsi}</p>
                      </div>
                    </div>
                  )}

                  <div className="flex items-start gap-3 px-5 py-3">
                    <User className="h-5 w-5 shrink-0 text-text-muted mt-0.5" />
                    <div className="min-w-0 flex-1">
                      <p className="text-xs text-text-muted">Unit Pemilik</p>
                      <p className="mt-0.5 text-sm font-medium text-text-primary">{sop.unit_pemilik}</p>
                    </div>
                  </div>

                  <div className="flex items-start gap-3 px-5 py-3">
                    <User className="h-5 w-5 shrink-0 text-text-muted mt-0.5" />
                    <div className="min-w-0 flex-1">
                      <p className="text-xs text-text-muted">Dibuat Oleh</p>
                      <p className="mt-0.5 text-sm font-medium text-text-primary">{sop.dibuat_oleh_nama}</p>
                      <p className="mt-0.5 text-xs text-text-muted">{formatDateTime(sop.dibuat_pada)}</p>
                    </div>
                  </div>

                  <div className="flex items-start gap-3 px-5 py-3">
                    <Calendar className="h-5 w-5 shrink-0 text-text-muted mt-0.5" />
                    <div className="min-w-0 flex-1">
                      <p className="text-xs text-text-muted">Diperbarui Terakhir</p>
                      <p className="mt-0.5 text-sm font-medium text-text-primary">{formatDateTime(sop.diperbarui_pada)}</p>
                    </div>
                  </div>
                </div>
              </Card>

              <Card padding="md">
                <CardHeader title="Konten SOP" className="mb-4" />
                <div className="rounded-lg border border-border bg-surface-muted/50 px-4 py-6 text-center">
                  <FileText className="mx-auto h-8 w-8 text-text-muted" />
                  <p className="mt-2 text-xs text-text-muted">
                    Konten SOP tidak tersedia dalam mode mock
                  </p>
                  <p className="mt-1 text-xs text-text-muted">
                    Versi aktif: {sop.versi_aktif || 'N/A'}
                  </p>
                </div>
              </Card>
            </div>

            <div className="space-y-6">
              <Card padding="md">
                <CardHeader title="Statistik" className="mb-4" />
                <div className="space-y-3">
                  <div>
                    <p className="text-xs text-text-muted">Total Versi</p>
                    <p className="mt-0.5 text-2xl font-semibold text-text-primary">{versions.length}</p>
                  </div>
                  <div>
                    <p className="text-xs text-text-muted">Total Review</p>
                    <p className="mt-0.5 text-2xl font-semibold text-text-primary">{reviews.length}</p>
                  </div>
                  <div>
                    <p className="text-xs text-text-muted">Kontrol Terkait</p>
                    <p className="mt-0.5 text-2xl font-semibold text-text-primary">{controls.length}</p>
                  </div>
                </div>
              </Card>

              <Card padding="md">
                <CardHeader title="Tautan Terkait" className="mb-3" />
                <div className="space-y-2">
                  <Link to="/dokumentasi/library" className="block">
                    <Button variant="outline" size="sm" className="w-full justify-start" leftIcon={<FileText className="h-4 w-4" />}>
                      Dokumen Terkait
                    </Button>
                  </Link>
                  <Link to="/asesmen-risiko" className="block">
                    <Button variant="outline" size="sm" className="w-full justify-start" leftIcon={<Shield className="h-4 w-4" />}>
                      Risiko Terkait
                    </Button>
                  </Link>
                </div>
              </Card>
            </div>
          </div>
        )}

        {activeTab === 'versions' && (
          <Card padding="none">
            <div className="border-b border-border px-5 py-4">
              <CardHeader 
                title="Riwayat Versi" 
                description="Daftar versi SOP yang pernah diterbitkan"
              />
            </div>
            {versions.length === 0 ? (
              <div className="px-5 py-12 text-center">
                <FileText className="mx-auto h-10 w-10 text-text-muted" />
                <p className="mt-2 text-sm font-medium text-text-primary">Belum ada riwayat versi</p>
                <p className="mt-1 text-xs text-text-muted">Versi SOP akan muncul di sini</p>
              </div>
            ) : (
              <div className="divide-y divide-border">
                {versions.map((version) => (
                  <div key={version.id} className="px-5 py-4">
                    <div className="flex items-start justify-between gap-4">
                      <div className="min-w-0 flex-1">
                        <div className="flex items-center gap-2">
                          <p className="text-sm font-semibold text-text-primary">
                            Versi {version.nomor_versi}
                          </p>
                          {getVersionStatusBadge(version.status)}
                        </div>
                        <p className="mt-1 text-xs text-text-secondary flex items-center gap-1">
                          <Calendar className="h-3 w-3" />
                          {formatDate(version.tanggal_versi)}
                        </p>
                        {version.catatan_versi && (
                          <p className="mt-2 text-xs text-text-secondary">{version.catatan_versi}</p>
                        )}
                        {version.dokumen_nama && (
                          <p className="mt-2 text-xs text-text-muted flex items-center gap-1">
                            <FileText className="h-3 w-3" />
                            Dokumen: {version.dokumen_nama}
                          </p>
                        )}
                        <p className="mt-1 text-xs text-text-muted">
                          Dibuat oleh {version.dibuat_oleh_nama} • {formatDateTime(version.dibuat_pada)}
                        </p>
                      </div>
                    </div>
                  </div>
                ))}
              </div>
            )}
          </Card>
        )}

        {activeTab === 'reviews' && (
          <Card padding="none">
            <div className="border-b border-border px-5 py-4">
              <CardHeader 
                title="Riwayat Review" 
                description="Hasil review dan evaluasi SOP"
              />
            </div>
            {reviews.length === 0 ? (
              <div className="px-5 py-12 text-center">
                <CheckCircle className="mx-auto h-10 w-10 text-text-muted" />
                <p className="mt-2 text-sm font-medium text-text-primary">Belum ada riwayat review</p>
                <p className="mt-1 text-xs text-text-muted">Review SOP akan muncul di sini</p>
              </div>
            ) : (
              <div className="divide-y divide-border">
                {reviews.map((review) => (
                  <div key={review.id} className="px-5 py-4">
                    <div className="flex items-start justify-between gap-4">
                      <div className="min-w-0 flex-1">
                        <div className="flex items-center gap-2">
                          <p className="text-sm font-semibold text-text-primary">
                            Review {formatDate(review.tanggal_review)}
                          </p>
                          {getReviewResultBadge(review.hasil_review)}
                        </div>
                        {review.versi_nomor && (
                          <p className="mt-1 text-xs text-text-secondary">
                            Versi: {review.versi_nomor}
                          </p>
                        )}
                        {review.catatan_review && (
                          <p className="mt-2 text-xs text-text-secondary">{review.catatan_review}</p>
                        )}
                        <p className="mt-2 text-xs text-text-muted">
                          Direview oleh {review.direview_oleh_nama} • {formatDateTime(review.direview_pada)}
                        </p>
                      </div>
                    </div>
                  </div>
                ))}
              </div>
            )}
          </Card>
        )}

        {activeTab === 'controls' && (
          <Card padding="none">
            <div className="border-b border-border px-5 py-4">
              <CardHeader 
                title="Kontrol Keamanan" 
                description="Kontrol keamanan yang diimplementasikan melalui SOP ini"
              />
            </div>
            {controls.length === 0 ? (
              <div className="px-5 py-12 text-center">
                <Shield className="mx-auto h-10 w-10 text-text-muted" />
                <p className="mt-2 text-sm font-medium text-text-primary">Belum ada kontrol terkait</p>
                <p className="mt-1 text-xs text-text-muted">Kontrol keamanan akan muncul di sini</p>
              </div>
            ) : (
              <div className="divide-y divide-border">
                {controls.map((control) => (
                  <div key={control.id} className="px-5 py-4">
                    <div className="flex items-start gap-3">
                      <div className="flex h-8 w-8 shrink-0 items-center justify-center rounded-lg bg-brand-muted mt-0.5">
                        <Shield className="h-4 w-4 text-brand" />
                      </div>
                      <div className="min-w-0 flex-1">
                        <div className="flex items-center gap-2 flex-wrap">
                          <p className="text-sm font-semibold text-text-primary">
                            {control.kontrol_nama}
                          </p>
                          <Badge variant="neutral" size="sm">{control.kontrol_kode}</Badge>
                          {getKontrolTipeBadge(control.kontrol_tipe)}
                        </div>
                        {control.kontrol_kategori && (
                          <p className="mt-1 text-xs text-text-secondary">
                            Kategori: {control.kontrol_kategori}
                          </p>
                        )}
                        {control.relevansi && (
                          <p className="mt-2 text-xs text-text-secondary">{control.relevansi}</p>
                        )}
                      </div>
                    </div>
                  </div>
                ))}
              </div>
            )}
          </Card>
        )}

        {/* Phase 7.1: Risiko Terkait Tab */}
        {activeTab === 'risks' && (
          <Card padding="none">
            <div className="border-b border-border px-5 py-4">
              <CardHeader 
                title="Risiko Terkait" 
                description="Risiko yang terkait dengan SOP ini"
              />
            </div>
            {risks.length === 0 ? (
              <div className="px-5 py-12 text-center">
                <ShieldAlert className="mx-auto h-10 w-10 text-text-muted" />
                <p className="mt-2 text-sm font-medium text-text-primary">Belum ada risiko terkait</p>
                <p className="mt-1 text-xs text-text-muted">Risiko yang mengacu pada SOP ini akan muncul di sini</p>
              </div>
            ) : (
              <div className="divide-y divide-border">
                {risks.map((risk) => (
                  <div key={risk.id} className="px-5 py-4">
                    <div className="flex items-start gap-3">
                      <div className="flex h-8 w-8 shrink-0 items-center justify-center rounded-lg bg-red-100 dark:bg-red-900/20 mt-0.5">
                        <ShieldAlert className="h-4 w-4 text-red-600 dark:text-red-400" />
                      </div>
                      <div className="min-w-0 flex-1">
                        <div className="flex items-center gap-2 flex-wrap">
                          <p className="text-sm font-semibold text-text-primary">
                            {risk.risiko_kode}
                          </p>
                          <Badge variant="error" size="sm">Risiko</Badge>
                        </div>
                        {risk.risiko_skenario && (
                          <p className="mt-1 text-xs text-text-secondary">
                            {risk.risiko_skenario}
                          </p>
                        )}
                        {risk.relevansi && (
                          <p className="mt-2 text-xs text-text-secondary">
                            <span className="font-medium">Relevansi:</span> {risk.relevansi}
                          </p>
                        )}
                        <div className="mt-2 flex items-center gap-2 text-xs text-text-muted">
                          <Calendar className="h-3 w-3" />
                          <span>Ditambahkan: {formatDate(risk.ditambahkan_pada)}</span>
                        </div>
                        <div className="mt-2">
                          <Link 
                            to="/asesmen-risiko" 
                            className="text-xs text-brand hover:text-brand-dark hover:underline"
                          >
                            Lihat Asesmen Risiko →
                          </Link>
                        </div>
                      </div>
                    </div>
                  </div>
                ))}
              </div>
            )}
          </Card>
        )}

        {/* Phase 7.1: Penanganan Terkait Tab */}
        {activeTab === 'treatments' && (
          <Card padding="none">
            <div className="border-b border-border px-5 py-4">
              <CardHeader 
                title="Penanganan Terkait" 
                description="Rencana penanganan yang menggunakan SOP ini"
              />
            </div>
            {treatments.length === 0 ? (
              <div className="px-5 py-12 text-center">
                <ShieldCheck className="mx-auto h-10 w-10 text-text-muted" />
                <p className="mt-2 text-sm font-medium text-text-primary">Belum ada penanganan terkait</p>
                <p className="mt-1 text-xs text-text-muted">Rencana penanganan yang mengacu pada SOP ini akan muncul di sini</p>
              </div>
            ) : (
              <div className="divide-y divide-border">
                {treatments.map((treatment) => (
                  <div key={treatment.id} className="px-5 py-4">
                    <div className="flex items-start gap-3">
                      <div className="flex h-8 w-8 shrink-0 items-center justify-center rounded-lg bg-green-100 dark:bg-green-900/20 mt-0.5">
                        <ShieldCheck className="h-4 w-4 text-green-600 dark:text-green-400" />
                      </div>
                      <div className="min-w-0 flex-1">
                        <div className="flex items-center gap-2 flex-wrap">
                          <p className="text-sm font-semibold text-text-primary">
                            {treatment.rencana_kode}
                          </p>
                          <Badge variant="success" size="sm">Penanganan</Badge>
                        </div>
                        {treatment.rencana_nama && (
                          <p className="mt-1 text-xs font-medium text-text-secondary">
                            {treatment.rencana_nama}
                          </p>
                        )}
                        {treatment.relevansi && (
                          <p className="mt-2 text-xs text-text-secondary">
                            <span className="font-medium">Relevansi:</span> {treatment.relevansi}
                          </p>
                        )}
                        <div className="mt-2 flex items-center gap-2 text-xs text-text-muted">
                          <Calendar className="h-3 w-3" />
                          <span>Ditambahkan: {formatDate(treatment.ditambahkan_pada)}</span>
                        </div>
                        <div className="mt-2">
                          <Link 
                            to="/penanganan-risiko" 
                            className="text-xs text-brand hover:text-brand-dark hover:underline"
                          >
                            Lihat Penanganan Risiko →
                          </Link>
                        </div>
                      </div>
                    </div>
                  </div>
                ))}
              </div>
            )}
          </Card>
        )}
      </div>
    </div>
  );
}
