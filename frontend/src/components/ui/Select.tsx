import React, { forwardRef } from 'react';
import { ChevronDown } from 'lucide-react';
import { clsx } from '@/utils/clsx';
import { inputBase, inputError, inputNormal } from './Input';

// ─── Types ───────────────────────────────────────────────────────────────────

export interface SelectOption {
  value: string | number;
  label: string;
  disabled?: boolean;
}

export interface SelectProps extends React.SelectHTMLAttributes<HTMLSelectElement> {
  label?:       string;
  error?:       string;
  hint?:        string;
  options:      SelectOption[];
  placeholder?: string;
}

// ─── Component ───────────────────────────────────────────────────────────────

export const Select = forwardRef<HTMLSelectElement, SelectProps>(
  ({ label, error, hint, options, placeholder, className, id, required, ...props }, ref) => {
    const selectId = id ?? label?.toLowerCase().replace(/\s+/g, '-');

    return (
      <div className="form-group">
        {label && (
          <label htmlFor={selectId} className="form-label">
            {label}
            {required && <span className="form-required">*</span>}
          </label>
        )}

        <div className="relative">
          <select
            ref={ref}
            id={selectId}
            required={required}
            {...props}
            className={clsx(
              inputBase,
              error ? inputError : inputNormal,
              'appearance-none pl-3 pr-9 cursor-pointer',
              className,
            )}
          >
            {placeholder && (
              <option value="" disabled>
                {placeholder}
              </option>
            )}
            {options.map((opt) => (
              <option key={opt.value} value={opt.value} disabled={opt.disabled}>
                {opt.label}
              </option>
            ))}
          </select>

          <ChevronDown
            className="pointer-events-none absolute right-3 top-1/2 h-3.5 w-3.5
                       -translate-y-1/2 text-text-muted"
          />
        </div>

        {error && <p className="form-error">{error}</p>}
        {!error && hint && <p className="form-hint">{hint}</p>}
      </div>
    );
  },
);

Select.displayName = 'Select';
