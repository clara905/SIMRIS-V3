import { AlertTriangle, Info } from 'lucide-react';
import { clsx } from '@/utils/clsx';
import { Button } from './Button';

// ─── Types ───────────────────────────────────────────────────────────────────

export type ConfirmDialogIntent = 'danger' | 'warning' | 'info';

export interface ConfirmDialogProps {
  open:           boolean;
  title:          string;
  message:        string;
  confirmLabel?:  string;
  cancelLabel?:   string;
  intent?:        ConfirmDialogIntent;
  loading?:       boolean;
  onConfirm:      () => void;
  onCancel:       () => void;
}

// ─── Style map ───────────────────────────────────────────────────────────────

const intentConfig: Record<ConfirmDialogIntent, {
  icon:       typeof AlertTriangle;
  iconBg:     string;
  iconColor:  string;
  btnVariant: 'danger' | 'primary';
}> = {
  danger: {
    icon:      AlertTriangle,
    iconBg:    'bg-red-50',
    iconColor: 'text-red-700',
    btnVariant: 'danger',
  },
  warning: {
    icon:      AlertTriangle,
    iconBg:    'bg-amber-50',
    iconColor: 'text-amber-700',
    btnVariant: 'primary',
  },
  info: {
    icon:      Info,
    iconBg:    'bg-blue-50',
    iconColor: 'text-blue-700',
    btnVariant: 'primary',
  },
};

// ─── Component ───────────────────────────────────────────────────────────────

export function ConfirmDialog({
  open,
  title,
  message,
  confirmLabel = 'Konfirmasi',
  cancelLabel  = 'Batal',
  intent       = 'danger',
  loading      = false,
  onConfirm,
  onCancel,
}: ConfirmDialogProps) {
  if (!open) return null;

  const { icon: Icon, iconBg, iconColor, btnVariant } = intentConfig[intent];

  return (
    <div
      className="fixed inset-0 z-50 flex items-center justify-center p-4"
      role="alertdialog"
      aria-modal="true"
      aria-labelledby="confirm-title"
      aria-describedby="confirm-message"
    >
      {/* Backdrop */}
      <div
        className="absolute inset-0 bg-black/40 backdrop-blur-[1px]"
        onClick={loading ? undefined : onCancel}
        aria-hidden="true"
      />

      {/* Panel */}
      <div className="relative w-full max-w-sm rounded-lg border border-border bg-white shadow-dropdown">
        <div className="p-5">
          <div
            className={clsx(
              'mb-4 flex h-9 w-9 items-center justify-center rounded-full',
              iconBg,
            )}
          >
            <Icon className={clsx('h-4 w-4', iconColor)} aria-hidden="true" />
          </div>

          <h3 id="confirm-title" className="text-base font-semibold text-text-primary">
            {title}
          </h3>
          <p id="confirm-message" className="mt-1.5 text-sm text-text-secondary">
            {message}
          </p>
        </div>

        <div className="flex justify-end gap-2 border-t border-border px-5 py-3.5">
          <Button
            variant="secondary"
            size="sm"
            onClick={onCancel}
            disabled={loading}
          >
            {cancelLabel}
          </Button>
          <Button
            variant={btnVariant}
            size="sm"
            onClick={onConfirm}
            loading={loading}
          >
            {confirmLabel}
          </Button>
        </div>
      </div>
    </div>
  );
}
