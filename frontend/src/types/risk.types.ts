import type { RiskLevel } from './common.types';

// --- Aset ---
export type AssetCategory =
  | 'Hardware'
  | 'Software'
  | 'Data'
  | 'Jaringan'
  | 'SDM'
  | 'Layanan'
  | 'Fasilitas'
  | 'Lainnya';

export type AssetCondition = 'Baik' | 'Rusak Ringan' | 'Rusak Berat';
export type AssetStatus = 'Aktif' | 'Pemeliharaan' | 'Tidak Aktif';

export type CIALevel = 1 | 2 | 3 | 4 | 5;

export interface Asset {
  id: number;
  kode: string;
  nama: string;
  kategori: AssetCategory;
  pemilik: string;
  lokasi: string;
  deskripsi: string | null;
  nilai_aset: number;
  tanggal_perolehan: string;
  kondisi: AssetCondition;
  status: AssetStatus;
  pic?: string | null;
  kerahasiaan: CIALevel;
  integritas: CIALevel;
  ketersediaan: CIALevel;
  unit_id: number | null;
}

// --- Ancaman ---
export type ThreatCategory =
  | 'Teknis'
  | 'Manusia'
  | 'Lingkungan'
  | 'Organisasi';

export interface Threat {
  id: number;
  kode: string;
  nama: string;
  kategori: ThreatCategory;
  sumber: string;
  deskripsi: string | null;
  status: 'aktif' | 'nonaktif';
}

// --- Kerentanan ---
export interface Vulnerability {
  id: number;
  kode: string;
  nama: string;
  deskripsi: string | null;
  aset_terkait: string | null;
  severitas: RiskLevel;
  status: 'terbuka' | 'dimitigasi' | 'diterima' | 'ditutup';
}

// ─── Risk Assessment (ERD-Compliant) ─────────────────────────────────────────

/**
 * PERIODE_PENILAIAN
 * Assessment period for risk management cycle
 */
export interface PeriodePenilaian {
  id: number;
  nama: string;
  tahun: number;
  tanggal_mulai: string;
  tanggal_selesai: string;
  status: 'draft' | 'active' | 'completed';
}

/**
 * SERI_ASESMEN
 * Assessment series within a period
 */
export interface SeriAsesmen {
  id: number;
  periode_id: number;
  nama: string;
  deskripsi: string | null;
  tanggal_mulai: string;
  tanggal_selesai: string;
}

/**
 * VERSI_METODE_RISIKO
 * Risk assessment methodology version
 */
export interface VersiMetodeRisiko {
  id: number;
  nama: string;
  deskripsi: string | null;
  berlaku_mulai: string;
  berlaku_hingga: string | null;
  is_active: boolean;
}

/**
 * ASESMEN_RISIKO
 * Assessment container - holds multiple RISIKO
 */
export interface AsesmenRisiko {
  id: number;
  seri_asesmen_id: number;
  seri_asesmen_nama?: string;  // For display
  versi_metode_risiko_id: number;
  metode_nama?: string;  // For display
  unit_id: number;
  unit_nama?: string;  // For display
  kode: string;
  nama: string;
  deskripsi: string | null;
  dibuat_oleh: number;
  dibuat_oleh_nama?: string;  // For display
  dibuat_pada: string;
  diperbarui_pada: string;
  status: 'draft' | 'active' | 'completed' | 'archived';
}

/**
 * SUMBER_ANCAMAN
 * Threat source (CRITICAL MISSING ENTITY)
 */
export interface SumberAncaman {
  id: number;
  kode: string;
  nama: string;
  deskripsi: string | null;
  kategori: 'internal' | 'eksternal';
  status: 'aktif' | 'nonaktif';
}

/**
 * RISIKO
 * Individual risk within an assessment
 * Separated from PENILAIAN_RISIKO
 */
export interface Risiko {
  id: number;
  asesmen_id: number;
  asesmen_nama?: string;  // For display
  ancaman_id: number;
  ancaman_nama?: string;  // For display
  sumber_ancaman_id: number;
  sumber_ancaman_nama?: string;  // For display
  kerentanan_id: number;
  kerentanan_nama?: string;  // For display
  pemilik_risiko_id: number;
  pemilik_risiko_nama?: string;  // For display
  dibuat_oleh: number;
  dibuat_oleh_nama?: string;  // For display
  kode: string;
  skenario: string;
  uraian_kerentanan: string | null;
  uraian_dampak: string | null;
  arah_dampak: 'positif' | 'negatif' | 'netral' | null;
  uraian_pelaksanaan_rencana_kerja: string | null;
  uraian_penerapan_sop_kontrol: string | null;
  dibuat_pada: string;
  diperbarui_pada: string;
}

/**
 * RISIKO_ASET
 * Many-to-many relationship with asset snapshots
 */
export interface RisikoAset {
  id: number;
  risiko_id: number;
  aset_id: number;
  // Asset snapshot fields (captured at time of risk identification)
  kode_aset_snapshot: string;
  nama_aset_snapshot: string;
  kategori_snapshot: string;
  klasifikasi_snapshot: string | null;
  unit_pemilik_snapshot: string;
  lokasi_snapshot: string;
  kondisi_snapshot: string;
  direkam_pada: string;
}

/**
 * MATRIKS_RISIKO
 * Risk matrix configuration for a methodology version
 */
export interface MatriksRisiko {
  id: number;
  versi_metode_id: number;
  skala_kemungkinan_min: number;
  skala_kemungkinan_max: number;
  skala_dampak_min: number;
  skala_dampak_max: number;
  formula: string;  // e.g., "kemungkinan * dampak"
}

/**
 * TINGKAT_RISIKO
 * Risk level definition within a matrix
 */
export interface TingkatRisiko {
  id: number;
  matriks_id: number;
  kode: string;
  nama: string;
  warna: string;
  skor_min: number;
  skor_max: number;
  urutan: number;
}

/**
 * PENILAIAN_RISIKO
 * Risk assessment/evaluation (separated from RISIKO)
 */
export interface PenilaianRisiko {
  id: number;
  risiko_id: number;
  matriks_id: number;
  dinilai_oleh: number;
  dinilai_oleh_nama?: string;  // For display
  jenis_penilaian: 'initial' | 'residual' | 'review';
  kemungkinan: number;
  dampak: number;
  skor_risiko: number;
  tingkat_risiko_id: number;
  tingkat_risiko_nama?: string;  // For display
  tingkat_risiko_warna?: string;  // For display
  alasan_kemungkinan: string | null;
  alasan_dampak: string | null;
  kesimpulan: string | null;
  dinilai_pada: string;
}

/**
 * USULAN_KEPUTUSAN
 * Risk decision proposal
 */
export interface UsulanKeputusan {
  id: number;
  risiko_id: number;
  diusulkan_oleh: number;
  strategi: 'mitigasi' | 'hindari' | 'transfer' | 'terima';
  justifikasi: string;
  diusulkan_pada: string;
  status: 'pending' | 'approved' | 'rejected';
}

/**
 * KEPUTUSAN_RISIKO
 * Final risk decision
 */
export interface KeputusanRisiko {
  id: number;
  risiko_id: number;
  usulan_id: number | null;
  diputuskan_oleh: number;
  strategi: 'mitigasi' | 'hindari' | 'transfer' | 'terima';
  justifikasi: string;
  diputuskan_pada: string;
}

/**
 * REVIEW_ASESMEN
 * Assessment review
 */
export interface ReviewAsesmen {
  id: number;
  asesmen_id: number;
  direview_oleh: number;
  hasil_review: 'approved' | 'revision_required' | 'rejected';
  catatan: string | null;
  direview_pada: string;
}

// ─── Risk Treatment (ERD-Compliant) ──────────────────────────────────────────

export type StrategiPenanganan = 'mitigasi' | 'hindari' | 'transfer' | 'terima';
export type StatusPenanganan = 'belum_dimulai' | 'sedang_berjalan' | 'selesai' | 'terlambat';

/**
 * RENCANA_PENANGANAN
 * Treatment plan for identified risk
 */
export interface RencanaPenanganan {
  id: number;
  risiko_id: number;
  risiko_kode?: string;  // For display
  risiko_skenario?: string;  // For display
  kode: string;
  nama: string;
  deskripsi: string | null;
  strategi: StrategiPenanganan;
  penanggung_jawab_id: number;
  penanggung_jawab_nama?: string;  // For display
  tanggal_mulai_rencana: string;
  tanggal_selesai_rencana: string;
  status: StatusPenanganan;
  progres_persen: number;  // 0-100
  dibuat_oleh: number;
  dibuat_oleh_nama?: string;  // For display
  dibuat_pada: string;
  diperbarui_pada: string;
}

/**
 * PROGRES_PENANGANAN
 * Treatment progress record
 */
export interface ProgresPenanganan {
  id: number;
  rencana_penanganan_id: number;
  tanggal_progres: string;
  deskripsi_progres: string;
  progres_persen: number;
  dicatat_oleh: number;
  dicatat_oleh_nama?: string;  // For display
  dicatat_pada: string;
}

/**
 * BUKTI_PENANGANAN
 * Treatment evidence/document
 */
export interface BuktiPenanganan {
  id: number;
  progres_penanganan_id: number | null;
  rencana_penanganan_id: number | null;
  dokumen_id: number;
  dokumen_nama?: string;  // For display
  jenis_bukti: string;
  deskripsi: string | null;
  diunggah_oleh: number;
  diunggah_pada: string;
}

/**
 * KONTROL_KEAMANAN
 * Security control master data
 */
export interface KontrolKeamanan {
  id: number;
  kode: string;
  nama: string;
  deskripsi: string | null;
  kategori: string;
  tipe_kontrol: 'preventif' | 'detektif' | 'korektif';
  unit_pemilik: string | null;
  standar_referensi: string | null;
  status: 'aktif' | 'nonaktif';
}

/**
 * RISIKO_KONTROL
 * Risk-Control relationship
 */
export interface RisikoKontrol {
  id: number;
  risiko_id: number;
  kontrol_id: number;
  kontrol_kode?: string;  // For display
  kontrol_nama?: string;  // For display
  status_implementasi: 'belum_diterapkan' | 'sebagian' | 'penuh';
  efektivitas: 'rendah' | 'sedang' | 'tinggi' | null;
  catatan: string | null;
}

/**
 * SOP (Simplified)
 * Standard Operating Procedure
 */
export interface SOP {
  id: number;
  kode: string;
  judul: string;
  deskripsi: string | null;
  versi_aktif: string;
  tanggal_berlaku: string;
  unit_pemilik: string;
  status: 'aktif' | 'revisi' | 'nonaktif';
}

/**
 * PENANGANAN_SOP
 * Treatment-SOP relationship
 */
export interface PenangananSOP {
  id: number;
  rencana_penanganan_id: number;
  sop_id: number;
  sop_kode?: string;  // For display
  sop_judul?: string;  // For display
  relevansi: string | null;
}

/**
 * DOKUMEN (Simplified)
 * Document metadata
 */
export interface Dokumen {
  id: number;
  nama_file: string;
  tipe_dokumen: string;
  ekstensi: string;
  ukuran_byte: number;
  path_file: string;
  diunggah_oleh: number;
  diunggah_pada: string;
}
  progres: number;
  catatan: string | null;
}
