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
  kerahasiaan: CIALevel;
  integritas: CIALevel;
  ketersediaan: CIALevel;
  status: 'aktif' | 'nonaktif' | 'review';
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

// --- Penilaian Risiko ---
export interface RiskAssessment {
  id: number;
  aset_id: number;
  aset_nama: string;
  ancaman_id: number;
  ancaman_nama: string;
  kerentanan_id: number;
  kerentanan_nama: string;
  kemungkinan: 1 | 2 | 3 | 4 | 5;
  dampak: 1 | 2 | 3 | 4 | 5;
  skor_risiko: number;
  level_risiko: RiskLevel;
  tanggal_penilaian: string;
  penilai: string;
  status: 'draft' | 'disetujui' | 'ditolak';
}

// --- Penanganan Risiko ---
export type TreatmentStrategy = 'mitigasi' | 'hindari' | 'transfer' | 'terima';
export type TreatmentStatus =
  | 'belum_dimulai'
  | 'sedang_berjalan'
  | 'selesai'
  | 'terlambat';

export interface RiskTreatment {
  id: number;
  penilaian_risiko_id: number;
  risiko_ringkasan: string;
  strategi: TreatmentStrategy;
  rencana: string;
  penanggung_jawab: string;
  tenggat_waktu: string;
  status: TreatmentStatus;
  progres: number;
  catatan: string | null;
}
