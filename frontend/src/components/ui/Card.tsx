import React from 'react';
import { clsx } from '@/utils/clsx';

// ─── Types ───────────────────────────────────────────────────────────────────

export type CardPadding = 'none' | 'sm' | 'md' | 'lg';

export interface CardProps {
  children:   React.ReactNode;
  className?: string;
  padding?:   CardPadding;
  /** Renders a left-side brand accent line */
  accent?:    'brand' | 'gold' | 'none';
}

// ─── Style maps ──────────────────────────────────────────────────────────────

const paddingClasses: Record<CardPadding, string> = {
  none: '',
  sm:   'p-4',
  md:   'p-5',
  lg:   'p-6',
};

const accentClasses: Record<'brand' | 'gold' | 'none', string> = {
  none:  '',
  brand: 'border-l-[3px] border-l-brand',
  gold:  'border-l-[3px] border-l-gold',
};

// ─── Card ─────────────────────────────────────────────────────────────────────

export function Card({ children, className, padding = 'md', accent = 'none' }: CardProps) {
  return (
    <div
      className={clsx(
        'rounded-lg border border-border bg-white shadow-card',
        paddingClasses[padding],
        accentClasses[accent],
        className,
      )}
    >
      {children}
    </div>
  );
}

// ─── CardHeader ───────────────────────────────────────────────────────────────

export interface CardHeaderProps {
  title:        string;
  description?: string;
  action?:      React.ReactNode;
  className?:   string;
}

export function CardHeader({ title, description, action, className }: CardHeaderProps) {
  return (
    <div className={clsx('flex items-start justify-between gap-4', className)}>
      <div className="min-w-0">
        <h3 className="text-card-heading">{title}</h3>
        {description && (
          <p className="mt-0.5 text-xs text-text-secondary">{description}</p>
        )}
      </div>
      {action && <div className="shrink-0">{action}</div>}
    </div>
  );
}

// ─── CardSection ──────────────────────────────────────────────────────────────
// Useful for cards that need a visually separated footer or sub-section.

export interface CardSectionProps {
  children:   React.ReactNode;
  className?: string;
}

export function CardSection({ children, className }: CardSectionProps) {
  return (
    <div className={clsx('border-t border-border', className)}>
      {children}
    </div>
  );
}
