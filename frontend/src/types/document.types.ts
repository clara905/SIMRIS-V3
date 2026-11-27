/**
 * Documentation & SOP Types
 * 
 * ERD-compliant type definitions for:
 * - DOKUMEN
 * - SOP, SOP_VERSI, SOP_REVIEW, SOP_KONTROL
 * - RISIKO_SOP, PENANGANAN_SOP
 * - BUKTI_PENILAIAN, BUKTI_PENANGANAN, BUKTI_KONDISI_ASET
 * 
 * Phase 7 — STAFF only
 */

// ─── DOKUMEN ─────────────────────────────────────────────────────────────────

export interface Dokumen {
  id: number;
  kode_dokumen: string;
  judul: string;
  tipe_dokumen: string;
  unit_pemilik: string | null;
  nama_file_asli: string;
  ekstensi: string;
  mime_type: string | null;
  ukuran_byte: number;
  path_penyimpanan: string | null;
  checksum: string | null;
  status_scan: 'pending' | 'clean' | 'infected' | 'error' | null;
  diunggah_oleh: number;
  diunggah_oleh_nama?: string;  // For display
  diunggah_pada: string;
}

export type TipeDokumen = 
  | 'asesmen'
  | 'penanganan'
  | 'kondisi_aset'
  | 'sop'
  | 'kontrol'
  | 'laporan'
  | 'lainnya';

// ─── SOP ─────────────────────────────────────────────────────────────────────

export interface SOP {
  id: number;
  kode: string;
  judul: string;
  deskripsi: string | null;
  unit_pemilik: string;
  versi_aktif: string | null;
  tanggal_terakhir_review: string | null;
  status: 'draft' | 'aktif' | 'revisi' | 'nonaktif';
  dibuat_oleh: number;
  dibuat_oleh_nama?: string;  // For display
  dibuat_pada: string;
  diperbarui_pada: string;
}

// ─── SOP_VERSI ───────────────────────────────────────────────────────────────

export interface SOPVersi {
  id: number;
  sop_id: number;
  nomor_versi: string;
  tanggal_versi: string;
  dokumen_id: number | null;
  dokumen_nama?: string;  // For display
  catatan_versi: string | null;
  status: 'draft' | 'aktif' | 'obsolete';
  dibuat_oleh: number;
  dibuat_oleh_nama?: string;  // For display
  dibuat_pada: string;
}

// ─── SOP_REVIEW ──────────────────────────────────────────────────────────────

export interface SOPReview {
  id: number;
  sop_id: number;
  versi_id: number | null;
  versi_nomor?: string;  // For display
  tanggal_review: string;
  direview_oleh: number;
  direview_oleh_nama?: string;  // For display
  hasil_review: 'approved' | 'revision_required' | 'rejected' | null;
  catatan_review: string | null;
  direview_pada: string;
}

// ─── SOP_KONTROL ─────────────────────────────────────────────────────────────

export interface SOPKontrol {
  id: number;
  sop_id: number;
  kontrol_id: number;
  kontrol_kode?: string;  // For display
  kontrol_nama?: string;  // For display
  kontrol_kategori?: string;  // For display
  kontrol_tipe?: string;  // For display
  relevansi: string | null;
  ditambahkan_pada: string;
}

// ─── RISIKO_SOP ──────────────────────────────────────────────────────────────

export interface RisikoSOP {
  id: number;
  risiko_id: number;
  risiko_kode?: string;  // For display
  risiko_skenario?: string;  // For display
  sop_id: number;
  relevansi: string | null;
  ditambahkan_pada: string;
}

// ─── PENANGANAN_SOP ──────────────────────────────────────────────────────────

export interface PenangananSOP {
  id: number;
  rencana_penanganan_id: number;
  rencana_kode?: string;  // For display
  rencana_nama?: string;  // For display
  sop_id: number;
  relevansi: string | null;
  ditambahkan_pada: string;
}

// ─── Evidence (Bukti) ────────────────────────────────────────────────────────

export interface BuktiPenilaian {
  id: number;
  penilaian_risiko_id: number;
  dokumen_id: number;
  dokumen_kode?: string;  // For display
  dokumen_judul?: string;  // For display
  jenis_bukti: string;
  deskripsi: string | null;
  diunggah_oleh: number;
  diunggah_oleh_nama?: string;  // For display
  diunggah_pada: string;
}

export interface BuktiPenanganan {
  id: number;
  progres_penanganan_id: number | null;
  rencana_penanganan_id: number | null;
  rencana_kode?: string;  // For display
  rencana_nama?: string;  // For display
  dokumen_id: number;
  dokumen_kode?: string;  // For display
  dokumen_judul?: string;  // For display
  jenis_bukti: string;
  deskripsi: string | null;
  diunggah_oleh: number;
  diunggah_oleh_nama?: string;  // For display
  diunggah_pada: string;
}

export interface BuktiKondisiAset {
  id: number;
  aset_id: number;
  aset_kode?: string;  // For display
  aset_nama?: string;  // For display
  dokumen_id: number;
  dokumen_kode?: string;  // For display
  dokumen_judul?: string;  // For display
  tanggal_kondisi: string;
  kondisi_saat_itu: string;
  deskripsi: string | null;
  diunggah_oleh: number;
  diunggah_oleh_nama?: string;  // For display
  diunggah_pada: string;
}

// ─── Display Helpers ─────────────────────────────────────────────────────────

export type DocumentStatusColor = 'success' | 'warning' | 'error' | 'neutral';

export function getDocumentStatusBadge(status: Dokumen['status_scan']): {
  variant: DocumentStatusColor;
  label: string;
} {
  if (status === 'clean') return { variant: 'success', label: 'Aman' };
  if (status === 'pending') return { variant: 'neutral', label: 'Menunggu' };
  if (status === 'infected') return { variant: 'error', label: 'Terinfeksi' };
  if (status === 'error') return { variant: 'warning', label: 'Error' };
  return { variant: 'neutral', label: 'Belum Dipindai' };
}

export type SOPStatusVariant = 'success' | 'info' | 'warning' | 'neutral';

export function getSOPStatusBadge(status: SOP['status']): {
  variant: SOPStatusVariant;
  label: string;
} {
  if (status === 'aktif') return { variant: 'success', label: 'Aktif' };
  if (status === 'draft') return { variant: 'neutral', label: 'Draft' };
  if (status === 'revisi') return { variant: 'warning', label: 'Revisi' };
  if (status === 'nonaktif') return { variant: 'neutral', label: 'Nonaktif' };
  return { variant: 'neutral', label: status };
}

export function formatFileSize(bytes: number): string {
  if (bytes === 0) return '0 B';
  const k = 1024;
  const sizes = ['B', 'KB', 'MB', 'GB'];
  const i = Math.floor(Math.log(bytes) / Math.log(k));
  return `${(bytes / Math.pow(k, i)).toFixed(1)} ${sizes[i]}`;
}

export function getFileIcon(extension: string): string {
  const ext = extension.toLowerCase().replace('.', '');
  
  // Documents
  if (['pdf'].includes(ext)) return '📄';
  if (['doc', 'docx'].includes(ext)) return '📝';
  if (['xls', 'xlsx'].includes(ext)) return '📊';
  if (['ppt', 'pptx'].includes(ext)) return '📽️';
  
  // Images
  if (['jpg', 'jpeg', 'png', 'gif', 'webp', 'svg'].includes(ext)) return '🖼️';
  
  // Archives
  if (['zip', 'rar', '7z', 'tar', 'gz'].includes(ext)) return '📦';
  
  // Text
  if (['txt', 'md', 'csv'].includes(ext)) return '📋';
  
  // Code/Data
  if (['json', 'xml', 'yaml', 'yml'].includes(ext)) return '📋';
  
  return '📁';
}
