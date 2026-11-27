import React, { Component, ReactNode } from 'react';
import { AlertTriangle } from 'lucide-react';

interface ErrorBoundaryProps {
  children: ReactNode;
  fallback?: ReactNode;
}

interface ErrorBoundaryState {
  hasError: boolean;
  error: Error | null;
  errorInfo: React.ErrorInfo | null;
}

/**
 * Error Boundary Component
 * 
 * Catches uncaught React rendering errors and displays a fallback UI
 * instead of leaving a blank white screen.
 * 
 * This component is intentionally independent of:
 * - AuthContext (to catch auth provider errors)
 * - Router (to catch routing errors)
 * - Any service (to avoid circular dependencies)
 * 
 * Phase 8.1.2.3
 */
export class ErrorBoundary extends Component<ErrorBoundaryProps, ErrorBoundaryState> {
  constructor(props: ErrorBoundaryProps) {
    super(props);
    this.state = {
      hasError: false,
      error: null,
      errorInfo: null,
    };
  }

  static getDerivedStateFromError(error: Error): Partial<ErrorBoundaryState> {
    // Update state so the next render will show the fallback UI
    return {
      hasError: true,
      error,
    };
  }

  componentDidCatch(error: Error, errorInfo: React.ErrorInfo): void {
    // Log error details for debugging
    console.error('[ErrorBoundary] Caught rendering error:', error);
    console.error('[ErrorBoundary] Component stack:', errorInfo.componentStack);
    
    // Update state with error info
    this.setState({
      errorInfo,
    });
  }

  handleReload = (): void => {
    window.location.reload();
  };

  handleReset = (): void => {
    this.setState({
      hasError: false,
      error: null,
      errorInfo: null,
    });
  };

  render(): ReactNode {
    if (this.state.hasError) {
      // Use custom fallback if provided
      if (this.props.fallback) {
        return this.props.fallback;
      }

      // Default fallback UI
      return (
        <div className="flex min-h-screen items-center justify-center bg-surface-muted p-6">
          <div className="w-full max-w-md rounded-lg border border-border bg-white p-6 shadow-card">
            {/* Error Icon */}
            <div className="mx-auto flex h-12 w-12 items-center justify-center rounded-full bg-red-100">
              <AlertTriangle className="h-6 w-6 text-status-error" />
            </div>

            {/* Error Message */}
            <div className="mt-4 text-center">
              <h2 className="text-lg font-semibold text-text-primary">
                Terjadi Kesalahan
              </h2>
              <p className="mt-2 text-sm text-text-secondary">
                Aplikasi mengalami kesalahan yang tidak terduga. Silakan muat ulang halaman atau hubungi administrator jika masalah berlanjut.
              </p>
            </div>

            {/* Technical Details (Collapsible) */}
            {this.state.error && (
              <details className="mt-4">
                <summary className="cursor-pointer text-xs font-medium text-text-muted hover:text-text-primary">
                  Detail Teknis
                </summary>
                <div className="mt-2 space-y-2">
                  <div className="rounded bg-surface-muted p-3">
                    <p className="text-xs font-semibold text-text-primary">Error:</p>
                    <pre className="mt-1 overflow-auto text-xs text-status-error">
                      {this.state.error.toString()}
                    </pre>
                  </div>
                  {this.state.errorInfo && (
                    <div className="rounded bg-surface-muted p-3">
                      <p className="text-xs font-semibold text-text-primary">Component Stack:</p>
                      <pre className="mt-1 overflow-auto text-xs text-text-secondary">
                        {this.state.errorInfo.componentStack}
                      </pre>
                    </div>
                  )}
                </div>
              </details>
            )}

            {/* Action Buttons */}
            <div className="mt-6 space-y-2">
              <button
                onClick={this.handleReload}
                className="w-full rounded-md bg-brand px-4 py-2.5 text-sm font-medium text-white transition-colors hover:bg-brand-dark focus:outline-none focus:ring-2 focus:ring-brand focus:ring-offset-2"
              >
                Muat Ulang Halaman
              </button>
              <button
                onClick={this.handleReset}
                className="w-full rounded-md border border-border bg-white px-4 py-2.5 text-sm font-medium text-text-primary transition-colors hover:bg-surface-muted focus:outline-none focus:ring-2 focus:ring-brand focus:ring-offset-2"
              >
                Coba Lagi (Tanpa Reload)
              </button>
            </div>

            {/* Footer */}
            <p className="mt-4 text-center text-xs text-text-muted">
              SIMRIS V3 — Sistem Manajemen Risiko
            </p>
          </div>
        </div>
      );
    }

    // No error, render children normally
    return this.props.children;
  }
}
