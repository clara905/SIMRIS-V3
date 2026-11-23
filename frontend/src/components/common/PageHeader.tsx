import React from 'react';
import { clsx } from '@/utils/clsx';

// ─── Types ───────────────────────────────────────────────────────────────────

export interface PageHeaderProps {
  title:        string;
  description?: string;
  breadcrumb?:  React.ReactNode;
  action?:      React.ReactNode;
  tabs?:        React.ReactNode;
  className?:   string;
}

// ─── Component ───────────────────────────────────────────────────────────────

export function PageHeader({
  title,
  description,
  breadcrumb,
  action,
  tabs,
  className,
}: PageHeaderProps) {
  return (
    <div className={clsx('mb-5', className)}>
      {/* Breadcrumb */}
      {breadcrumb && (
        <div className="mb-2 flex items-center gap-1 text-breadcrumb">
          {breadcrumb}
        </div>
      )}

      {/* Title row */}
      <div className="flex flex-wrap items-start justify-between gap-3">
        <div className="min-w-0">
          <h1 className="text-page-title">{title}</h1>
          {description && (
            <p className="mt-1 text-sm text-text-secondary">{description}</p>
          )}
        </div>

        {action && (
          <div className="flex shrink-0 items-center gap-2">{action}</div>
        )}
      </div>

      {/* Tabs (optional — renders below title) */}
      {tabs && <div className="mt-4">{tabs}</div>}
    </div>
  );
}

// ─── Breadcrumb helpers ───────────────────────────────────────────────────────

import { ChevronRight } from 'lucide-react';
import { Link } from 'react-router-dom';

export interface BreadcrumbItem {
  label: string;
  to?:   string;
}

export function Breadcrumb({ items }: { items: BreadcrumbItem[] }) {
  return (
    <nav aria-label="Breadcrumb">
      <ol className="flex flex-wrap items-center gap-1">
        {items.map((item, i) => {
          const isLast = i === items.length - 1;
          return (
            <li key={item.label} className="flex items-center gap-1">
              {i > 0 && (
                <ChevronRight
                  className="h-3 w-3 shrink-0 text-text-muted"
                  aria-hidden="true"
                />
              )}
              {isLast || !item.to ? (
                <span
                  className={clsx(
                    'text-sm',
                    isLast
                      ? 'font-medium text-text-primary'
                      : 'text-text-secondary hover:text-text-primary cursor-pointer transition-colors',
                  )}
                  aria-current={isLast ? 'page' : undefined}
                >
                  {item.label}
                </span>
              ) : (
                <Link
                  to={item.to}
                  className="text-sm text-text-secondary hover:text-text-primary transition-colors"
                >
                  {item.label}
                </Link>
              )}
            </li>
          );
        })}
      </ol>
    </nav>
  );
}
