import React, { useState, useRef, useEffect, useCallback } from 'react';
import { clsx } from '@/utils/clsx';

// ─── Types ───────────────────────────────────────────────────────────────────

export interface DropdownItem {
  key:        string;
  label:      string;
  icon?:      React.ReactNode;
  variant?:   'default' | 'danger';
  disabled?:  boolean;
  separator?: boolean;   // render a divider before this item
  onClick?:   () => void;
}

export type DropdownAlign = 'left' | 'right';

export interface DropdownProps {
  /** The trigger element — receives onClick, aria-expanded, and ref */
  trigger:    React.ReactNode;
  items:      DropdownItem[];
  align?:     DropdownAlign;
  className?: string;
  /** Min-width of the menu panel */
  minWidth?:  string;
}

// ─── Component ───────────────────────────────────────────────────────────────

export function Dropdown({
  trigger,
  items,
  align = 'right',
  className,
  minWidth = 'w-44',
}: DropdownProps) {
  const [open, setOpen]       = useState(false);
  const containerRef          = useRef<HTMLDivElement>(null);

  const close = useCallback(() => setOpen(false), []);

  /* Close on outside click */
  useEffect(() => {
    if (!open) return;
    const handler = (e: MouseEvent) => {
      if (containerRef.current && !containerRef.current.contains(e.target as Node)) {
        close();
      }
    };
    document.addEventListener('mousedown', handler);
    return () => document.removeEventListener('mousedown', handler);
  }, [open, close]);

  /* Close on Escape */
  useEffect(() => {
    if (!open) return;
    const handler = (e: KeyboardEvent) => {
      if (e.key === 'Escape') close();
    };
    document.addEventListener('keydown', handler);
    return () => document.removeEventListener('keydown', handler);
  }, [open, close]);

  return (
    <div ref={containerRef} className={clsx('relative inline-flex', className)}>
      {/* Trigger — clone to inject open state */}
      <div
        onClick={() => setOpen((v) => !v)}
        aria-expanded={open}
        aria-haspopup="menu"
        className="cursor-pointer"
      >
        {trigger}
      </div>

      {/* Menu panel */}
      {open && (
        <div
          role="menu"
          className={clsx(
            'absolute top-full z-50 mt-1',
            align === 'right' ? 'right-0' : 'left-0',
            minWidth,
            'rounded-lg border border-border bg-white py-1 shadow-dropdown',
          )}
        >
          {items.map((item) => (
            <React.Fragment key={item.key}>
              {item.separator && (
                <div className="my-1 border-t border-border" role="separator" />
              )}
              <button
                role="menuitem"
                disabled={item.disabled}
                onClick={() => {
                  if (!item.disabled) {
                    item.onClick?.();
                    close();
                  }
                }}
                className={clsx(
                  'flex w-full items-center gap-2.5 px-3.5 py-2',
                  'text-left text-sm transition-colors duration-fast',
                  item.variant === 'danger'
                    ? 'text-status-error hover:bg-red-50'
                    : 'text-text-secondary hover:bg-surface-muted hover:text-text-primary',
                  item.disabled && 'pointer-events-none opacity-40',
                )}
              >
                {item.icon && (
                  <span className="flex h-4 w-4 shrink-0 items-center justify-center">
                    {item.icon}
                  </span>
                )}
                <span>{item.label}</span>
              </button>
            </React.Fragment>
          ))}
        </div>
      )}
    </div>
  );
}
