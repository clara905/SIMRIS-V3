import type { LucideIcon } from 'lucide-react';
import { clsx } from '@/utils/clsx';

// ─── Types ───────────────────────────────────────────────────────────────────

export type StatCardVariant = 'default' | 'critical' | 'warning' | 'success' | 'info';

export interface StatCardProps {
  label:      string;
  value:      string | number;
  icon:       LucideIcon;
  supporting?: string;
  trend?:     { value: number; label: string };
  variant?:   StatCardVariant;
  className?: string;
}

// ─── Style maps ──────────────────────────────────────────────────────────────

const config: Record<StatCardVariant, { icon: string; accent: string }> = {
  default:  { icon: 'bg-brand-muted  text-brand',          accent: '' },
  critical: { icon: 'bg-red-50      text-red-700',         accent: 'border-l-[3px] border-l-red-600' },
  warning:  { icon: 'bg-amber-50    text-amber-700',       accent: 'border-l-[3px] border-l-amber-500' },
  success:  { icon: 'bg-green-50    text-green-700',       accent: 'border-l-[3px] border-l-green-600' },
  info:     { icon: 'bg-blue-50     text-blue-700',        accent: 'border-l-[3px] border-l-blue-600' },
};

// ─── Component ───────────────────────────────────────────────────────────────

export function StatCard({
  label,
  value,
  icon: Icon,
  supporting,
  trend,
  variant = 'default',
  className,
}: StatCardProps) {
  // Defensive: fallback to 'default' if variant is not in config
  const selectedVariant = variant in config ? variant : 'default';
  const { icon: iconClass, accent } = config[selectedVariant];
  
  // Log warning in development if invalid variant is used
  if (selectedVariant !== variant && process.env.NODE_ENV === 'development') {
    console.warn(`[StatCard] Invalid variant "${variant}" provided. Falling back to "default".`);
  }

  return (
    <div
      className={clsx(
        'rounded-lg border border-border bg-white p-5 shadow-card',
        accent,
        className,
      )}
    >
      <div className="flex items-start justify-between gap-3">
        <div className="min-w-0 flex-1">
          <p className="text-sm text-text-secondary truncate">{label}</p>
          <p className="mt-1 text-2xl font-semibold tracking-tight text-text-primary tabular-nums">
            {typeof value === 'number' ? value.toLocaleString('id-ID') : value}
          </p>
          {supporting && (
            <p className="mt-0.5 text-xs text-text-muted">{supporting}</p>
          )}
          {trend && (
            <div className="mt-2 flex items-center gap-1">
              <span
                className={clsx(
                  'text-xs font-medium',
                  trend.value > 0 ? 'text-red-600' : 'text-green-600',
                )}
              >
                {trend.value > 0 ? '↑' : '↓'}{' '}
                {Math.abs(trend.value)}%
              </span>
              <span className="text-xs text-text-muted">{trend.label}</span>
            </div>
          )}
        </div>

        <div
          className={clsx(
            'flex h-10 w-10 shrink-0 items-center justify-center rounded-lg',
            iconClass,
          )}
        >
          <Icon className="h-5 w-5" aria-hidden="true" />
        </div>
      </div>
    </div>
  );
}
