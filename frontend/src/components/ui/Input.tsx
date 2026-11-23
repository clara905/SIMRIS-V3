import React, { forwardRef } from 'react';
import { clsx } from '@/utils/clsx';

// ─── Types ───────────────────────────────────────────────────────────────────

export interface InputProps extends React.InputHTMLAttributes<HTMLInputElement> {
  label?:        string;
  error?:        string;
  hint?:         string;
  leftIcon?:     React.ReactNode;
  rightElement?: React.ReactNode;
}

// ─── Shared input field class ─────────────────────────────────────────────────

export const inputBase = [
  'w-full rounded-md border bg-white py-2 text-sm text-text-primary',
  'placeholder:text-text-muted',
  'transition-colors duration-fast',
  'focus:outline-none focus:ring-2 focus:ring-brand/20 focus:border-brand',
  'hover:border-border-strong',
  'disabled:cursor-not-allowed disabled:bg-surface-muted disabled:opacity-60',
].join(' ');

export const inputError = 'border-status-error focus:ring-status-error/20 focus:border-status-error';
export const inputNormal = 'border-border';

// ─── Component ───────────────────────────────────────────────────────────────

export const Input = forwardRef<HTMLInputElement, InputProps>(
  ({ label, error, hint, leftIcon, rightElement, className, id, required, ...props }, ref) => {
    const inputId = id ?? label?.toLowerCase().replace(/\s+/g, '-');

    return (
      <div className="form-group">
        {label && (
          <label htmlFor={inputId} className="form-label">
            {label}
            {required && <span className="form-required">*</span>}
          </label>
        )}

        <div className="relative flex items-center">
          {leftIcon && (
            <span className="pointer-events-none absolute left-3 flex items-center text-text-muted">
              {leftIcon}
            </span>
          )}

          <input
            ref={ref}
            id={inputId}
            required={required}
            {...props}
            className={clsx(
              inputBase,
              error ? inputError : inputNormal,
              leftIcon    ? 'pl-9'  : 'pl-3',
              rightElement ? 'pr-10' : 'pr-3',
              className,
            )}
          />

          {rightElement && (
            <span className="absolute right-3 flex items-center">
              {rightElement}
            </span>
          )}
        </div>

        {error && <p className="form-error">{error}</p>}
        {!error && hint && <p className="form-hint">{hint}</p>}
      </div>
    );
  },
);

Input.displayName = 'Input';
