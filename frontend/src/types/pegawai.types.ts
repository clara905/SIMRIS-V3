export type JenisKepegawaian = 'PNS' | 'PPPK' | 'NON_PNS' | 'TNI';

export interface PenempatanSummary {
  id: number;
  unit_id: number;
  unit: {
    kode: string;
    nama: string;
    jenis: string;
  } | null;
  mulai: string;
  selesai: string | null;
}

export interface Pegawai {
  id: number;
  nip: string;
  nama: string;
  email_kontak: string | null;
  jenis_kepegawaian: JenisKepegawaian;
  aktif: boolean;
  penempatan: PenempatanSummary | null;
}

export interface StorePegawaiPayload {
  nip: string;
  nama: string;
  email_kontak?: string;
  jenis_kepegawaian: JenisKepegawaian;
  unit_id: number;
  tanggal_mulai: string;
}

export interface UpdatePegawaiPayload {
  nip: string;
  nama: string;
  email_kontak?: string;
  jenis_kepegawaian: JenisKepegawaian;
}

export interface DeactivatePegawaiPayload {
  tanggal_selesai: string;
  catatan?: string;
}
