import { Loader2 } from 'lucide-react';
import { clsx } from '@/utils/clsx';

// ─── Types ───────────────────────────────────────────────────────────────────

export interface LoadingSpinnerProps {
  size?:      'xs' | 'sm' | 'md' | 'lg';
  label?:     string;
  className?: string;
}

// ─── Spinner ─────────────────────────────────────────────────────────────────

export function LoadingSpinner({
  size      = 'md',
  label     = 'Memuat...',
  className,
}: LoadingSpinnerProps) {
  const spinSize = {
    xs: 'h-3 w-3',
    sm: 'h-4 w-4',
    md: 'h-5 w-5',
    lg: 'h-7 w-7',
  }[size];

  return (
    <div
      className={clsx(
        'flex items-center justify-center gap-2 text-text-secondary',
        className,
      )}
      role="status"
      aria-label={label}
    >
      <Loader2 className={clsx('animate-spin text-brand', spinSize)} />
      {size !== 'xs' && size !== 'sm' && (
        <span className="text-sm">{label}</span>
      )}
    </div>
  );
}

// ─── Page loader (centered in content area) ───────────────────────────────────

export function PageLoader({ label }: { label?: string }) {
  return (
    <div className="flex min-h-[16rem] w-full items-center justify-center">
      <LoadingSpinner size="lg" label={label} />
    </div>
  );
}

// ─── Inline loader (inside buttons, cells, etc.) ──────────────────────────────

export function InlineLoader({ className }: { className?: string }) {
  return (
    <Loader2
      className={clsx('h-4 w-4 animate-spin text-text-muted', className)}
      aria-hidden="true"
    />
  );
}
