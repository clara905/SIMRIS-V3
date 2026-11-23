import React, { forwardRef } from 'react';
import { clsx } from '@/utils/clsx';

// ─── Checkbox ────────────────────────────────────────────────────────────────

export interface CheckboxProps extends React.InputHTMLAttributes<HTMLInputElement> {
  label?:       string;
  description?: string;
  error?:       string;
}

export const Checkbox = forwardRef<HTMLInputElement, CheckboxProps>(
  ({ label, description, error, className, id, ...props }, ref) => {
    const checkId = id ?? label?.toLowerCase().replace(/\s+/g, '-');

    return (
      <div className="flex items-start gap-2.5">
        <input
          ref={ref}
          type="checkbox"
          id={checkId}
          {...props}
          className={clsx(
            'mt-0.5 h-4 w-4 shrink-0 cursor-pointer rounded-sm',
            'border-border-strong bg-white',
            'accent-brand',
            'focus-visible:ring-2 focus-visible:ring-brand/40 focus-visible:ring-offset-1',
            'disabled:cursor-not-allowed disabled:opacity-50',
            error && 'border-status-error',
            className,
          )}
        />
        {(label || description) && (
          <div className="min-w-0">
            {label && (
              <label
                htmlFor={checkId}
                className="block text-sm font-medium text-text-primary cursor-pointer"
              >
                {label}
              </label>
            )}
            {description && (
              <p className="text-xs text-text-muted">{description}</p>
            )}
            {error && <p className="form-error mt-0.5">{error}</p>}
          </div>
        )}
      </div>
    );
  },
);

Checkbox.displayName = 'Checkbox';

// ─── Radio ───────────────────────────────────────────────────────────────────

export interface RadioProps extends React.InputHTMLAttributes<HTMLInputElement> {
  label?:       string;
  description?: string;
}

export const Radio = forwardRef<HTMLInputElement, RadioProps>(
  ({ label, description, className, id, ...props }, ref) => {
    const radioId = id ?? label?.toLowerCase().replace(/\s+/g, '-');

    return (
      <div className="flex items-start gap-2.5">
        <input
          ref={ref}
          type="radio"
          id={radioId}
          {...props}
          className={clsx(
            'mt-0.5 h-4 w-4 shrink-0 cursor-pointer',
            'border-border-strong bg-white',
            'accent-brand',
            'focus-visible:ring-2 focus-visible:ring-brand/40 focus-visible:ring-offset-1',
            'disabled:cursor-not-allowed disabled:opacity-50',
            className,
          )}
        />
        {(label || description) && (
          <div className="min-w-0">
            {label && (
              <label
                htmlFor={radioId}
                className="block text-sm font-medium text-text-primary cursor-pointer"
              >
                {label}
              </label>
            )}
            {description && (
              <p className="text-xs text-text-muted">{description}</p>
            )}
          </div>
        )}
      </div>
    );
  },
);

Radio.displayName = 'Radio';
