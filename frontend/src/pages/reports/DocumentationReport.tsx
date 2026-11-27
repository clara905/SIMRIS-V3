import { useEffect, useState } from 'react';
import { FileText, Download, BookOpen, Eye } from 'lucide-react';
import { reportsService } from '@/services/reports.service';
import { getFileIcon, formatFileSize, getDocumentStatusBadge, getSOPStatusBadge } from '@/types/document.types';
import { PageHeader, Breadcrumb } from '@/components/common/PageHeader';
import { Button } from '@/components/ui/Button';
import { Badge } from '@/components/ui/Badge';
import { Card, CardHeader } from '@/components/ui/Card';
import { StatCard } from '@/components/ui/StatCard';
import { PageLoader } from '@/components/ui/LoadingSpinner';
import { Table, TableHead, TableBody, TableRow, Th, Td, TableContainer } from '@/components/ui/Table';
import {
  PieChart as RechartsPieChart,
  Pie,
  Cell,
  ResponsiveContainer,
  Tooltip,
  Legend,
} from 'recharts';

// ─── Helper Functions ────────────────────────────────────────────────────────

function formatDate(dateString: string): string {
  const date = new Date(dateString);
  return date.toLocaleDateString('id-ID', {
    year: 'numeric',
    month: 'short',
    day: 'numeric',
  });
}

// ─── Component ───────────────────────────────────────────────────────────────

export function DocumentationReport() {
  const [data, setData] = useState<any>(null);
  const [loading, setLoading] = useState(true);

  useEffect(() => {
    reportsService.getDocumentReportData()
      .then(setData)
      .finally(() => setLoading(false));
  }, []);

  if (loading) return <PageLoader />;

  const handleExport = () => {
    alert('Fitur ekspor akan tersedia setelah integrasi backend.\n\nData yang akan diekspor:\n' +
      `- Total Dokumen: ${data?.totalDocuments}\n` +
      `- Total SOP: ${data?.totalSOPs}\n` +
      `- Format: PDF / Excel`);
  };

  // Chart data
  const docTypeData = data?.documentsByType.map((item: any) => ({
    name: item.type,
    value: item.count,
  })) || [];

  const docTypeColors = ['#762127', '#2563EB', '#D97706'];

  const sopStatusData = [
    { name: 'Aktif', value: data?.activeSOPs || 0, color: '#16A34A' },
    { name: 'Non-Aktif', value: (data?.totalSOPs || 0) - (data?.activeSOPs || 0), color: '#9CA3AF' },
  ];

  return (
    <div className="space-y-5">
      <PageHeader
        title="Laporan Dokumentasi & SOP"
        description="Ringkasan dokumen dan Standard Operating Procedure"
        breadcrumb={
          <Breadcrumb
            items={[
              { label: 'Laporan', to: '/laporan' },
              { label: 'Dokumentasi' },
            ]}
          />
        }
        action={
          <Button
            variant="primary"
            leftIcon={<Download className="h-4 w-4" />}
            onClick={handleExport}
          >
            Ekspor Laporan
          </Button>
        }
      />

      {/* Summary Stats */}
      {data && (
        <div className="grid grid-cols-1 gap-4 sm:grid-cols-2 xl:grid-cols-4">
          <StatCard
            label="Total Dokumen"
            value={data.totalDocuments}
            icon={FileText}
            supporting="Dokumen terdaftar"
            variant="info"
          />
          <StatCard
            label="Dokumen Asesmen"
            value={data.documentsByType.find((t: any) => t.type === 'Asesmen')?.count || 0}
            icon={FileText}
            supporting="Assessment evidence"
          />
          <StatCard
            label="Total SOP"
            value={data.totalSOPs}
            icon={BookOpen}
            supporting="Prosedur standar"
          />
          <StatCard
            label="SOP Aktif"
            value={data.activeSOPs}
            icon={BookOpen}
            supporting="Prosedur berlaku"
            variant="success"
          />
        </div>
      )}

      {/* Charts */}
      {data && (
        <div className="grid grid-cols-1 gap-6 lg:grid-cols-2">
          <Card padding="md">
            <CardHeader title="Distribusi Tipe Dokumen" className="mb-4" />
            <div className="h-64">
              <ResponsiveContainer width="100%" height="100%">
                <RechartsPieChart>
                  <Pie
                    data={docTypeData}
                    dataKey="value"
                    nameKey="name"
                    cx="50%"
                    cy="50%"
                    outerRadius={90}
                    innerRadius={55}
                  >
                    {docTypeData.map((entry: any, index: number) => (
                      <Cell key={`cell-${index}`} fill={docTypeColors[index % docTypeColors.length]} />
                    ))}
                  </Pie>
                  <Tooltip contentStyle={{ borderRadius: 8, border: '1px solid #E5E7EB', fontSize: 12 }} />
                  <Legend iconType="circle" iconSize={8} wrapperStyle={{ fontSize: 12 }} />
                </RechartsPieChart>
              </ResponsiveContainer>
            </div>
          </Card>

          <Card padding="md">
            <CardHeader title="Status SOP" className="mb-4" />
            <div className="h-64">
              <ResponsiveContainer width="100%" height="100%">
                <RechartsPieChart>
                  <Pie
                    data={sopStatusData}
                    dataKey="value"
                    nameKey="name"
                    cx="50%"
                    cy="50%"
                    outerRadius={90}
                    innerRadius={55}
                  >
                    {sopStatusData.map((entry, index) => (
                      <Cell key={`cell-${index}`} fill={entry.color} />
                    ))}
                  </Pie>
                  <Tooltip contentStyle={{ borderRadius: 8, border: '1px solid #E5E7EB', fontSize: 12 }} />
                  <Legend iconType="circle" iconSize={8} wrapperStyle={{ fontSize: 12 }} />
                </RechartsPieChart>
              </ResponsiveContainer>
            </div>
          </Card>
        </div>
      )}

      {/* Recent Documents */}
      {data?.recentDocuments && (
        <Card padding="none">
          <div className="border-b border-border px-5 py-4">
            <CardHeader title="Dokumen Terbaru" description="5 dokumen terakhir yang diunggah" />
          </div>
          <TableContainer>
            <Table>
              <TableHead>
                <TableRow>
                  <Th width="40px"></Th>
                  <Th>Judul Dokumen</Th>
                  <Th>Tipe</Th>
                  <Th>Ukuran</Th>
                  <Th>Status</Th>
                  <Th>Tanggal</Th>
                  <Th align="right">Aksi</Th>
                </TableRow>
              </TableHead>
              <TableBody>
                {data.recentDocuments.map((doc: any) => {
                  const statusBadge = getDocumentStatusBadge(doc.status_scan);
                  return (
                    <TableRow key={doc.id} className="hover:bg-surface-muted/50">
                      <Td align="center">
                        <div className="text-xl">{getFileIcon(doc.ekstensi)}</div>
                      </Td>
                      <Td primary>
                        <div>
                          <p className="font-medium text-text-primary">{doc.judul}</p>
                          <p className="text-xs text-text-muted">{doc.kode_dokumen}</p>
                        </div>
                      </Td>
                      <Td secondary>{doc.tipe_dokumen}</Td>
                      <Td secondary>{formatFileSize(doc.ukuran_byte)}</Td>
                      <Td>
                        <Badge variant={statusBadge.variant as any} size="sm">
                          {statusBadge.label}
                        </Badge>
                      </Td>
                      <Td secondary>{formatDate(doc.diunggah_pada)}</Td>
                      <Td align="right">
                        <button
                          onClick={() => {
                            alert(`Detail dokumen ${doc.kode_dokumen} akan ditampilkan.\nIntegrasi dengan modul Documentation.`);
                          }}
                          className="rounded p-1.5 text-text-muted hover:bg-surface-muted hover:text-brand"
                          title="Lihat detail"
                        >
                          <Eye className="h-4 w-4" />
                        </button>
                      </Td>
                    </TableRow>
                  );
                })}
              </TableBody>
            </Table>
          </TableContainer>
        </Card>
      )}

      {/* SOP List */}
      {data?.sops && (
        <Card padding="none">
          <div className="border-b border-border px-5 py-4">
            <CardHeader title="Daftar SOP" description="Standard Operating Procedures" />
          </div>
          <TableContainer>
            <Table>
              <TableHead>
                <TableRow>
                  <Th>Kode & Judul</Th>
                  <Th>Unit Pemilik</Th>
                  <Th>Versi Aktif</Th>
                  <Th>Status</Th>
                  <Th align="right">Aksi</Th>
                </TableRow>
              </TableHead>
              <TableBody>
                {data.sops.map((sop: any) => {
                  const statusBadge = getSOPStatusBadge(sop.status);
                  return (
                    <TableRow key={sop.id} className="hover:bg-surface-muted/50">
                      <Td primary>
                        <div>
                          <p className="font-medium text-text-primary">{sop.judul}</p>
                          <p className="text-xs text-text-muted">{sop.kode}</p>
                        </div>
                      </Td>
                      <Td secondary>{sop.unit_pemilik}</Td>
                      <Td>
                        {sop.versi_aktif ? (
                          <Badge variant="neutral" size="sm">
                            v{sop.versi_aktif}
                          </Badge>
                        ) : (
                          <span className="text-xs text-text-muted">-</span>
                        )}
                      </Td>
                      <Td>
                        <Badge variant={statusBadge.variant as any} size="sm" dot>
                          {statusBadge.label}
                        </Badge>
                      </Td>
                      <Td align="right">
                        <button
                          onClick={() => {
                            alert(`Detail SOP ${sop.kode} akan ditampilkan.\nIntegrasi dengan modul SOP.`);
                          }}
                          className="rounded p-1.5 text-text-muted hover:bg-surface-muted hover:text-brand"
                          title="Lihat detail"
                        >
                          <Eye className="h-4 w-4" />
                        </button>
                      </Td>
                    </TableRow>
                  );
                })}
              </TableBody>
            </Table>
          </TableContainer>
        </Card>
      )}

      {/* Info Banner */}
      <Card padding="md" className="border-l-[3px] border-l-brand bg-brand-muted/20">
        <div className="flex items-start gap-3">
          <BookOpen className="h-5 w-5 shrink-0 text-brand" />
          <div className="min-w-0 flex-1">
            <p className="text-sm font-medium text-text-primary">
              Data dari Modul Documentation & SOP
            </p>
            <p className="mt-1 text-xs text-text-secondary">
              Laporan ini mengkonsolidasikan data dari modul Documentation dan SOP Management. 
              Dokumen terkait asesmen, treatment, dan kondisi aset terintegrasi dengan modul terkait.
            </p>
          </div>
        </div>
      </Card>
    </div>
  );
}
