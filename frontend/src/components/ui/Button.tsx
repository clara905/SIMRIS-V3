import React from 'react';
import { Loader2 } from 'lucide-react';
import { clsx } from '@/utils/clsx';

// ─── Types ───────────────────────────────────────────────────────────────────

export type ButtonVariant = 'primary' | 'secondary' | 'outline' | 'ghost' | 'danger';
export type ButtonSize    = 'xs' | 'sm' | 'md' | 'lg';

export interface ButtonProps extends React.ButtonHTMLAttributes<HTMLButtonElement> {
  variant?: ButtonVariant;
  size?: ButtonSize;
  loading?: boolean;
  leftIcon?: React.ReactNode;
  rightIcon?: React.ReactNode;
  /** Renders as a full-width block */
  block?: boolean;
}

// ─── Style maps ──────────────────────────────────────────────────────────────

const base = [
  'inline-flex items-center justify-center',
  'rounded-md border font-medium',
  'transition-colors duration-fast',
  'focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-brand/40 focus-visible:ring-offset-1',
  'disabled:pointer-events-none disabled:opacity-50 disabled:cursor-not-allowed',
  'select-none',
].join(' ');

const variants: Record<ButtonVariant, string> = {
  /**
   * Primary — muted burgundy fill. Used for the most important action on a page.
   * Keep to one per section.
   */
  primary: [
    'bg-brand border-brand text-white',
    'hover:bg-brand-dark hover:border-brand-dark',
    'active:bg-brand-dark',
  ].join(' '),

  /**
   * Secondary — light gray fill. The safe default for supporting actions.
   */
  secondary: [
    'bg-surface-muted border-border text-text-primary',
    'hover:bg-border hover:border-border-strong',
    'active:bg-border',
  ].join(' '),

  /**
   * Outline — white fill with brand border. Pairs with primary buttons.
   */
  outline: [
    'bg-white border-brand text-brand',
    'hover:bg-brand-subtle hover:border-brand-dark',
    'active:bg-brand-muted',
  ].join(' '),

  /**
   * Ghost — no border, no background. For icon actions and low-emphasis controls.
   */
  ghost: [
    'bg-transparent border-transparent text-text-secondary',
    'hover:bg-surface-muted hover:text-text-primary',
    'active:bg-border',
  ].join(' '),

  /**
   * Danger — red fill. For destructive actions (delete, deactivate).
   * Always pair with a confirmation dialog.
   */
  danger: [
    'bg-status-error border-status-error text-white',
    'hover:bg-red-700 hover:border-red-700',
    'active:bg-red-800',
  ].join(' '),
};

const sizes: Record<ButtonSize, string> = {
  xs: 'h-7  px-2.5 text-xs  gap-1',
  sm: 'h-8  px-3   text-sm  gap-1.5',
  md: 'h-9  px-3.5 text-sm  gap-1.5',
  lg: 'h-10 px-4   text-sm  gap-2',
};

// ─── Component ───────────────────────────────────────────────────────────────

export function Button({
  variant = 'primary',
  size    = 'md',
  loading = false,
  leftIcon,
  rightIcon,
  disabled,
  block = false,
  className,
  children,
  ...props
}: ButtonProps) {
  return (
    <button
      {...props}
      disabled={disabled || loading}
      className={clsx(
        base,
        variants[variant],
        sizes[size],
        block && 'w-full',
        className,
      )}
    >
      {loading ? (
        <Loader2 className="h-3.5 w-3.5 animate-spin shrink-0" />
      ) : (
        leftIcon && <span className="shrink-0 flex items-center">{leftIcon}</span>
      )}
      {children && <span>{children}</span>}
      {!loading && rightIcon && (
        <span className="shrink-0 flex items-center">{rightIcon}</span>
      )}
    </button>
  );
}
