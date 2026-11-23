import React from 'react';
import { ShieldCheck } from 'lucide-react';

interface AuthLayoutProps {
  children: React.ReactNode;
}

export function AuthLayout({ children }: AuthLayoutProps) {
  return (
    <div className="flex min-h-screen bg-surface-muted">
      {/* Left panel — branding */}
      <div className="hidden lg:flex lg:w-5/12 lg:flex-col lg:justify-between bg-brand-dark px-12 py-10">
        <div className="flex items-center gap-3">
          <div className="flex h-9 w-9 items-center justify-center rounded-lg bg-white/10">
            <ShieldCheck className="h-5 w-5 text-white" />
          </div>
          <div>
            <p className="text-sm font-bold tracking-tight text-white">SIMRIS V3</p>
            <p className="text-[10px] uppercase tracking-widest text-white/60">Risk Management</p>
          </div>
        </div>

        <div className="space-y-5">
          <div>
            <h2 className="text-3xl font-bold leading-tight text-white">
              Kelola Risiko<br />Informasi Organisasi
            </h2>
            <p className="mt-3 text-sm leading-relaxed text-white/70">
              Platform manajemen risiko keamanan informasi terintegrasi
              untuk identifikasi, penilaian, dan penanganan risiko aset
              secara sistematis.
            </p>
          </div>

          <div className="grid grid-cols-2 gap-3">
            {[
              { label: 'Penilaian Risiko', desc: 'Berbasis ISO 27005' },
              { label: 'Manajemen Aset', desc: 'Inventaris terpusat' },
              { label: 'Rencana Penanganan', desc: 'Monitoring progres' },
              { label: 'Laporan Komprehensif', desc: 'Data & visualisasi' },
            ].map((f) => (
              <div key={f.label} className="rounded-lg bg-white/10 p-3">
                <p className="text-xs font-semibold text-white">{f.label}</p>
                <p className="text-[11px] text-white/60">{f.desc}</p>
              </div>
            ))}
          </div>
        </div>

        <p className="text-xs text-white/40">
          © 2026 SIMRIS V3. Seluruh hak dilindungi.
        </p>
      </div>

      {/* Right panel — form */}
      <div className="flex flex-1 flex-col items-center justify-center px-6 py-12">
        {/* Mobile logo */}
        <div className="mb-8 flex items-center gap-2 lg:hidden">
          <div className="flex h-8 w-8 items-center justify-center rounded-lg bg-brand">
            <ShieldCheck className="h-4 w-4 text-white" />
          </div>
          <span className="text-base font-bold text-text-primary">SIMRIS V3</span>
        </div>

        <div className="w-full max-w-sm">{children}</div>
      </div>
    </div>
  );
}
