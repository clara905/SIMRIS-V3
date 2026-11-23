import React, { useEffect, useRef } from 'react';
import { X } from 'lucide-react';
import { clsx } from '@/utils/clsx';

// ─── Types ───────────────────────────────────────────────────────────────────

export type ModalSize = 'sm' | 'md' | 'lg' | 'xl' | 'full';

export interface ModalProps {
  open:         boolean;
  onClose:      () => void;
  title:        string;
  description?: string;
  children:     React.ReactNode;
  size?:        ModalSize;
  footer?:      React.ReactNode;
  /** Prevent closing on backdrop click */
  persistent?:  boolean;
}

// ─── Style map ───────────────────────────────────────────────────────────────

const sizeClasses: Record<ModalSize, string> = {
  sm:   'max-w-sm',
  md:   'max-w-lg',
  lg:   'max-w-2xl',
  xl:   'max-w-4xl',
  full: 'max-w-[calc(100vw-2rem)]',
};

// ─── Component ───────────────────────────────────────────────────────────────

export function Modal({
  open,
  onClose,
  title,
  description,
  children,
  size = 'md',
  footer,
  persistent = false,
}: ModalProps) {
  const panelRef = useRef<HTMLDivElement>(null);

  /* Keyboard: Escape to close */
  useEffect(() => {
    if (!open) return;
    const handler = (e: KeyboardEvent) => {
      if (e.key === 'Escape' && !persistent) onClose();
    };
    document.addEventListener('keydown', handler);
    return () => document.removeEventListener('keydown', handler);
  }, [open, onClose, persistent]);

  /* Body scroll lock */
  useEffect(() => {
    if (open) {
      document.body.style.overflow = 'hidden';
    } else {
      document.body.style.overflow = '';
    }
    return () => { document.body.style.overflow = ''; };
  }, [open]);

  if (!open) return null;

  return (
    <div
      className="fixed inset-0 z-50 flex items-end sm:items-center justify-center p-4"
      role="dialog"
      aria-modal="true"
      aria-labelledby="modal-title"
    >
      {/* Backdrop */}
      <div
        className="absolute inset-0 bg-black/40 backdrop-blur-[1px]"
        onClick={persistent ? undefined : onClose}
        aria-hidden="true"
      />

      {/* Panel */}
      <div
        ref={panelRef}
        className={clsx(
          'relative w-full rounded-lg border border-border bg-white shadow-dropdown',
          'flex flex-col max-h-[calc(100vh-2rem)]',
          sizeClasses[size],
        )}
      >
        {/* Header */}
        <div className="flex shrink-0 items-start justify-between gap-4 border-b border-border px-5 py-4">
          <div className="min-w-0">
            <h2
              id="modal-title"
              className="text-base font-semibold text-text-primary leading-snug"
            >
              {title}
            </h2>
            {description && (
              <p className="mt-0.5 text-sm text-text-secondary">{description}</p>
            )}
          </div>
          <button
            onClick={onClose}
            className={clsx(
              'ml-2 shrink-0 rounded-md p-1.5 text-text-muted',
              'hover:bg-surface-muted hover:text-text-primary',
              'transition-colors duration-fast',
              'focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-brand/40',
            )}
            aria-label="Tutup dialog"
          >
            <X className="h-4 w-4" />
          </button>
        </div>

        {/* Body */}
        <div className="flex-1 overflow-y-auto px-5 py-4">
          {children}
        </div>

        {/* Footer */}
        {footer && (
          <div className="flex shrink-0 items-center justify-end gap-2 border-t border-border px-5 py-3.5">
            {footer}
          </div>
        )}
      </div>
    </div>
  );
}
