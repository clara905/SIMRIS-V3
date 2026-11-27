/**
 * Documentation & SOP Service
 * 
 * Frontend-only mock service for Phase 7 (STAFF role).
 * 
 * Provides:
 * - Dashboard stats
 * - Document library
 * - SOP management
 * - Evidence relationships
 * 
 * All data is MOCK and frontend-only.
 */

import type { 
  Dokumen, 
  SOP, 
  SOPVersi, 
  SOPReview, 
  SOPKontrol,
  RisikoSOP,
  PenangananSOP,
  BuktiPenilaian,
  BuktiPenanganan,
  BuktiKondisiAset,
} from '@/types/document.types';
import type { PaginatedResponse } from '@/types/common.types';

// ─── Dashboard Stats ─────────────────────────────────────────────────────────

export interface DocumentationStats {
  totalDokumen: number;
  dokumenAsesmen: number;
  dokumenPenanganan: number;
  dokumenSOP: number;
  recentDocuments: Dokumen[];
  documentActivity: {
    id: number;
    aksi: string;
    dokumen_judul: string;
    waktu: string;
  }[];
}

// ─── Mock Data ───────────────────────────────────────────────────────────────

const MOCK_DOCUMENTS: Dokumen[] = [
  {
    id: 1,
    kode_dokumen: 'DOK-ASM-001',
    judul: 'Laporan Asesmen Risiko TI Q1 2024',
    tipe_dokumen: 'asesmen',
    unit_pemilik: 'TI',
    nama_file_asli: 'asesmen-risiko-ti-q1-2024.pdf',
    ekstensi: '.pdf',
    mime_type: 'application/pdf',
    ukuran_byte: 2458624,
    path_penyimpanan: '/dokumen/2024/01/asesmen-risiko-ti-q1-2024.pdf',
    checksum: 'a3f4b2c1d5e6f7g8h9i0j1k2l3m4n5o6',
    status_scan: 'clean',
    diunggah_oleh: 1,
    diunggah_oleh_nama: 'Ahmad Fauzi',
    diunggah_pada: '2024-01-15T10:30:00Z',
  },
  {
    id: 2,
    kode_dokumen: 'DOK-PEN-001',
    judul: 'Bukti Implementasi Firewall Enterprise',
    tipe_dokumen: 'penanganan',
    unit_pemilik: 'TI',
    nama_file_asli: 'implementasi-firewall-2024.pdf',
    ekstensi: '.pdf',
    mime_type: 'application/pdf',
    ukuran_byte: 1548288,
    path_penyimpanan: '/dokumen/2024/02/implementasi-firewall-2024.pdf',
    checksum: 'b4g5c3d6e7f8g9h0i1j2k3l4m5n6o7p8',
    status_scan: 'clean',
    diunggah_oleh: 2,
    diunggah_oleh_nama: 'Siti Nurhaliza',
    diunggah_pada: '2024-02-10T14:20:00Z',
  },
  {
    id: 3,
    kode_dokumen: 'DOK-SOP-001',
    judul: 'SOP Backup Data Server v2.0',
    tipe_dokumen: 'sop',
    unit_pemilik: 'TI',
    nama_file_asli: 'sop-backup-data-v2.0.docx',
    ekstensi: '.docx',
    mime_type: 'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
    ukuran_byte: 524288,
    path_penyimpanan: '/dokumen/2024/01/sop-backup-data-v2.0.docx',
    checksum: 'c5h6d4e7f8g9h0i1j2k3l4m5n6o7p8q9',
    status_scan: 'clean',
    diunggah_oleh: 1,
    diunggah_oleh_nama: 'Ahmad Fauzi',
    diunggah_pada: '2024-01-20T09:15:00Z',
  },
  {
    id: 4,
    kode_dokumen: 'DOK-ASM-002',
    judul: 'Hasil Vulnerability Assessment 2024',
    tipe_dokumen: 'asesmen',
    unit_pemilik: 'TI',
    nama_file_asli: 'vulnerability-assessment-2024.xlsx',
    ekstensi: '.xlsx',
    mime_type: 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
    ukuran_byte: 1048576,
    path_penyimpanan: '/dokumen/2024/03/vulnerability-assessment-2024.xlsx',
    checksum: 'd6i7e5f8g9h0i1j2k3l4m5n6o7p8q9r0',
    status_scan: 'pending',
    diunggah_oleh: 3,
    diunggah_oleh_nama: 'Budi Santoso',
    diunggah_pada: '2024-03-05T11:45:00Z',
  },
  {
    id: 5,
    kode_dokumen: 'DOK-AST-001',
    judul: 'Foto Kondisi Server Ruang Data',
    tipe_dokumen: 'kondisi_aset',
    unit_pemilik: 'TI',
    nama_file_asli: 'server-room-condition-march-2024.jpg',
    ekstensi: '.jpg',
    mime_type: 'image/jpeg',
    ukuran_byte: 3145728,
    path_penyimpanan: '/dokumen/2024/03/server-room-condition-march-2024.jpg',
    checksum: 'e7j8f6g9h0i1j2k3l4m5n6o7p8q9r0s1',
    status_scan: 'clean',
    diunggah_oleh: 2,
    diunggah_oleh_nama: 'Siti Nurhaliza',
    diunggah_pada: '2024-03-10T15:30:00Z',
  },
  {
    id: 6,
    kode_dokumen: 'DOK-PEN-002',
    judul: 'Bukti Pelatihan Kesadaran Keamanan',
    tipe_dokumen: 'penanganan',
    unit_pemilik: 'TI',
    nama_file_asli: 'pelatihan-security-awareness-2024.pdf',
    ekstensi: '.pdf',
    mime_type: 'application/pdf',
    ukuran_byte: 2097152,
    path_penyimpanan: '/dokumen/2024/02/pelatihan-security-awareness-2024.pdf',
    checksum: 'f8k9g7h0i1j2k3l4m5n6o7p8q9r0s1t2',
    status_scan: 'clean',
    diunggah_oleh: 1,
    diunggah_oleh_nama: 'Ahmad Fauzi',
    diunggah_pada: '2024-02-20T13:00:00Z',
  },
];

const MOCK_SOPS: SOP[] = [
  {
    id: 1,
    kode: 'SOP-TI-001',
    judul: 'Prosedur Backup dan Recovery Data',
    deskripsi: 'Standar operasional prosedur untuk melakukan backup dan pemulihan data pada sistem informasi',
    unit_pemilik: 'TI',
    versi_aktif: '2.0',
    tanggal_terakhir_review: '2024-01-15',
    status: 'aktif',
    dibuat_oleh: 1,
    dibuat_oleh_nama: 'Ahmad Fauzi',
    dibuat_pada: '2023-06-10T08:00:00Z',
    diperbarui_pada: '2024-01-15T10:30:00Z',
  },
  {
    id: 2,
    kode: 'SOP-TI-002',
    judul: 'Pengelolaan Akses Pengguna Sistem',
    deskripsi: 'Prosedur standar untuk pemberian, perubahan, dan pencabutan hak akses pengguna',
    unit_pemilik: 'TI',
    versi_aktif: '1.5',
    tanggal_terakhir_review: '2024-02-20',
    status: 'aktif',
    dibuat_oleh: 1,
    dibuat_oleh_nama: 'Ahmad Fauzi',
    dibuat_pada: '2023-08-15T09:00:00Z',
    diperbarui_pada: '2024-02-20T11:00:00Z',
  },
  {
    id: 3,
    kode: 'SOP-TI-003',
    judul: 'Manajemen Patch dan Update Sistem',
    deskripsi: 'Prosedur untuk melakukan update dan patching sistem operasi dan aplikasi',
    unit_pemilik: 'TI',
    versi_aktif: '1.0',
    tanggal_terakhir_review: null,
    status: 'revisi',
    dibuat_oleh: 2,
    dibuat_oleh_nama: 'Siti Nurhaliza',
    dibuat_pada: '2024-01-10T10:00:00Z',
    diperbarui_pada: '2024-03-01T14:00:00Z',
  },
  {
    id: 4,
    kode: 'SOP-TI-004',
    judul: 'Respons Insiden Keamanan Informasi',
    deskripsi: 'Prosedur tanggap darurat untuk menangani insiden keamanan informasi',
    unit_pemilik: 'TI',
    versi_aktif: '3.0',
    tanggal_terakhir_review: '2024-03-10',
    status: 'aktif',
    dibuat_oleh: 1,
    dibuat_oleh_nama: 'Ahmad Fauzi',
    dibuat_pada: '2023-01-05T08:30:00Z',
    diperbarui_pada: '2024-03-10T15:00:00Z',
  },
];

const MOCK_SOP_VERSIONS: SOPVersi[] = [
  {
    id: 1,
    sop_id: 1,
    nomor_versi: '1.0',
    tanggal_versi: '2023-06-10',
    dokumen_id: null,
    catatan_versi: 'Versi awal',
    status: 'obsolete',
    dibuat_oleh: 1,
    dibuat_oleh_nama: 'Ahmad Fauzi',
    dibuat_pada: '2023-06-10T08:00:00Z',
  },
  {
    id: 2,
    sop_id: 1,
    nomor_versi: '2.0',
    tanggal_versi: '2024-01-15',
    dokumen_id: 3,
    dokumen_nama: 'SOP Backup Data Server v2.0',
    catatan_versi: 'Penambahan prosedur cloud backup dan disaster recovery',
    status: 'aktif',
    dibuat_oleh: 1,
    dibuat_oleh_nama: 'Ahmad Fauzi',
    dibuat_pada: '2024-01-15T10:30:00Z',
  },
  {
    id: 3,
    sop_id: 2,
    nomor_versi: '1.0',
    tanggal_versi: '2023-08-15',
    dokumen_id: null,
    catatan_versi: 'Versi awal',
    status: 'obsolete',
    dibuat_oleh: 1,
    dibuat_oleh_nama: 'Ahmad Fauzi',
    dibuat_pada: '2023-08-15T09:00:00Z',
  },
  {
    id: 4,
    sop_id: 2,
    nomor_versi: '1.5',
    tanggal_versi: '2024-02-20',
    dokumen_id: null,
    catatan_versi: 'Penyesuaian dengan kebijakan MFA',
    status: 'aktif',
    dibuat_oleh: 1,
    dibuat_oleh_nama: 'Ahmad Fauzi',
    dibuat_pada: '2024-02-20T11:00:00Z',
  },
  {
    id: 5,
    sop_id: 4,
    nomor_versi: '3.0',
    tanggal_versi: '2024-03-10',
    dokumen_id: null,
    catatan_versi: 'Penambahan prosedur notifikasi dan eskalasi',
    status: 'aktif',
    dibuat_oleh: 1,
    dibuat_oleh_nama: 'Ahmad Fauzi',
    dibuat_pada: '2024-03-10T15:00:00Z',
  },
];

const MOCK_SOP_REVIEWS: SOPReview[] = [
  {
    id: 1,
    sop_id: 1,
    versi_id: 2,
    versi_nomor: '2.0',
    tanggal_review: '2024-01-15',
    direview_oleh: 2,
    direview_oleh_nama: 'Siti Nurhaliza',
    hasil_review: 'approved',
    catatan_review: 'Prosedur sudah sesuai dengan best practice. Approved untuk implementasi.',
    direview_pada: '2024-01-15T14:00:00Z',
  },
  {
    id: 2,
    sop_id: 2,
    versi_id: 4,
    versi_nomor: '1.5',
    tanggal_review: '2024-02-20',
    direview_oleh: 3,
    direview_oleh_nama: 'Budi Santoso',
    hasil_review: 'approved',
    catatan_review: 'Implementasi MFA sudah sesuai standar ISO 27001.',
    direview_pada: '2024-02-20T15:30:00Z',
  },
  {
    id: 3,
    sop_id: 3,
    versi_id: null,
    tanggal_review: '2024-03-01',
    direview_oleh: 1,
    direview_oleh_nama: 'Ahmad Fauzi',
    hasil_review: 'revision_required',
    catatan_review: 'Perlu ditambahkan prosedur testing sebelum deployment ke production.',
    direview_pada: '2024-03-01T16:00:00Z',
  },
];

const MOCK_SOP_CONTROLS: SOPKontrol[] = [
  {
    id: 1,
    sop_id: 1,
    kontrol_id: 1,
    kontrol_kode: 'A.12.3.1',
    kontrol_nama: 'Information Backup',
    kontrol_kategori: 'Technical',
    kontrol_tipe: 'preventif',
    relevansi: 'SOP ini mengimplementasikan kontrol backup informasi sesuai ISO 27001',
    ditambahkan_pada: '2024-01-15T10:30:00Z',
  },
  {
    id: 2,
    sop_id: 2,
    kontrol_id: 2,
    kontrol_kode: 'A.9.2.1',
    kontrol_nama: 'User Registration and De-registration',
    kontrol_kategori: 'Organizational',
    kontrol_tipe: 'preventif',
    relevansi: 'Prosedur registrasi dan de-registrasi pengguna',
    ditambahkan_pada: '2024-02-20T11:00:00Z',
  },
  {
    id: 3,
    sop_id: 4,
    kontrol_id: 3,
    kontrol_kode: 'A.16.1.1',
    kontrol_nama: 'Responsibilities and Procedures',
    kontrol_kategori: 'Organizational',
    kontrol_tipe: 'detektif',
    relevansi: 'Tanggung jawab dan prosedur dalam penanganan insiden keamanan',
    ditambahkan_pada: '2024-03-10T15:00:00Z',
  },
];

// ─── Evidence Relationships (Phase 7.1) ─────────────────────────────────────

/**
 * BUKTI_PENILAIAN - Links documents to risk assessments
 * Frontend mock data only - references existing PENILAIAN_RISIKO records
 * from asesmen.service.ts (id: 1, 2, 3)
 */
const MOCK_BUKTI_PENILAIAN: BuktiPenilaian[] = [
  {
    id: 1,
    penilaian_risiko_id: 1, // Links to PENILAIAN_RISIKO id:1 (RISK-ASM001-01)
    dokumen_id: 1, // Links to DOK-ASM-001
    dokumen_kode: 'DOK-ASM-001',
    dokumen_judul: 'Laporan Asesmen Risiko TI Q1 2024',
    jenis_bukti: 'Laporan Asesmen',
    deskripsi: 'Dokumen hasil asesmen risiko keamanan informasi untuk aset TI',
    diunggah_oleh: 1,
    diunggah_oleh_nama: 'Ahmad Fauzi',
    diunggah_pada: '2024-01-15T10:30:00Z',
  },
  {
    id: 2,
    penilaian_risiko_id: 2, // Links to PENILAIAN_RISIKO id:2 (RISK-ASM001-02)
    dokumen_id: 1, // Same document used for another assessment
    dokumen_kode: 'DOK-ASM-001',
    dokumen_judul: 'Laporan Asesmen Risiko TI Q1 2024',
    jenis_bukti: 'Laporan Asesmen',
    deskripsi: 'Dokumen asesmen mencakup evaluasi risiko phishing',
    diunggah_oleh: 1,
    diunggah_oleh_nama: 'Ahmad Fauzi',
    diunggah_pada: '2024-01-15T10:30:00Z',
  },
  {
    id: 3,
    penilaian_risiko_id: 3, // Links to PENILAIAN_RISIKO id:3 (RISK-ASM002-01)
    dokumen_id: 4, // Links to DOK-ASM-002
    dokumen_kode: 'DOK-ASM-002',
    dokumen_judul: 'Hasil Vulnerability Assessment 2024',
    jenis_bukti: 'Hasil Assessment',
    deskripsi: 'Hasil vulnerability scan dan analisis kerentanan sistem keuangan',
    diunggah_oleh: 3,
    diunggah_oleh_nama: 'Budi Santoso',
    diunggah_pada: '2024-03-05T11:45:00Z',
  },
];

/**
 * BUKTI_PENANGANAN - Links documents to treatment plans/progress
 * Frontend mock data only - references existing RENCANA_PENANGANAN and
 * PROGRES_PENANGANAN records from penanganan.service.ts
 */
const MOCK_BUKTI_PENANGANAN: BuktiPenanganan[] = [
  {
    id: 1,
    progres_penanganan_id: 'PG002', // Links to PROGRES_PENANGANAN PG002
    rencana_penanganan_id: 'RP001', // Links to RENCANA_PENANGANAN RP001
    rencana_kode: 'RP001',
    rencana_nama: 'Implementasi enkripsi database dan access control',
    dokumen_id: 2, // Links to DOK-PEN-001
    dokumen_kode: 'DOK-PEN-001',
    dokumen_judul: 'Bukti Implementasi Firewall Enterprise',
    jenis_bukti: 'Bukti Implementasi',
    deskripsi: 'Dokumentasi implementasi firewall dan konfigurasi access control',
    diunggah_oleh: 2,
    diunggah_oleh_nama: 'Siti Nurhaliza',
    diunggah_pada: '2024-02-10T14:20:00Z',
  },
  {
    id: 2,
    progres_penanganan_id: 'PG004', // Links to PROGRES_PENANGANAN PG004
    rencana_penanganan_id: 'RP002', // Links to RENCANA_PENANGANAN RP002
    rencana_kode: 'RP002',
    rencana_nama: 'Pelatihan awareness keamanan data',
    dokumen_id: 6, // Links to DOK-PEN-002
    dokumen_kode: 'DOK-PEN-002',
    dokumen_judul: 'Bukti Pelatihan Kesadaran Keamanan',
    jenis_bukti: 'Bukti Pelatihan',
    deskripsi: 'Dokumentasi pelaksanaan pelatihan security awareness untuk 45 staff',
    diunggah_oleh: 1,
    diunggah_oleh_nama: 'Ahmad Fauzi',
    diunggah_pada: '2024-02-20T13:00:00Z',
  },
  {
    id: 3,
    progres_penanganan_id: null, // No specific progress, general plan evidence
    rencana_penanganan_id: 'RP003', // Links to RENCANA_PENANGANAN RP003
    rencana_kode: 'RP003',
    rencana_nama: 'Asuransi perangkat IT dan extended warranty',
    dokumen_id: 2, // Links to DOK-PEN-001 (reused)
    dokumen_kode: 'DOK-PEN-001',
    dokumen_judul: 'Bukti Implementasi Firewall Enterprise',
    jenis_bukti: 'Proposal Asuransi',
    deskripsi: 'Proposal dan persetujuan asuransi perangkat IT',
    diunggah_oleh: 2,
    diunggah_oleh_nama: 'Siti Nurhaliza',
    diunggah_pada: '2024-02-10T14:20:00Z',
  },
];

/**
 * BUKTI_KONDISI_ASET - Links documents to asset conditions
 * Frontend mock data only - references existing ASET records from asset.service.ts
 */
const MOCK_BUKTI_KONDISI_ASET: BuktiKondisiAset[] = [
  {
    id: 1,
    aset_id: 2, // Links to AST-002 (Server Database MySQL)
    aset_kode: 'AST-002',
    aset_nama: 'Server Database MySQL',
    dokumen_id: 5, // Links to DOK-AST-001
    dokumen_kode: 'DOK-AST-001',
    dokumen_judul: 'Foto Kondisi Server Ruang Data',
    tanggal_kondisi: '2024-03-10',
    kondisi_saat_itu: 'Baik',
    deskripsi: 'Dokumentasi visual kondisi server database di data center',
    diunggah_oleh: 2,
    diunggah_oleh_nama: 'Siti Nurhaliza',
    diunggah_pada: '2024-03-10T15:30:00Z',
  },
  {
    id: 2,
    aset_id: 3, // Links to AST-003 (Printer Epson L3110)
    aset_kode: 'AST-003',
    aset_nama: 'Printer Epson L3110',
    dokumen_id: 5, // Same document, multiple assets
    dokumen_kode: 'DOK-AST-001',
    dokumen_judul: 'Foto Kondisi Server Ruang Data',
    tanggal_kondisi: '2024-03-10',
    kondisi_saat_itu: 'Rusak Ringan',
    deskripsi: 'Dokumentasi kerusakan ringan pada printer (paper jam mechanism)',
    diunggah_oleh: 2,
    diunggah_oleh_nama: 'Siti Nurhaliza',
    diunggah_pada: '2024-03-10T15:30:00Z',
  },
];

/**
 * RISIKO_SOP - Links risks to SOPs
 * Frontend mock data only - references existing RISIKO records from asesmen.service.ts
 * and SOP records from this service
 */
const MOCK_RISIKO_SOP: RisikoSOP[] = [
  {
    id: 1,
    risiko_id: 1, // Links to RISIKO id:1 (RISK-ASM001-01 - Malware attack)
    risiko_kode: 'RISK-ASM001-01',
    risiko_skenario: 'Serangan malware menginfeksi server aplikasi',
    sop_id: 3, // Links to SOP id:3 (Manajemen Patch dan Update Sistem)
    relevansi: 'SOP patch management diperlukan untuk mitigasi kerentanan malware',
    ditambahkan_pada: '2024-01-20T10:00:00Z',
  },
  {
    id: 2,
    risiko_id: 2, // Links to RISIKO id:2 (RISK-ASM001-02 - Phishing attack)
    risiko_kode: 'RISK-ASM001-02',
    risiko_skenario: 'Pengguna menerima email phishing dan kredensial dicuri',
    sop_id: 2, // Links to SOP id:2 (Pengelolaan Akses Pengguna Sistem)
    relevansi: 'SOP akses pengguna mengatur autentikasi dan MFA untuk mencegah phishing',
    ditambahkan_pada: '2024-01-22T11:30:00Z',
  },
  {
    id: 3,
    risiko_id: 1, // Links to RISIKO id:1 (multiple SOPs per risk)
    risiko_kode: 'RISK-ASM001-01',
    risiko_skenario: 'Serangan malware menginfeksi server aplikasi',
    sop_id: 4, // Links to SOP id:4 (Respons Insiden Keamanan Informasi)
    relevansi: 'SOP respons insiden untuk menangani kejadian serangan malware',
    ditambahkan_pada: '2024-01-25T09:15:00Z',
  },
];

/**
 * PENANGANAN_SOP - Links treatment plans to SOPs
 * Frontend mock data only - references existing RENCANA_PENANGANAN from
 * penanganan.service.ts and SOP records from this service
 */
const MOCK_PENANGANAN_SOP: PenangananSOP[] = [
  {
    id: 1,
    rencana_penanganan_id: 'RP001', // Links to RENCANA_PENANGANAN RP001
    rencana_kode: 'RP001',
    rencana_nama: 'Implementasi enkripsi database dan access control',
    sop_id: 2, // Links to SOP id:2 (Pengelolaan Akses Pengguna Sistem)
    relevansi: 'Implementasi access control mengikuti prosedur SOP akses pengguna',
    ditambahkan_pada: '2024-01-15T14:00:00Z',
  },
  {
    id: 2,
    rencana_penanganan_id: 'RP001', // Multiple SOPs per treatment
    rencana_kode: 'RP001',
    rencana_nama: 'Implementasi enkripsi database dan access control',
    sop_id: 1, // Links to SOP id:1 (Prosedur Backup dan Recovery Data)
    relevansi: 'Enkripsi database mengikuti prosedur backup untuk memastikan data integrity',
    ditambahkan_pada: '2024-01-15T14:30:00Z',
  },
  {
    id: 3,
    rencana_penanganan_id: 'RP002', // Links to RENCANA_PENANGANAN RP002
    rencana_kode: 'RP002',
    rencana_nama: 'Pelatihan awareness keamanan data',
    sop_id: 2, // Links to SOP id:2
    relevansi: 'Pelatihan mencakup materi SOP akses pengguna dan keamanan data',
    ditambahkan_pada: '2024-02-01T10:00:00Z',
  },
];

// ─── Service Functions ───────────────────────────────────────────────────────

export const documentService = {
  /**
   * Get documentation dashboard statistics
   */
  async getStats(): Promise<DocumentationStats> {
    // Simulate API delay
    await new Promise(resolve => setTimeout(resolve, 300));

    const recentDocs = [...MOCK_DOCUMENTS]
      .sort((a, b) => new Date(b.diunggah_pada).getTime() - new Date(a.diunggah_pada).getTime())
      .slice(0, 5);

    return {
      totalDokumen: MOCK_DOCUMENTS.length,
      dokumenAsesmen: MOCK_DOCUMENTS.filter(d => d.tipe_dokumen === 'asesmen').length,
      dokumenPenanganan: MOCK_DOCUMENTS.filter(d => d.tipe_dokumen === 'penanganan').length,
      dokumenSOP: MOCK_DOCUMENTS.filter(d => d.tipe_dokumen === 'sop').length,
      recentDocuments: recentDocs,
      documentActivity: [
        {
          id: 1,
          aksi: 'Mengunggah dokumen baru',
          dokumen_judul: 'Foto Kondisi Server Ruang Data',
          waktu: '2 jam yang lalu',
        },
        {
          id: 2,
          aksi: 'Memperbarui dokumen',
          dokumen_judul: 'Hasil Vulnerability Assessment 2024',
          waktu: '5 jam yang lalu',
        },
        {
          id: 3,
          aksi: 'Menghapus dokumen',
          dokumen_judul: 'Laporan Lama Q4 2023',
          waktu: '1 hari yang lalu',
        },
      ],
    };
  },

  /**
   * Get paginated document list
   */
  async getDocuments(params: {
    page?: number;
    per_page?: number;
    search?: string;
    tipe_dokumen?: string;
    status_scan?: string;
  }): Promise<PaginatedResponse<Dokumen>> {
    await new Promise(resolve => setTimeout(resolve, 400));

    let filtered = [...MOCK_DOCUMENTS];

    // Filter by search
    if (params.search) {
      const search = params.search.toLowerCase();
      filtered = filtered.filter(d =>
        d.judul.toLowerCase().includes(search) ||
        d.kode_dokumen.toLowerCase().includes(search) ||
        d.nama_file_asli.toLowerCase().includes(search)
      );
    }

    // Filter by type
    if (params.tipe_dokumen) {
      filtered = filtered.filter(d => d.tipe_dokumen === params.tipe_dokumen);
    }

    // Filter by scan status
    if (params.status_scan) {
      filtered = filtered.filter(d => d.status_scan === params.status_scan);
    }

    // Sort by upload date (newest first)
    filtered.sort((a, b) => 
      new Date(b.diunggah_pada).getTime() - new Date(a.diunggah_pada).getTime()
    );

    // Pagination
    const page = params.page || 1;
    const perPage = params.per_page || 10;
    const total = filtered.length;
    const lastPage = Math.ceil(total / perPage);
    const from = (page - 1) * perPage;
    const to = Math.min(from + perPage, total);
    const data = filtered.slice(from, to);

    return {
      data,
      links: {
        first: page > 1 ? `/api/documents?page=1` : null,
        last: page < lastPage ? `/api/documents?page=${lastPage}` : null,
        prev: page > 1 ? `/api/documents?page=${page - 1}` : null,
        next: page < lastPage ? `/api/documents?page=${page + 1}` : null,
      },
      meta: {
        current_page: page,
        from: data.length > 0 ? from + 1 : null,
        last_page: lastPage,
        per_page: perPage,
        to: data.length > 0 ? to : null,
        total,
        path: '/api/documents',
      },
    };
  },

  /**
   * Get document by ID
   */
  async getDocument(id: number): Promise<Dokumen | null> {
    await new Promise(resolve => setTimeout(resolve, 200));
    return MOCK_DOCUMENTS.find(d => d.id === id) || null;
  },

  /**
   * Get SOP list
   */
  async getSOPs(params: {
    page?: number;
    per_page?: number;
    search?: string;
    status?: string;
  }): Promise<PaginatedResponse<SOP>> {
    await new Promise(resolve => setTimeout(resolve, 400));

    let filtered = [...MOCK_SOPS];

    // Filter by search
    if (params.search) {
      const search = params.search.toLowerCase();
      filtered = filtered.filter(s =>
        s.judul.toLowerCase().includes(search) ||
        s.kode.toLowerCase().includes(search)
      );
    }

    // Filter by status
    if (params.status) {
      filtered = filtered.filter(s => s.status === params.status);
    }

    // Sort by update date (newest first)
    filtered.sort((a, b) => 
      new Date(b.diperbarui_pada).getTime() - new Date(a.diperbarui_pada).getTime()
    );

    // Pagination
    const page = params.page || 1;
    const perPage = params.per_page || 10;
    const total = filtered.length;
    const lastPage = Math.ceil(total / perPage);
    const from = (page - 1) * perPage;
    const to = Math.min(from + perPage, total);
    const data = filtered.slice(from, to);

    return {
      data,
      links: {
        first: page > 1 ? `/api/sops?page=1` : null,
        last: page < lastPage ? `/api/sops?page=${lastPage}` : null,
        prev: page > 1 ? `/api/sops?page=${page - 1}` : null,
        next: page < lastPage ? `/api/sops?page=${page + 1}` : null,
      },
      meta: {
        current_page: page,
        from: data.length > 0 ? from + 1 : null,
        last_page: lastPage,
        per_page: perPage,
        to: data.length > 0 ? to : null,
        total,
        path: '/api/sops',
      },
    };
  },

  /**
   * Get SOP by ID
   */
  async getSOP(id: number): Promise<SOP | null> {
    await new Promise(resolve => setTimeout(resolve, 200));
    return MOCK_SOPS.find(s => s.id === id) || null;
  },

  /**
   * Get SOP versions
   */
  async getSOPVersions(sopId: number): Promise<SOPVersi[]> {
    await new Promise(resolve => setTimeout(resolve, 200));
    return MOCK_SOP_VERSIONS.filter(v => v.sop_id === sopId)
      .sort((a, b) => new Date(b.tanggal_versi).getTime() - new Date(a.tanggal_versi).getTime());
  },

  /**
   * Get SOP reviews
   */
  async getSOPReviews(sopId: number): Promise<SOPReview[]> {
    await new Promise(resolve => setTimeout(resolve, 200));
    return MOCK_SOP_REVIEWS.filter(r => r.sop_id === sopId)
      .sort((a, b) => new Date(b.direview_pada).getTime() - new Date(a.direview_pada).getTime());
  },

  /**
   * Get SOP controls
   */
  async getSOPControls(sopId: number): Promise<SOPKontrol[]> {
    await new Promise(resolve => setTimeout(resolve, 200));
    return MOCK_SOP_CONTROLS.filter(c => c.sop_id === sopId);
  },

  /**
   * Get document evidence relationships (Phase 7.1)
   * Returns all evidence relationships for a specific document
   */
  async getDocumentEvidenceRelationships(dokumenId: number): Promise<{
    buktiPenilaian: BuktiPenilaian[];
    buktiPenanganan: BuktiPenanganan[];
    buktiKondisiAset: BuktiKondisiAset[];
  }> {
    await new Promise(resolve => setTimeout(resolve, 300));
    return {
      buktiPenilaian: MOCK_BUKTI_PENILAIAN.filter(b => b.dokumen_id === dokumenId),
      buktiPenanganan: MOCK_BUKTI_PENANGANAN.filter(b => b.dokumen_id === dokumenId),
      buktiKondisiAset: MOCK_BUKTI_KONDISI_ASET.filter(b => b.dokumen_id === dokumenId),
    };
  },

  /**
   * Get SOP risk relationships (Phase 7.1)
   * Returns all risks linked to a specific SOP
   */
  async getSOPRisks(sopId: number): Promise<RisikoSOP[]> {
    await new Promise(resolve => setTimeout(resolve, 200));
    return MOCK_RISIKO_SOP.filter(r => r.sop_id === sopId);
  },

  /**
   * Get SOP treatment relationships (Phase 7.1)
   * Returns all treatment plans linked to a specific SOP
   */
  async getSOPTreatments(sopId: number): Promise<PenangananSOP[]> {
    await new Promise(resolve => setTimeout(resolve, 200));
    return MOCK_PENANGANAN_SOP.filter(p => p.sop_id === sopId);
  },
};
