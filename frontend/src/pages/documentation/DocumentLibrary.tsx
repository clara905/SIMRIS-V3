import { useEffect, useState, useCallback } from 'react';
import { Link, useNavigate } from 'react-router-dom';
import { FileText, Search, Filter, Eye, Download } from 'lucide-react';
import { documentService } from '@/services/document.service';
import type { Dokumen } from '@/types/document.types';
import { getFileIcon, formatFileSize, getDocumentStatusBadge } from '@/types/document.types';
import type { PaginatedResponse } from '@/types/common.types';
import { PageHeader, Breadcrumb } from '@/components/common/PageHeader';
import { SearchInput } from '@/components/common/SearchInput';
import { Button } from '@/components/ui/Button';
import { Badge } from '@/components/ui/Badge';
import { Card } from '@/components/ui/Card';
import { Select } from '@/components/ui/Select';
import { Pagination } from '@/components/ui/Pagination';
import { PageLoader } from '@/components/ui/LoadingSpinner';
import { EmptyState } from '@/components/ui/EmptyState';
import { Table, TableHead, TableBody, TableRow, Th, Td, TableContainer } from '@/components/ui/Table';

// ─── Filter Options ──────────────────────────────────────────────────────────

const TIPE_DOKUMEN_OPTIONS = [
  { value: '', label: 'Semua Tipe' },
  { value: 'asesmen', label: 'Asesmen' },
  { value: 'penanganan', label: 'Penanganan' },
  { value: 'kondisi_aset', label: 'Kondisi Aset' },
  { value: 'sop', label: 'SOP' },
  { value: 'kontrol', label: 'Kontrol' },
  { value: 'laporan', label: 'Laporan' },
  { value: 'lainnya', label: 'Lainnya' },
];

const STATUS_SCAN_OPTIONS = [
  { value: '', label: 'Semua Status' },
  { value: 'clean', label: 'Aman' },
  { value: 'pending', label: 'Menunggu' },
  { value: 'infected', label: 'Terinfeksi' },
  { value: 'error', label: 'Error' },
];

// ─── Helper Functions ────────────────────────────────────────────────────────

function formatDate(dateString: string): string {
  const date = new Date(dateString);
  return date.toLocaleDateString('id-ID', {
    year: 'numeric',
    month: 'short',
    day: 'numeric',
  });
}

function getTipeDokumenLabel(tipe: string): string {
  const labels: Record<string, string> = {
    asesmen: 'Asesmen',
    penanganan: 'Penanganan',
    kondisi_aset: 'Kondisi Aset',
    sop: 'SOP',
    kontrol: 'Kontrol',
    laporan: 'Laporan',
    lainnya: 'Lainnya',
  };
  return labels[tipe] || tipe;
}

function getTipeDokumenBadge(tipe: string) {
  const variants: Record<string, any> = {
    asesmen: 'info',
    penanganan: 'success',
    kondisi_aset: 'warning',
    sop: 'brand',
    kontrol: 'neutral',
    laporan: 'neutral',
    lainnya: 'muted',
  };
  return <Badge variant={variants[tipe] || 'neutral'}>{getTipeDokumenLabel(tipe)}</Badge>;
}

// ─── Component ───────────────────────────────────────────────────────────────

export function DocumentLibrary() {
  const navigate = useNavigate();
  const [result, setResult] = useState<PaginatedResponse<Dokumen> | null>(null);
  const [loading, setLoading] = useState(true);
  const [search, setSearch] = useState('');
  const [tipeDokumen, setTipeDokumen] = useState('');
  const [statusScan, setStatusScan] = useState('');
  const [page, setPage] = useState(1);

  const fetchDocuments = useCallback(() => {
    setLoading(true);
    documentService
      .getDocuments({
        page,
        per_page: 15,
        search,
        tipe_dokumen: tipeDokumen,
        status_scan: statusScan,
      })
      .then(setResult)
      .finally(() => setLoading(false));
  }, [page, search, tipeDokumen, statusScan]);

  useEffect(() => {
    fetchDocuments();
  }, [fetchDocuments]);

  const handleSearch = (value: string) => {
    setSearch(value);
    setPage(1);
  };

  const handleTipeChange = (value: string) => {
    setTipeDokumen(value);
    setPage(1);
  };

  const handleStatusChange = (value: string) => {
    setStatusScan(value);
    setPage(1);
  };

  const handleViewDocument = (doc: Dokumen) => {
    navigate(`/dokumentasi/library/${doc.id}`);
  };

  const documents = result?.data ?? [];
  const hasFilters = search || tipeDokumen || statusScan;

  return (
    <div className="space-y-5">
      <PageHeader
        title="Library Dokumen"
        description="Kelola dan akses semua dokumen keamanan informasi"
        breadcrumb={
          <Breadcrumb
            items={[
              { label: 'Dokumentasi', to: '/dokumentasi' },
              { label: 'Library' },
            ]}
          />
        }
      />

      {/* Filters */}
      <Card padding="md">
        <div className="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
          <div className="flex-1 max-w-md">
            <SearchInput
              placeholder="Cari dokumen..."
              value={search}
              onChange={handleSearch}
            />
          </div>
          <div className="flex flex-wrap gap-2">
            <Select
              value={tipeDokumen}
              onChange={handleTipeChange}
              options={TIPE_DOKUMEN_OPTIONS}
              className="w-40"
            />
            <Select
              value={statusScan}
              onChange={handleStatusChange}
              options={STATUS_SCAN_OPTIONS}
              className="w-40"
            />
            {hasFilters && (
              <Button
                size="sm"
                variant="outline"
                onClick={() => {
                  setSearch('');
                  setTipeDokumen('');
                  setStatusScan('');
                  setPage(1);
                }}
              >
                Reset
              </Button>
            )}
          </div>
        </div>
      </Card>

      {/* Document Table */}
      <Card padding="none">
        {loading ? (
          <div className="flex items-center justify-center py-12">
            <PageLoader />
          </div>
        ) : documents.length === 0 ? (
          <EmptyState
            icon={FileText}
            title="Tidak ada dokumen"
            description={
              hasFilters
                ? 'Tidak ada dokumen yang sesuai dengan filter. Coba ubah filter pencarian.'
                : 'Belum ada dokumen yang diunggah.'
            }
          />
        ) : (
          <>
            <TableContainer>
              <Table>
                <TableHead>
                  <TableRow>
                    <Th width="40px"></Th>
                    <Th>Kode & Judul</Th>
                    <Th>Tipe</Th>
                    <Th>File</Th>
                    <Th>Ukuran</Th>
                    <Th>Status</Th>
                    <Th>Diunggah</Th>
                    <Th align="right">Aksi</Th>
                  </TableRow>
                </TableHead>
                <TableBody>
                  {documents.map((doc) => {
                    const statusBadge = getDocumentStatusBadge(doc.status_scan);
                    return (
                      <TableRow
                        key={doc.id}
                        onClick={() => handleViewDocument(doc)}
                        className="cursor-pointer hover:bg-surface-muted/50"
                      >
                        <Td align="center">
                          <div className="flex items-center justify-center text-xl">
                            {getFileIcon(doc.ekstensi)}
                          </div>
                        </Td>
                        <Td primary>
                          <div>
                            <p className="font-medium text-text-primary">{doc.judul}</p>
                            <p className="text-xs text-text-muted">{doc.kode_dokumen}</p>
                          </div>
                        </Td>
                        <Td>{getTipeDokumenBadge(doc.tipe_dokumen)}</Td>
                        <Td secondary>
                          <div className="max-w-[200px] truncate" title={doc.nama_file_asli}>
                            {doc.nama_file_asli}
                          </div>
                          <p className="text-xs text-text-muted">{doc.ekstensi}</p>
                        </Td>
                        <Td secondary>{formatFileSize(doc.ukuran_byte)}</Td>
                        <Td>
                          <Badge variant={statusBadge.variant as any} size="sm">
                            {statusBadge.label}
                          </Badge>
                        </Td>
                        <Td secondary>
                          <div>
                            <p className="text-xs">{doc.diunggah_oleh_nama}</p>
                            <p className="text-xs text-text-muted">{formatDate(doc.diunggah_pada)}</p>
                          </div>
                        </Td>
                        <Td align="right">
                          <div className="flex items-center justify-end gap-1">
                            <button
                              onClick={(e) => {
                                e.stopPropagation();
                                handleViewDocument(doc);
                              }}
                              className="rounded p-1.5 text-text-muted hover:bg-surface-muted hover:text-brand"
                              title="Lihat detail"
                            >
                              <Eye className="h-4 w-4" />
                            </button>
                            <button
                              onClick={(e) => {
                                e.stopPropagation();
                                // Mock download - no actual file available
                                alert('Download tidak tersedia dalam mode mock. File path: ' + doc.path_penyimpanan);
                              }}
                              className="rounded p-1.5 text-text-muted hover:bg-surface-muted hover:text-brand"
                              title="Download (mock)"
                            >
                              <Download className="h-4 w-4" />
                            </button>
                          </div>
                        </Td>
                      </TableRow>
                    );
                  })}
                </TableBody>
              </Table>
            </TableContainer>

            {/* Pagination */}
            {result && result.meta.last_page > 1 && (
              <div className="border-t border-border px-5 py-4">
                <Pagination
                  currentPage={result.meta.current_page}
                  totalPages={result.meta.last_page}
                  onPageChange={setPage}
                  totalItems={result.meta.total}
                  perPage={result.meta.per_page}
                />
              </div>
            )}
          </>
        )}
      </Card>

      {/* Info Banner */}
      {!loading && documents.length > 0 && (
        <Card padding="md" className="border-l-[3px] border-l-blue-600 bg-blue-50/50">
          <div className="flex items-start gap-3">
            <FileText className="h-5 w-5 shrink-0 text-blue-700" />
            <div className="min-w-0 flex-1">
              <p className="text-sm font-medium text-blue-900">
                Menampilkan {result?.meta.from ?? 0} - {result?.meta.to ?? 0} dari {result?.meta.total ?? 0} dokumen
              </p>
              <p className="mt-1 text-xs text-blue-800">
                Download dokumen hanya tersedia dalam mode frontend mock. Integrasi backend diperlukan untuk akses file sebenarnya.
              </p>
            </div>
          </div>
        </Card>
      )}
    </div>
  );
}
