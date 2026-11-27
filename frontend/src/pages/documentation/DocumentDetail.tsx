import { useEffect, useState } from 'react';
import { useParams, useNavigate, Link } from 'react-router-dom';
import {
  FileText,
  Download,
  ChevronLeft,
  Calendar,
  User,
  HardDrive,
  Shield,
  AlertCircle,
  CheckCircle,
  Server,
  ClipboardList,
  ShieldCheck,
  BookOpen,
  FileCheck,
  Package,
} from 'lucide-react';
import { documentService } from '@/services/document.service';
import type { Dokumen, BuktiPenilaian, BuktiPenanganan, BuktiKondisiAset } from '@/types/document.types';
import { getFileIcon, formatFileSize, getDocumentStatusBadge } from '@/types/document.types';
import { PageHeader, Breadcrumb } from '@/components/common/PageHeader';
import { Button } from '@/components/ui/Button';
import { Badge } from '@/components/ui/Badge';
import { Card, CardHeader, CardSection } from '@/components/ui/Card';
import { PageLoader } from '@/components/ui/LoadingSpinner';

// ─── Helper Functions ────────────────────────────────────────────────────────

function formatDate(dateString: string): string {
  const date = new Date(dateString);
  return date.toLocaleDateString('id-ID', {
    weekday: 'long',
    year: 'numeric',
    month: 'long',
    day: 'numeric',
    hour: '2-digit',
    minute: '2-digit',
  });
}

function getTipeDokumenLabel(tipe: string): string {
  const labels: Record<string, string> = {
    asesmen: 'Dokumen Asesmen Risiko',
    penanganan: 'Bukti Penanganan Risiko',
    kondisi_aset: 'Bukti Kondisi Aset',
    sop: 'Standard Operating Procedure',
    kontrol: 'Kontrol Keamanan',
    laporan: 'Laporan',
    lainnya: 'Dokumen Lainnya',
  };
  return labels[tipe] || tipe;
}

// ─── Component ───────────────────────────────────────────────────────────────

export function DocumentDetail() {
  const { id } = useParams<{ id: string }>();
  const navigate = useNavigate();
  const [document, setDocument] = useState<Dokumen | null>(null);
  const [relationships, setRelationships] = useState<{
    buktiPenilaian: BuktiPenilaian[];
    buktiPenanganan: BuktiPenanganan[];
    buktiKondisiAset: BuktiKondisiAset[];
  } | null>(null);
  const [loading, setLoading] = useState(true);
  const [relationshipsLoading, setRelationshipsLoading] = useState(true);

  useEffect(() => {
    if (!id) return;
    const docId = parseInt(id);
    
    documentService
      .getDocument(docId)
      .then(setDocument)
      .finally(() => setLoading(false));
    
    // Load evidence relationships (Phase 7.1)
    documentService
      .getDocumentEvidenceRelationships(docId)
      .then(setRelationships)
      .finally(() => setRelationshipsLoading(false));
  }, [id]);

  if (loading) return <PageLoader />;

  if (!document) {
    return (
      <div className="space-y-5">
        <PageHeader
          title="Dokumen Tidak Ditemukan"
          description="Dokumen yang Anda cari tidak tersedia"
        />
        <Card padding="lg">
          <div className="text-center">
            <AlertCircle className="mx-auto h-12 w-12 text-red-600" />
            <p className="mt-4 text-text-secondary">Dokumen dengan ID {id} tidak ditemukan.</p>
            <Button className="mt-4" onClick={() => navigate('/dokumentasi/library')}>
              Kembali ke Library
            </Button>
          </div>
        </Card>
      </div>
    );
  }

  const statusBadge = getDocumentStatusBadge(document.status_scan);

  return (
    <div className="space-y-5">
      <PageHeader
        title={document.judul}
        description={getTipeDokumenLabel(document.tipe_dokumen)}
        breadcrumb={
          <Breadcrumb
            items={[
              { label: 'Dokumentasi', to: '/dokumentasi' },
              { label: 'Library', to: '/dokumentasi/library' },
              { label: document.kode_dokumen },
            ]}
          />
        }
        action={
          <div className="flex gap-2">
            <Button
              variant="outline"
              leftIcon={<ChevronLeft className="h-4 w-4" />}
              onClick={() => navigate('/dokumentasi/library')}
            >
              Kembali
            </Button>
            <Button
              variant="primary"
              leftIcon={<Download className="h-4 w-4" />}
              onClick={() => {
                alert('Download tidak tersedia dalam mode mock.\n\nFile path: ' + document.path_penyimpanan);
              }}
            >
              Download
            </Button>
          </div>
        }
      />

      <div className="grid grid-cols-1 gap-6 lg:grid-cols-3">
        {/* Main Content */}
        <div className="lg:col-span-2 space-y-6">
          {/* Document Preview Card */}
          <Card padding="none">
            <div className="border-b border-border px-5 py-4">
              <CardHeader title="Preview Dokumen" />
            </div>
            <div className="p-6">
              <div className="flex flex-col items-center justify-center rounded-lg border-2 border-dashed border-border bg-surface-muted py-16">
                <div className="text-6xl mb-4">{getFileIcon(document.ekstensi)}</div>
                <p className="text-sm font-medium text-text-primary">{document.nama_file_asli}</p>
                <p className="mt-1 text-xs text-text-muted">{formatFileSize(document.ukuran_byte)}</p>
                <p className="mt-4 text-xs text-text-secondary">
                  Preview tidak tersedia dalam mode mock
                </p>
              </div>
            </div>
          </Card>

          {/* Document Information */}
          <Card padding="none">
            <div className="border-b border-border px-5 py-4">
              <CardHeader title="Informasi Dokumen" />
            </div>
            <div className="divide-y divide-border">
              <div className="flex items-start gap-3 px-5 py-3">
                <FileText className="h-5 w-5 shrink-0 text-text-muted mt-0.5" />
                <div className="min-w-0 flex-1">
                  <p className="text-xs text-text-muted">Kode Dokumen</p>
                  <p className="mt-0.5 text-sm font-medium text-text-primary">{document.kode_dokumen}</p>
                </div>
              </div>

              <div className="flex items-start gap-3 px-5 py-3">
                <FileText className="h-5 w-5 shrink-0 text-text-muted mt-0.5" />
                <div className="min-w-0 flex-1">
                  <p className="text-xs text-text-muted">Judul</p>
                  <p className="mt-0.5 text-sm font-medium text-text-primary">{document.judul}</p>
                </div>
              </div>

              <div className="flex items-start gap-3 px-5 py-3">
                <FileText className="h-5 w-5 shrink-0 text-text-muted mt-0.5" />
                <div className="min-w-0 flex-1">
                  <p className="text-xs text-text-muted">Tipe Dokumen</p>
                  <p className="mt-0.5 text-sm font-medium text-text-primary">
                    {getTipeDokumenLabel(document.tipe_dokumen)}
                  </p>
                </div>
              </div>

              {document.unit_pemilik && (
                <div className="flex items-start gap-3 px-5 py-3">
                  <HardDrive className="h-5 w-5 shrink-0 text-text-muted mt-0.5" />
                  <div className="min-w-0 flex-1">
                    <p className="text-xs text-text-muted">Unit Pemilik</p>
                    <p className="mt-0.5 text-sm font-medium text-text-primary">{document.unit_pemilik}</p>
                  </div>
                </div>
              )}

              <div className="flex items-start gap-3 px-5 py-3">
                <User className="h-5 w-5 shrink-0 text-text-muted mt-0.5" />
                <div className="min-w-0 flex-1">
                  <p className="text-xs text-text-muted">Diunggah Oleh</p>
                  <p className="mt-0.5 text-sm font-medium text-text-primary">{document.diunggah_oleh_nama}</p>
                </div>
              </div>

              <div className="flex items-start gap-3 px-5 py-3">
                <Calendar className="h-5 w-5 shrink-0 text-text-muted mt-0.5" />
                <div className="min-w-0 flex-1">
                  <p className="text-xs text-text-muted">Tanggal Unggah</p>
                  <p className="mt-0.5 text-sm font-medium text-text-primary">{formatDate(document.diunggah_pada)}</p>
                </div>
              </div>
            </div>
          </Card>

          {/* Related Entities - Phase 7.1 Implementation */}
          <Card padding="none">
            <div className="border-b border-border px-5 py-4">
              <CardHeader 
                title="Entitas Terkait" 
                description="Risiko, asesmen, dan penanganan yang terkait dengan dokumen ini"
              />
            </div>
            
            {relationshipsLoading ? (
              <div className="px-5 py-12 text-center">
                <div className="inline-block h-6 w-6 animate-spin rounded-full border-2 border-brand border-r-transparent"></div>
                <p className="mt-2 text-xs text-text-muted">Memuat relasi...</p>
              </div>
            ) : relationships && (
              relationships.buktiPenilaian.length === 0 &&
              relationships.buktiPenanganan.length === 0 &&
              relationships.buktiKondisiAset.length === 0
            ) ? (
              <div className="px-5 py-12 text-center">
                <FileText className="mx-auto h-10 w-10 text-text-muted" />
                <p className="mt-2 text-sm font-medium text-text-primary">Tidak ada entitas terkait</p>
                <p className="mt-1 text-xs text-text-muted">
                  Dokumen ini belum dikaitkan dengan asesmen, penanganan, atau aset
                </p>
              </div>
            ) : (
              <div className="divide-y divide-border">
                {/* Asesmen Risiko */}
                {relationships && relationships.buktiPenilaian.length > 0 && (
                  <div className="px-5 py-4">
                    <div className="mb-3 flex items-center gap-2">
                      <ClipboardList className="h-4 w-4 text-brand" />
                      <h4 className="text-sm font-semibold text-text-primary">
                        Asesmen Risiko ({relationships.buktiPenilaian.length})
                      </h4>
                    </div>
                    <div className="space-y-3">
                      {relationships.buktiPenilaian.map((bukti) => (
                        <div key={bukti.id} className="rounded-lg border border-border bg-surface-muted/30 p-3">
                          <div className="flex items-start gap-3">
                            <div className="flex h-8 w-8 shrink-0 items-center justify-center rounded-lg bg-blue-100 dark:bg-blue-900/20">
                              <FileCheck className="h-4 w-4 text-blue-600 dark:text-blue-400" />
                            </div>
                            <div className="min-w-0 flex-1">
                              <div className="flex items-center gap-2 flex-wrap">
                                <p className="text-sm font-medium text-text-primary">
                                  {bukti.jenis_bukti}
                                </p>
                                <Badge variant="info" size="sm">Penilaian #{bukti.penilaian_risiko_id}</Badge>
                              </div>
                              {bukti.deskripsi && (
                                <p className="mt-1 text-xs text-text-secondary">{bukti.deskripsi}</p>
                              )}
                              <div className="mt-2 flex items-center gap-2 text-xs text-text-muted">
                                <User className="h-3 w-3" />
                                <span>{bukti.diunggah_oleh_nama}</span>
                                <span>•</span>
                                <Calendar className="h-3 w-3" />
                                <span>{formatDate(bukti.diunggah_pada)}</span>
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
                  </div>
                )}

                {/* Penanganan Risiko */}
                {relationships && relationships.buktiPenanganan.length > 0 && (
                  <div className="px-5 py-4">
                    <div className="mb-3 flex items-center gap-2">
                      <ShieldCheck className="h-4 w-4 text-brand" />
                      <h4 className="text-sm font-semibold text-text-primary">
                        Penanganan Risiko ({relationships.buktiPenanganan.length})
                      </h4>
                    </div>
                    <div className="space-y-3">
                      {relationships.buktiPenanganan.map((bukti) => (
                        <div key={bukti.id} className="rounded-lg border border-border bg-surface-muted/30 p-3">
                          <div className="flex items-start gap-3">
                            <div className="flex h-8 w-8 shrink-0 items-center justify-center rounded-lg bg-green-100 dark:bg-green-900/20">
                              <FileCheck className="h-4 w-4 text-green-600 dark:text-green-400" />
                            </div>
                            <div className="min-w-0 flex-1">
                              <div className="flex items-center gap-2 flex-wrap">
                                <p className="text-sm font-medium text-text-primary">
                                  {bukti.jenis_bukti}
                                </p>
                                {bukti.rencana_kode && (
                                  <Badge variant="success" size="sm">{bukti.rencana_kode}</Badge>
                                )}
                              </div>
                              {bukti.rencana_nama && (
                                <p className="mt-1 text-xs font-medium text-text-secondary">
                                  {bukti.rencana_nama}
                                </p>
                              )}
                              {bukti.progres_penanganan_id && (
                                <p className="mt-1 text-xs text-text-muted">
                                  Progres: {bukti.progres_penanganan_id}
                                </p>
                              )}
                              {bukti.deskripsi && (
                                <p className="mt-1 text-xs text-text-secondary">{bukti.deskripsi}</p>
                              )}
                              <div className="mt-2 flex items-center gap-2 text-xs text-text-muted">
                                <User className="h-3 w-3" />
                                <span>{bukti.diunggah_oleh_nama}</span>
                                <span>•</span>
                                <Calendar className="h-3 w-3" />
                                <span>{formatDate(bukti.diunggah_pada)}</span>
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
                  </div>
                )}

                {/* Kondisi Aset */}
                {relationships && relationships.buktiKondisiAset.length > 0 && (
                  <div className="px-5 py-4">
                    <div className="mb-3 flex items-center gap-2">
                      <Package className="h-4 w-4 text-brand" />
                      <h4 className="text-sm font-semibold text-text-primary">
                        Kondisi Aset ({relationships.buktiKondisiAset.length})
                      </h4>
                    </div>
                    <div className="space-y-3">
                      {relationships.buktiKondisiAset.map((bukti) => (
                        <div key={bukti.id} className="rounded-lg border border-border bg-surface-muted/30 p-3">
                          <div className="flex items-start gap-3">
                            <div className="flex h-8 w-8 shrink-0 items-center justify-center rounded-lg bg-amber-100 dark:bg-amber-900/20">
                              <Server className="h-4 w-4 text-amber-600 dark:text-amber-400" />
                            </div>
                            <div className="min-w-0 flex-1">
                              <div className="flex items-center gap-2 flex-wrap">
                                <p className="text-sm font-medium text-text-primary">
                                  {bukti.aset_nama}
                                </p>
                                {bukti.aset_kode && (
                                  <Badge variant="neutral" size="sm">{bukti.aset_kode}</Badge>
                                )}
                              </div>
                              <div className="mt-1 flex items-center gap-3 text-xs">
                                <span className="text-text-muted">
                                  Kondisi: <span className="font-medium text-text-primary">{bukti.kondisi_saat_itu}</span>
                                </span>
                                <span className="text-text-muted">•</span>
                                <span className="text-text-muted">
                                  <Calendar className="inline h-3 w-3" /> {formatDate(bukti.tanggal_kondisi)}
                                </span>
                              </div>
                              {bukti.deskripsi && (
                                <p className="mt-1 text-xs text-text-secondary">{bukti.deskripsi}</p>
                              )}
                              <div className="mt-2 flex items-center gap-2 text-xs text-text-muted">
                                <User className="h-3 w-3" />
                                <span>{bukti.diunggah_oleh_nama}</span>
                                <span>•</span>
                                <Calendar className="h-3 w-3" />
                                <span>{formatDate(bukti.diunggah_pada)}</span>
                              </div>
                              <div className="mt-2">
                                <Link 
                                  to="/aset" 
                                  className="text-xs text-brand hover:text-brand-dark hover:underline"
                                >
                                  Lihat Daftar Aset →
                                </Link>
                              </div>
                            </div>
                          </div>
                        </div>
                      ))}
                    </div>
                  </div>
                )}
              </div>
            )}
          </Card>
        </div>

        {/* Sidebar */}
        <div className="space-y-6">
          {/* File Metadata */}
          <Card padding="md">
            <CardHeader title="Metadata File" className="mb-4" />
            <div className="space-y-3">
              <div>
                <p className="text-xs text-text-muted">Nama File Asli</p>
                <p className="mt-0.5 text-sm font-medium text-text-primary break-all">
                  {document.nama_file_asli}
                </p>
              </div>

              <div>
                <p className="text-xs text-text-muted">Ekstensi</p>
                <p className="mt-0.5 text-sm font-medium text-text-primary">{document.ekstensi}</p>
              </div>

              {document.mime_type && (
                <div>
                  <p className="text-xs text-text-muted">MIME Type</p>
                  <p className="mt-0.5 text-sm font-mono text-xs text-text-secondary break-all">
                    {document.mime_type}
                  </p>
                </div>
              )}

              <div>
                <p className="text-xs text-text-muted">Ukuran File</p>
                <p className="mt-0.5 text-sm font-medium text-text-primary">
                  {formatFileSize(document.ukuran_byte)} ({document.ukuran_byte.toLocaleString('id-ID')} bytes)
                </p>
              </div>
            </div>
          </Card>

          {/* Security Info */}
          <Card padding="md">
            <CardHeader title="Informasi Keamanan" className="mb-4" />
            <div className="space-y-3">
              <div>
                <p className="text-xs text-text-muted mb-1">Status Pemindaian</p>
                <Badge 
                  variant={statusBadge.variant as any} 
                  dot
                  className="text-xs"
                >
                  {statusBadge.label}
                </Badge>
              </div>

              {document.checksum && (
                <div>
                  <p className="text-xs text-text-muted">Checksum</p>
                  <p className="mt-0.5 text-xs font-mono text-text-secondary break-all">
                    {document.checksum}
                  </p>
                </div>
              )}

              {document.path_penyimpanan && (
                <div>
                  <p className="text-xs text-text-muted">Path Penyimpanan</p>
                  <p className="mt-0.5 text-xs font-mono text-text-secondary break-all">
                    {document.path_penyimpanan}
                  </p>
                </div>
              )}
            </div>
          </Card>

          {/* Quick Links */}
          <Card padding="md">
            <CardHeader title="Tautan Cepat" className="mb-3" />
            <div className="space-y-2">
              <Link to="/dokumentasi/library" className="block">
                <Button variant="outline" size="sm" className="w-full justify-start" leftIcon={<FileText className="h-4 w-4" />}>
                  Library Dokumen
                </Button>
              </Link>
              <Link to="/asesmen-risiko" className="block">
                <Button variant="outline" size="sm" className="w-full justify-start" leftIcon={<ClipboardList className="h-4 w-4" />}>
                  Asesmen Risiko
                </Button>
              </Link>
              <Link to="/penanganan-risiko" className="block">
                <Button variant="outline" size="sm" className="w-full justify-start" leftIcon={<ShieldCheck className="h-4 w-4" />}>
                  Penanganan Risiko
                </Button>
              </Link>
              <Link to="/sop" className="block">
                <Button variant="outline" size="sm" className="w-full justify-start" leftIcon={<BookOpen className="h-4 w-4" />}>
                  Kelola SOP
                </Button>
              </Link>
            </div>
          </Card>
        </div>
      </div>
    </div>
  );
}
