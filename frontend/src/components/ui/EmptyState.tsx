import type { LucideIcon } from 'lucide-react';
import { Inbox } from 'lucide-react';
import { clsx } from '@/utils/clsx';
import { Button } from './Button';

// ─── Types ───────────────────────────────────────────────────────────────────

export interface EmptyStateProps {
  icon?:        LucideIcon;
  title:        string;
  description?: string;
  action?:      { label: string; onClick: () => void };
  className?:   string;
  /** Compact version for use inside table rows */
  compact?:     boolean;
}

// ─── Component ───────────────────────────────────────────────────────────────

export function EmptyState({
  icon: Icon = Inbox,
  title,
  description,
  action,
  className,
  compact = false,
}: EmptyStateProps) {
  return (
    <div
      className={clsx(
        'flex flex-col items-center justify-center text-center',
        compact ? 'gap-2 py-10' : 'gap-3 py-16',
        className,
      )}
    >
      <div
        className={clsx(
          'flex items-center justify-center rounded-full bg-surface-muted',
          compact ? 'h-10 w-10' : 'h-12 w-12',
        )}
      >
        <Icon
          className={clsx(
            'text-text-muted',
            compact ? 'h-5 w-5' : 'h-6 w-6',
          )}
          aria-hidden="true"
        />
      </div>

      <div className="max-w-xs">
        <p className="text-sm font-medium text-text-primary">{title}</p>
        {description && (
          <p className="mt-1 text-sm text-text-secondary">{description}</p>
        )}
      </div>

      {action && (
        <Button variant="primary" size="sm" onClick={action.onClick}>
          {action.label}
        </Button>
      )}
    </div>
  );
}
