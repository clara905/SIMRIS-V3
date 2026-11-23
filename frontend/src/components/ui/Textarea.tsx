import React, { forwardRef } from 'react';
import { clsx } from '@/utils/clsx';
import { inputError, inputNormal } from './Input';

// ─── Types ───────────────────────────────────────────────────────────────────

export interface TextareaProps extends React.TextareaHTMLAttributes<HTMLTextAreaElement> {
  label?:   string;
  error?:   string;
  hint?:    string;
}

// ─── Component ───────────────────────────────────────────────────────────────

export const Textarea = forwardRef<HTMLTextAreaElement, TextareaProps>(
  ({ label, error, hint, className, id, required, ...props }, ref) => {
    const textareaId = id ?? label?.toLowerCase().replace(/\s+/g, '-');

    return (
      <div className="form-group">
        {label && (
          <label htmlFor={textareaId} className="form-label">
            {label}
            {required && <span className="form-required">*</span>}
          </label>
        )}

        <textarea
          ref={ref}
          id={textareaId}
          required={required}
          rows={props.rows ?? 3}
          {...props}
          className={clsx(
            // Reuse inputBase but swap py-2 for py-2 (textareas need px-3)
            'w-full rounded-md border bg-white px-3 py-2 text-sm text-text-primary',
            'placeholder:text-text-muted resize-y',
            'transition-colors duration-fast',
            'focus:outline-none focus:ring-2 focus:ring-brand/20 focus:border-brand',
            'hover:border-border-strong',
            'disabled:cursor-not-allowed disabled:bg-surface-muted disabled:opacity-60',
            error ? inputError : inputNormal,
            className,
          )}
        />

        {error && <p className="form-error">{error}</p>}
        {!error && hint && <p className="form-hint">{hint}</p>}
      </div>
    );
  },
);

Textarea.displayName = 'Textarea';
