import { Search, X } from 'lucide-react';
import { clsx } from '@/utils/clsx';

// ─── Types ───────────────────────────────────────────────────────────────────

export interface SearchInputProps {
  value:        string;
  onChange:     (value: string) => void;
  placeholder?: string;
  className?:   string;
  disabled?:    boolean;
}

// ─── Component ───────────────────────────────────────────────────────────────

export function SearchInput({
  value,
  onChange,
  placeholder = 'Cari...',
  className,
  disabled,
}: SearchInputProps) {
  return (
    <div className={clsx('relative flex items-center', className)}>
      <Search
        className="pointer-events-none absolute left-3 h-3.5 w-3.5 text-text-muted"
        aria-hidden="true"
      />

      <input
        type="search"
        value={value}
        onChange={(e) => onChange(e.target.value)}
        placeholder={placeholder}
        disabled={disabled}
        className={clsx(
          'h-9 w-full rounded-md border border-border bg-white',
          'pl-8 pr-8 text-sm text-text-primary placeholder:text-text-muted',
          'transition-colors duration-fast',
          'focus:border-brand focus:outline-none focus:ring-2 focus:ring-brand/20',
          'hover:border-border-strong',
          'disabled:cursor-not-allowed disabled:opacity-60',
        )}
        aria-label={placeholder}
      />

      {value && (
        <button
          type="button"
          onClick={() => onChange('')}
          className={clsx(
            'absolute right-2.5 rounded p-0.5',
            'text-text-muted hover:text-text-primary transition-colors',
          )}
          aria-label="Hapus pencarian"
        >
          <X className="h-3.5 w-3.5" />
        </button>
      )}
    </div>
  );
}
