import { useEffect, useState, useCallback } from 'react';
import { Link, useNavigate } from 'react-router-dom';
import { BookOpen, Eye, Calendar } from 'lucide-react';
import { documentService } from '@/services/document.service';
import type { SOP } from '@/types/document.types';
import { getSOPStatusBadge } from '@/types/document.types';
import type { PaginatedResponse } from '@/types/common.types';
import { PageHeader } from '@/components/common/PageHeader';
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

const STATUS_OPTIONS = [
  { value: '', label: 'Semua Status' },
  { value: 'aktif', label: 'Aktif' },
  { value: 'draft', label: 'Draft' },
  { value: 'revisi', label: 'Revisi' },
  { value: 'nonaktif', label: 'Nonaktif' },
];

// ─── Helper Functions ────────────────────────────────────────────────────────

function formatDate(dateString: string | null): string {
  if (!dateString) return '-';
  const date = new Date(dateString);
  return date.toLocaleDateString('id-ID', {
    year: 'numeric',
    month: 'short',
    day: 'numeric',
  });
}

// ─── Component ───────────────────────────────────────────────────────────────

export function SOPList() {
  const navigate = useNavigate();
  const [result, setResult] = useState<PaginatedResponse<SOP> | null>(null);
  const [loading, setLoading] = useState(true);
  const [search, setSearch] = useState('');
  const [status, setStatus] = useState('');
  const [page, setPage] = useState(1);

  const fetchSOPs = useCallback(() => {
    setLoading(true);
    documentService
      .getSOPs({
        page,
        per_page: 15,
        search,
        status,
      })
      .then(setResult)
      .finally(() => setLoading(false));
  }, [page, search, status]);

  useEffect(() => {
    fetchSOPs();
  }, [fetchSOPs]);

  const handleSearch = (value: string) => {
    setSearch(value);
    setPage(1);
  };

  const handleStatusChange = (value: string) => {
    setStatus(value);
    setPage(1);
  };

  const handleViewSOP = (sop: SOP) => {
    navigate(`/sop/${sop.id}`);
  };

  const sops = result?.data ?? [];
  const hasFilters = search || status;

  return (
    <div className="space-y-5">
      <PageHeader
        title="Standard Operating Procedure (SOP)"
        description="Kelola SOP keamanan informasi dan kontrol terkait"
      />

      {/* Filters */}
      <Card padding="md">
        <div className="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
          <div className="flex-1 max-w-md">
            <SearchInput
              placeholder="Cari SOP..."
              value={search}
              onChange={handleSearch}
            />
          </div>
          <div className="flex flex-wrap gap-2">
            <Select
              value={status}
              onChange={handleStatusChange}
              options={STATUS_OPTIONS}
              className="w-40"
            />
            {hasFilters && (
              <Button
                size="sm"
                variant="outline"
                onClick={() => {
                  setSearch('');
                  setStatus('');
                  setPage(1);
                }}
              >
                Reset
              </Button>
            )}
          </div>
        </div>
      </Card>

      {/* SOP Table */}
      <Card padding="none">
        {loading ? (
          <div className="flex items-center justify-center py-12">
            <PageLoader />
          </div>
        ) : sops.length === 0 ? (
          <EmptyState
            icon={BookOpen}
            title="Tidak ada SOP"
            description={
              hasFilters
                ? 'Tidak ada SOP yang sesuai dengan filter. Coba ubah filter pencarian.'
                : 'Belum ada SOP yang terdaftar.'
            }
          />
        ) : (
          <>
            <TableContainer>
              <Table>
                <TableHead>
                  <TableRow>
                    <Th>Kode & Judul</Th>
                    <Th>Unit Pemilik</Th>
                    <Th>Versi Aktif</Th>
                    <Th>Review Terakhir</Th>
                    <Th>Status</Th>
                    <Th align="right">Aksi</Th>
                  </TableRow>
                </TableHead>
                <TableBody>
                  {sops.map((sop) => {
                    const statusBadge = getSOPStatusBadge(sop.status);
                    return (
                      <TableRow
                        key={sop.id}
                        onClick={() => handleViewSOP(sop)}
                        className="cursor-pointer hover:bg-surface-muted/50"
                      >
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
                        <Td secondary>
                          {sop.tanggal_terakhir_review ? (
                            <div className="flex items-center gap-1">
                              <Calendar className="h-3 w-3" />
                              {formatDate(sop.tanggal_terakhir_review)}
                            </div>
                          ) : (
                            '-'
                          )}
                        </Td>
                        <Td>
                          <Badge variant={statusBadge.variant as any} size="sm" dot>
                            {statusBadge.label}
                          </Badge>
                        </Td>
                        <Td align="right">
                          <button
                            onClick={(e) => {
                              e.stopPropagation();
                              handleViewSOP(sop);
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
      {!loading && sops.length > 0 && (
        <Card padding="md" className="border-l-[3px] border-l-brand bg-brand-muted/20">
          <div className="flex items-start gap-3">
            <BookOpen className="h-5 w-5 shrink-0 text-brand" />
            <div className="min-w-0 flex-1">
              <p className="text-sm font-medium text-text-primary">
                Menampilkan {result?.meta.from ?? 0} - {result?.meta.to ?? 0} dari {result?.meta.total ?? 0} SOP
              </p>
              <p className="mt-1 text-xs text-text-secondary">
                SOP yang aktif menunjukkan prosedur standar yang saat ini berlaku untuk kontrol keamanan informasi.
              </p>
            </div>
          </div>
        </Card>
      )}
    </div>
  );
}
