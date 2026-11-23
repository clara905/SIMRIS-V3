/**
 * All badge variants available in the design system.
 *
 * Risk levels   : critical | high | medium | low
 * Semantic status: success | warning | error | info
 * Neutral       : neutral | muted
 * Brand         : brand | gold
 */
export type BadgeVariant =
  // ── Risk levels ──────────────────────────────────────────────
  | 'critical'
  | 'high'
  | 'medium'
  | 'low'
  // ── Semantic status ──────────────────────────────────────────
  | 'success'
  | 'warning'
  | 'error'
  | 'info'
  // ── Neutral ──────────────────────────────────────────────────
  | 'neutral'
  | 'muted'
  // ── Brand accents ────────────────────────────────────────────
  | 'brand'
  | 'gold';
