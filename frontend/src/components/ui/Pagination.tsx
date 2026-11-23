import { ChevronLeft, ChevronRight } from 'lucide-react';
import { clsx } from '@/utils/clsx';

// ─── Types ───────────────────────────────────────────────────────────────────
//
// Accepts both camelCase (component API) and snake_case (Laravel meta spread).

export interface PaginationProps {
  currentPage?:  number;
  current_page?: number;
  lastPage?:     number;
  last_page?:    number;
  total:         number;
  perPage?:      number;
  per_page?:     number;
  from:          number | null;
  to:            number | null;
  onPageChange:  (page: number) => void;
}

// ─── Helpers ─────────────────────────────────────────────────────────────────

function buildPageList(current: number, last: number): (number | '…')[] {
  if (last <= 7) {
    return Array.from({ length: last }, (_, i) => i + 1);
  }
  const pages: (number | '…')[] = [1];
  if (current > 3) pages.push('…');
  for (let i = Math.max(2, current - 1); i <= Math.min(last - 1, current + 1); i++) {
    pages.push(i);
  }
  if (current < last - 2) pages.push('…');
  pages.push(last);
  return pages;
}

// ─── Component ───────────────────────────────────────────────────────────────

export function Pagination({
  currentPage,
  current_page,
  lastPage,
  last_page,
  total,
  from,
  to,
  onPageChange,
}: PaginationProps) {
  const page  = currentPage ?? current_page ?? 1;
  const last  = lastPage    ?? last_page    ?? 1;

  if (last <= 1) return null;

  const pages = buildPageList(page, last);

  const btnBase = [
    'flex h-7 min-w-[1.75rem] items-center justify-center',
    'rounded-md border text-xs font-medium tabular-nums',
    'transition-colors duration-fast',
  ].join(' ');

  return (
    <div className="flex flex-wrap items-center justify-between gap-3">
      {/* Count */}
      <p className="text-xs text-text-secondary">
        {from !== null && to !== null ? (
          <>
            <span className="font-medium text-text-primary">{from}</span>
            {' – '}
            <span className="font-medium text-text-primary">{to}</span>
            {' dari '}
            <span className="font-medium text-text-primary">
              {total.toLocaleString('id-ID')}
            </span>
          </>
        ) : (
          <>
            Total{' '}
            <span className="font-medium text-text-primary">
              {total.toLocaleString('id-ID')}
            </span>
          </>
        )}
      </p>

      {/* Controls */}
      <div className="flex items-center gap-1">
        {/* Previous */}
        <button
          onClick={() => onPageChange(page - 1)}
          disabled={page === 1}
          className={clsx(
            btnBase,
            'px-1.5 border-border text-text-secondary',
            'hover:bg-surface-muted hover:text-text-primary',
            'disabled:pointer-events-none disabled:opacity-40',
          )}
          aria-label="Halaman sebelumnya"
        >
          <ChevronLeft className="h-3.5 w-3.5" />
        </button>

        {/* Page numbers */}
        {pages.map((p, i) =>
          p === '…' ? (
            <span
              key={`ellipsis-${i}`}
              className="flex h-7 w-6 items-center justify-center text-xs text-text-muted"
            >
              …
            </span>
          ) : (
            <button
              key={p}
              onClick={() => onPageChange(p as number)}
              className={clsx(
                btnBase,
                'px-2',
                p === page
                  ? 'border-brand bg-brand text-white'
                  : 'border-border text-text-secondary hover:bg-surface-muted hover:text-text-primary',
              )}
              aria-current={p === page ? 'page' : undefined}
            >
              {p}
            </button>
          ),
        )}

        {/* Next */}
        <button
          onClick={() => onPageChange(page + 1)}
          disabled={page === last}
          className={clsx(
            btnBase,
            'px-1.5 border-border text-text-secondary',
            'hover:bg-surface-muted hover:text-text-primary',
            'disabled:pointer-events-none disabled:opacity-40',
          )}
          aria-label="Halaman berikutnya"
        >
          <ChevronRight className="h-3.5 w-3.5" />
        </button>
      </div>
    </div>
  );
}
