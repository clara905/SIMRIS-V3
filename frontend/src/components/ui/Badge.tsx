import React from 'react';
import { clsx } from '@/utils/clsx';
import type { BadgeVariant } from '@/types/badge.types';

export type { BadgeVariant };

// ─── Types ───────────────────────────────────────────────────────────────────

export type BadgeSize = 'sm' | 'md';

export interface BadgeProps {
  variant?:  BadgeVariant;
  size?:     BadgeSize;
  dot?:      boolean;    // show a colored leading dot
  children:  React.ReactNode;
  className?: string;
}

// ─── Style map ───────────────────────────────────────────────────────────────
//
// Each variant: bg + text + border
// Risk level colors are intentionally distinct from the brand burgundy
// to avoid confusion between "brand" and "danger".
//
// All variants have enough contrast for WCAG AA on white backgrounds.

const variantClasses: Record<BadgeVariant, string> = {
  // Risk levels — these map to risk.* tokens (distinct from brand)
  critical: 'bg-red-50    text-red-800    border-red-200',
  high:     'bg-orange-50 text-orange-800 border-orange-200',
  medium:   'bg-amber-50  text-amber-800  border-amber-200',
  low:      'bg-green-50  text-green-800  border-green-200',

  // Semantic status
  success:  'bg-green-50  text-green-800  border-green-200',
  warning:  'bg-amber-50  text-amber-800  border-amber-200',
  error:    'bg-red-50    text-red-800    border-red-200',
  info:     'bg-blue-50   text-blue-800   border-blue-200',

  // Neutral
  neutral:  'bg-surface-muted text-text-secondary border-border',
  muted:    'bg-white          text-text-muted    border-border',

  // Brand (use sparingly — identifies SIMRIS-specific states)
  brand:    'bg-brand-muted text-brand-dark  border-brand-muted',
  gold:     'bg-gold-muted  text-amber-800   border-amber-200',
};

const dotColors: Record<BadgeVariant, string> = {
  critical: 'bg-red-600',
  high:     'bg-orange-600',
  medium:   'bg-amber-500',
  low:      'bg-green-600',
  success:  'bg-green-600',
  warning:  'bg-amber-500',
  error:    'bg-red-600',
  info:     'bg-blue-600',
  neutral:  'bg-text-secondary',
  muted:    'bg-text-muted',
  brand:    'bg-brand',
  gold:     'bg-gold',
};

const sizeClasses: Record<BadgeSize, string> = {
  sm: 'px-1.5 py-0  text-2xs gap-1',
  md: 'px-2   py-0.5 text-xs gap-1.5',
};

// ─── Component ───────────────────────────────────────────────────────────────

export function Badge({
  variant   = 'neutral',
  size      = 'md',
  dot       = false,
  children,
  className,
}: BadgeProps) {
  return (
    <span
      className={clsx(
        'inline-flex items-center rounded-sm border font-medium leading-5',
        variantClasses[variant],
        sizeClasses[size],
        className,
      )}
    >
      {dot && (
        <span
          className={clsx(
            'shrink-0 rounded-full',
            size === 'sm' ? 'h-1.5 w-1.5' : 'h-2 w-2',
            dotColors[variant],
          )}
          aria-hidden="true"
        />
      )}
      {children}
    </span>
  );
}

// ─── Convenience helpers ──────────────────────────────────────────────────────
//
// Pre-configured badges for the most common SIMRIS concepts.
// Use these instead of repeating variant + label logic in every page.

type RiskLevel = 'critical' | 'high' | 'medium' | 'low';

const RISK_LABELS: Record<RiskLevel, string> = {
  critical: 'Kritis',
  high:     'Tinggi',
  medium:   'Sedang',
  low:      'Rendah',
};

export function RiskBadge({
  level,
  dot,
  size,
  className,
}: {
  level: RiskLevel;
  dot?: boolean;
  size?: BadgeSize;
  className?: string;
}) {
  return (
    <Badge variant={level} dot={dot} size={size} className={className}>
      {RISK_LABELS[level]}
    </Badge>
  );
}

type ActiveStatus = 'aktif' | 'nonaktif';

export function StatusBadge({
  status,
  size,
  className,
}: {
  status: ActiveStatus;
  size?: BadgeSize;
  className?: string;
}) {
  return (
    <Badge
      variant={status === 'aktif' ? 'success' : 'neutral'}
      dot
      size={size}
      className={className}
    >
      {status === 'aktif' ? 'Aktif' : 'Nonaktif'}
    </Badge>
  );
}
