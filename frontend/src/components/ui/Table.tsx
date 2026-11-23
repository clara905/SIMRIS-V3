import React from 'react';
import { clsx } from '@/utils/clsx';

/**
 * Table — GitHub-inspired compact data table.
 *
 * Usage:
 *   <Table>
 *     <TableHead>
 *       <TableRow>
 *         <Th>Name</Th>
 *         <Th align="right">Actions</Th>
 *       </TableRow>
 *     </TableHead>
 *     <TableBody>
 *       {rows.map(r => (
 *         <TableRow key={r.id}>
 *           <Td primary>…</Td>
 *           <Td align="right">…</Td>
 *         </TableRow>
 *       ))}
 *     </TableBody>
 *   </Table>
 *
 * Wrap in <TableContainer> for horizontal scroll on small screens.
 */

// ─── TableContainer ───────────────────────────────────────────────────────────

export function TableContainer({
  children,
  className,
}: {
  children:   React.ReactNode;
  className?: string;
}) {
  return (
    <div className={clsx('table-container', className)}>
      {children}
    </div>
  );
}

// ─── Table ────────────────────────────────────────────────────────────────────

export function Table({
  children,
  className,
}: {
  children:   React.ReactNode;
  className?: string;
}) {
  return (
    <table className={clsx('simris-table', className)}>
      {children}
    </table>
  );
}

// ─── TableHead ────────────────────────────────────────────────────────────────

export function TableHead({
  children,
  className,
}: {
  children:   React.ReactNode;
  className?: string;
}) {
  return <thead className={className}>{children}</thead>;
}

// ─── TableBody ────────────────────────────────────────────────────────────────

export function TableBody({
  children,
  className,
}: {
  children:   React.ReactNode;
  className?: string;
}) {
  return <tbody className={className}>{children}</tbody>;
}

// ─── TableRow ─────────────────────────────────────────────────────────────────

export interface TableRowProps {
  children:    React.ReactNode;
  className?:  string;
  onClick?:    () => void;
  /** Visually mute the row (e.g. inactive records) */
  muted?:      boolean;
}

export function TableRow({
  children,
  className,
  onClick,
  muted = false,
}: TableRowProps) {
  return (
    <tr
      onClick={onClick}
      className={clsx(
        onClick && 'cursor-pointer',
        muted && 'opacity-50',
        className,
      )}
    >
      {children}
    </tr>
  );
}

// ─── Th ──────────────────────────────────────────────────────────────────────

export type Align = 'left' | 'center' | 'right';

export interface ThProps {
  children?:  React.ReactNode;
  align?:     Align;
  width?:     string;
  className?: string;
}

const alignClass: Record<Align, string> = {
  left:   'text-left',
  center: 'text-center',
  right:  'text-right',
};

export function Th({ children, align = 'left', width, className }: ThProps) {
  return (
    <th
      className={clsx('text-table-head', alignClass[align], className)}
      style={width ? { width } : undefined}
    >
      {children}
    </th>
  );
}

// ─── Td ──────────────────────────────────────────────────────────────────────

export interface TdProps {
  children?:  React.ReactNode;
  align?:     Align;
  /** Bold text + primary color. Use for the main identifier column. */
  primary?:   boolean;
  /** Muted text. Use for secondary/meta information. */
  secondary?: boolean;
  width?:     string;
  colSpan?:   number;
  className?: string;
}

export function Td({
  children,
  align = 'left',
  primary = false,
  secondary = false,
  width,
  colSpan,
  className,
}: TdProps) {
  return (
    <td
      colSpan={colSpan}
      className={clsx(
        'text-table-cell',
        alignClass[align],
        primary   && 'font-medium text-text-primary',
        secondary && 'text-text-secondary',
        className,
      )}
      style={width ? { width } : undefined}
    >
      {children}
    </td>
  );
}
