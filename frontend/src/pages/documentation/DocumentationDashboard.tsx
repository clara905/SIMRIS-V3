import { useEffect, useState } from 'react';
import { Link } from 'react-router-dom';
import {
  FileText,
  ClipboardList,
  ShieldCheck,
  BookOpen,
  ArrowRight,
  Clock,
} from 'lucide-react';
import { documentService, type DocumentationStats } from '@/services/document.service';
import type { Dokumen } from '@/types/document.types';
import { getFileIcon, formatFileSize, getDocumentStatusBadge } from '@/types/document.types';
import { StatCard } from '@/components/ui/StatCard';
import { Card, CardHeader } from '@/components/ui/Card';
import { Badge } from '@/components/ui/Badge';
import { Button } from '@/components/ui/Button';
import { PageLoader } from '@/components/ui/LoadingSpinner';
import { PageHeader } from '@/components/common/PageHeader';

function formatRelativeTime(dateString: string): string {
  const date = new Date(dateString);
  const now = new Date();
  const diffMs = now.getTime() - date.getTime();
  const diffDays = Math.floor(diffMs / (1000 * 60 * 60 * 24));
  
  if (diffDays === 0) return 'Hari ini';
  if (diffDays === 1) return 'Kemarin';
  if (diffDays < 7) return `${diffDays} hari yang lalu`;
  if (diffDays < 30) return `${Math.floor(diffDays / 7)} minggu yang lalu`;
  return date.toLocaleDateString('id-ID', { year: 'numeric', month: 'short', day: 'numeric' });
}

export function DocumentationDashboard() {
  const [stats, setStats] = useState<DocumentationStats | null>(null);
  const [loading, setLoading] = useState(true);

  useEffect(() => {
    documentService.getStats()
      .then(setStats)
      .finally(() => setLoading(false));
  }, []);

  if (loading) return <PageLoader />;

  return (
    <div className="space-y-6">
      <PageHeader
        title="Dokumentasi"
        description="Kelola dokumen dan SOP keamanan informasi"
      />

      {/* Stats */}
      <div className="grid grid-cols-1 gap-4 sm:grid-cols-2 xl:grid-cols-4">
        <StatCard
          label="Total Dokumen"
          value={stats?.totalDokumen ?? 0}
          icon={FileText}
          supporting="Semua jenis dokumen"
          variant="info"
        />
        <StatCard
          label="Dokumen Asesmen"
          value={stats?.dokumenAsesmen ?? 0}
          icon={ClipboardList}
          supporting="Terkait asesmen risiko"
        />
        <StatCard
          label="Bukti Penanganan"
          value={stats?.dokumenPenanganan ?? 0}
          icon={ShieldCheck}
          supporting="Implementasi penanganan"
        />
        <StatCard
          label="Dokumen SOP"
          value={stats?.dokumenSOP ?? 0}
          icon={BookOpen}
          supporting="Standard Operating Procedure"
        />
      </div>

      <div className="grid grid-cols-1 gap-6 lg:grid-cols-3">
        {/* Recent Documents */}
        <Card className="lg:col-span-2" padding="none">
          <div className="flex items-center justify-between border-b border-border px-5 py-4">
            <CardHeader 
              title="Dokumen Terbaru" 
              description="Dokumen yang baru diunggah" 
            />
            <Link to="/dokumentasi/library">
              <Button size="sm" variant="secondary">
                Lihat Semua
              </Button>
            </Link>
          </div>
          <div className="divide-y divide-border">
            {!stats?.recentDocuments || stats.recentDocuments.length === 0 ? (
              <div className="px-5 py-8 text-center">
                <FileText className="mx-auto h-10 w-10 text-text-muted" />
                <p className="mt-2 text-sm font-medium text-text-primary">Belum ada dokumen</p>
                <p className="mt-1 text-xs text-text-muted">Dokumen yang diunggah akan muncul di sini</p>
              </div>
            ) : (
              stats.recentDocuments.map((doc) => {
                const statusBadge = getDocumentStatusBadge(doc.status_scan);
                return (
                  <Link
                    key={doc.id}
                    to={`/dokumentasi/library/${doc.id}`}
                    className="flex items-start gap-3 px-5 py-3.5 hover:bg-surface-muted/50"
                  >
                    <div className="flex h-10 w-10 shrink-0 items-center justify-center rounded-lg bg-brand-muted text-2xl">
                      {getFileIcon(doc.ekstensi)}
                    </div>
                    <div className="min-w-0 flex-1">
                      <div className="flex items-center gap-2">
                        <p className="truncate text-sm font-medium text-text-primary">
                          {doc.judul}
                        </p>
                        <Badge variant={statusBadge.variant as any} size="sm" className="shrink-0">
                          {statusBadge.label}
                        </Badge>
                      </div>
                      <p className="mt-0.5 text-xs text-text-muted">
                        {doc.nama_file_asli} • {formatFileSize(doc.ukuran_byte)}
                      </p>
                      <p className="mt-0.5 text-xs text-text-secondary">
                        {doc.diunggah_oleh_nama} • {formatRelativeTime(doc.diunggah_pada)}
                      </p>
                    </div>
                    <button className="shrink-0 rounded p-1.5 text-text-muted hover:bg-surface-muted hover:text-brand">
                      <ArrowRight className="h-4 w-4" />
                    </button>
                  </Link>
                );
              })
            )}
          </div>
        </Card>

        {/* Quick Actions & Activity */}
        <div className="space-y-4">
          {/* Quick Actions */}
          <Card padding="md">
            <CardHeader title="Aksi Cepat" className="mb-4" />
            <div className="space-y-2">
              <Link to="/dokumentasi/library" className="block">
                <Button 
                  variant="outline" 
                  className="w-full justify-start" 
                  leftIcon={<FileText className="h-4 w-4" />}
                >
                  Library Dokumen
                </Button>
              </Link>
              <Link to="/sop" className="block">
                <Button 
                  variant="outline" 
                  className="w-full justify-start" 
                  leftIcon={<BookOpen className="h-4 w-4" />}
                >
                  Kelola SOP
                </Button>
              </Link>
              <Link to="/asesmen-risiko" className="block">
                <Button 
                  variant="outline" 
                  className="w-full justify-start" 
                  leftIcon={<ClipboardList className="h-4 w-4" />}
                >
                  Lihat Asesmen
                </Button>
              </Link>
            </div>
          </Card>

          {/* Document Activity */}
          <Card padding="none">
            <div className="border-b border-border px-5 py-4">
              <h3 className="text-base font-semibold text-text-primary">Aktivitas Terkini</h3>
            </div>
            <div className="divide-y divide-border">
              {!stats?.documentActivity || stats.documentActivity.length === 0 ? (
                <div className="px-5 py-6 text-center text-xs text-text-muted">
                  Belum ada aktivitas tercatat
                </div>
              ) : (
                stats.documentActivity.map((activity) => (
                  <div key={activity.id} className="flex items-start gap-3 px-5 py-3">
                    <div className="mt-0.5 flex h-7 w-7 shrink-0 items-center justify-center rounded-full bg-brand-muted">
                      <FileText className="h-3.5 w-3.5 text-brand" />
                    </div>
                    <div className="min-w-0 flex-1">
                      <p className="text-xs text-text-primary">{activity.aksi}</p>
                      <p className="mt-0.5 truncate text-xs font-medium text-text-secondary">
                        {activity.dokumen_judul}
                      </p>
                      <p className="mt-0.5 text-[10px] text-text-muted flex items-center gap-1">
                        <Clock className="h-3 w-3" />
                        {activity.waktu}
                      </p>
                    </div>
                  </div>
                ))
              )}
            </div>
          </Card>
        </div>
      </div>

      {/* Information Banner */}
      <Card padding="md" accent="brand">
        <div className="flex items-start gap-3">
          <div className="flex h-8 w-8 shrink-0 items-center justify-center rounded-lg bg-brand-muted">
            <FileText className="h-4 w-4 text-brand" />
          </div>
          <div className="min-w-0 flex-1">
            <h4 className="text-sm font-semibold text-text-primary">Kelola Dokumentasi dengan Baik</h4>
            <p className="mt-1 text-xs text-text-secondary">
              Pastikan semua dokumen terkait asesmen risiko, penanganan, dan SOP tersimpan dengan 
              aman dan terorganisir. Dokumentasi yang baik mendukung audit dan compliance.
            </p>
          </div>
        </div>
      </Card>
    </div>
  );
}
