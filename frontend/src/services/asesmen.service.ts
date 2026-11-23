import type { AsesmenRisiko, Risiko, RisikoAset, PenilaianRisiko, SumberAncaman } from '@/types/risk.types';
import type { PaginatedResponse } from '@/types/common.types';

// ─── Assessment Summary Stats ────────────────────────────────────────────────

export interface AsesmenSummary {
  totalAsesmen: number;
  draft: number;
  active: number;
  completed: number;
}

// ─── Mock Data: Assessment Periods & Series ──────────────────────────────────

const MOCK_PERIODE = [
  { id: 1, nama: 'Periode 2026', tahun: 2026, tanggal_mulai: '2026-01-01', tanggal_selesai: '2026-12-31', status: 'active' as const },
];

const MOCK_SERI = [
  { id: 1, periode_id: 1, nama: 'Seri Q4 2026', deskripsi: 'Asesmen risiko triwulan 4 tahun 2026', tanggal_mulai: '2026-10-01', tanggal_selesai: '2026-12-31' },
];

const MOCK_METODE = [
  { id: 1, nama: 'ISO 31000:2018', deskripsi: 'Standar manajemen risiko ISO 31000:2018', berlaku_mulai: '2023-01-01', berlaku_hingga: null, is_active: true },
];

// ─── Mock Data: Threat Sources (CRITICAL MISSING ENTITY) ─────────────────────

let MOCK_SUMBER_ANCAMAN: SumberAncaman[] = [
  { id: 1, kode: 'SA-INT-001', nama: 'Karyawan Internal', deskripsi: 'Ancaman dari karyawan atau pegawai internal organisasi', kategori: 'internal', status: 'aktif' },
  { id: 2, kode: 'SA-EXT-001', nama: 'Hacker/Cracker', deskripsi: 'Peretas dari luar organisasi', kategori: 'eksternal', status: 'aktif' },
  { id: 3, kode: 'SA-EXT-002', nama: 'Malware/Virus', deskripsi: 'Perangkat lunak berbahaya', kategori: 'eksternal', status: 'aktif' },
  { id: 4, kode: 'SA-NAT-001', nama: 'Bencana Alam', deskripsi: 'Gempa bumi, banjir, kebakaran', kategori: 'eksternal', status: 'aktif' },
  { id: 5, kode: 'SA-EXT-003', nama: 'Kompetitor', deskripsi: 'Pesaing bisnis', kategori: 'eksternal', status: 'aktif' },
];

// ─── Mock Data: Assessments ──────────────────────────────────────────────────

let MOCK_ASESMEN: AsesmenRisiko[] = [
  {
    id: 1,
    seri_asesmen_id: 1,
    seri_asesmen_nama: 'Seri Q4 2026',
    versi_metode_risiko_id: 1,
    metode_nama: 'ISO 31000:2018',
    unit_id: 1,
    unit_nama: 'Bidang TI',
    kode: 'ASM-2026-001',
    nama: 'Asesmen Risiko Keamanan Informasi Q4 2026',
    deskripsi: 'Penilaian risiko keamanan informasi untuk aset TI periode Q4 2026',
    dibuat_oleh: 1,
    dibuat_oleh_nama: 'Budi Santoso',
    dibuat_pada: '2026-11-01T08:00:00Z',
    diperbarui_pada: '2026-11-23T10:30:00Z',
    status: 'active',
  },
  {
    id: 2,
    seri_asesmen_id: 1,
    seri_asesmen_nama: 'Seri Q4 2026',
    versi_metode_risiko_id: 1,
    metode_nama: 'ISO 31000:2018',
    unit_id: 2,
    unit_nama: 'Bidang Keuangan',
    kode: 'ASM-2026-002',
    nama: 'Asesmen Risiko Sistem Keuangan Q4 2026',
    deskripsi: 'Penilaian risiko untuk sistem informasi keuangan',
    dibuat_oleh: 1,
    dibuat_oleh_nama: 'Budi Santoso',
    dibuat_pada: '2026-11-15T09:00:00Z',
    diperbarui_pada: '2026-11-20T14:00:00Z',
    status: 'draft',
  },
  {
    id: 3,
    seri_asesmen_id: 1,
    seri_asesmen_nama: 'Seri Q4 2026',
    versi_metode_risiko_id: 1,
    metode_nama: 'ISO 31000:2018',
    unit_id: 1,
    unit_nama: 'Bidang TI',
    kode: 'ASM-2026-003',
    nama: 'Asesmen Risiko Jaringan dan Infrastruktur',
    deskripsi: null,
    dibuat_oleh: 1,
    dibuat_oleh_nama: 'Budi Santoso',
    dibuat_pada: '2026-10-01T08:00:00Z',
    diperbarui_pada: '2026-10-31T17:00:00Z',
    status: 'completed',
  },
];

let nextAsesmenId = MOCK_ASESMEN.length + 1;

// ─── Mock Data: Risks ────────────────────────────────────────────────────────

let MOCK_RISIKO: Risiko[] = [
  {
    id: 1,
    asesmen_id: 1,
    asesmen_nama: 'ASM-2026-001',
    ancaman_id: 2,
    ancaman_nama: 'Malware / Ransomware',
    sumber_ancaman_id: 3,
    sumber_ancaman_nama: 'Malware/Virus',
    kerentanan_id: 2,
    kerentanan_nama: 'Patch Keamanan Belum Diterapkan',
    pemilik_risiko_id: 5,
    pemilik_risiko_nama: 'Kepala Bidang TI',
    dibuat_oleh: 1,
    dibuat_oleh_nama: 'Budi Santoso',
    kode: 'RISK-ASM001-01',
    skenario: 'Serangan malware menginfeksi server aplikasi melalui email phishing kepada administrator sistem',
    uraian_kerentanan: 'Patch keamanan OS dan aplikasi tidak diterapkan secara rutin dan konsisten',
    uraian_dampak: 'Downtime sistem aplikasi, kehilangan data penting, gangguan operasional seluruh unit',
    arah_dampak: 'negatif',
    uraian_pelaksanaan_rencana_kerja: 'Rencana kerja patch management sudah disusun namun pelaksanaannya tidak optimal',
    uraian_penerapan_sop_kontrol: 'SOP patch management tersedia namun tidak diikuti secara konsisten oleh tim TI',
    dibuat_pada: '2026-11-01T09:00:00Z',
    diperbarui_pada: '2026-11-20T10:30:00Z',
  },
  {
    id: 2,
    asesmen_id: 1,
    asesmen_nama: 'ASM-2026-001',
    ancaman_id: 1,
    ancaman_nama: 'Serangan Phishing',
    sumber_ancaman_id: 2,
    sumber_ancaman_nama: 'Hacker/Cracker',
    kerentanan_id: 4,
    kerentanan_nama: 'Tidak Ada MFA pada Email Server',
    pemilik_risiko_id: 5,
    pemilik_risiko_nama: 'Kepala Bidang TI',
    dibuat_oleh: 1,
    dibuat_oleh_nama: 'Budi Santoso',
    kode: 'RISK-ASM001-02',
    skenario: 'Pengguna menerima email phishing dan mengklik tautan berbahaya, kredensial dicuri',
    uraian_kerentanan: 'Autentikasi dua faktor (MFA) belum diimplementasikan pada email server',
    uraian_dampak: 'Akses tidak sah ke email karyawan, kebocoran informasi rahasia perusahaan',
    arah_dampak: 'negatif',
    uraian_pelaksanaan_rencana_kerja: null,
    uraian_penerapan_sop_kontrol: 'Belum ada SOP khusus untuk keamanan email dan autentikasi',
    dibuat_pada: '2026-11-02T10:00:00Z',
    diperbarui_pada: '2026-11-18T15:00:00Z',
  },
  {
    id: 3,
    asesmen_id: 2,
    asesmen_nama: 'ASM-2026-002',
    ancaman_id: 3,
    ancaman_nama: 'Kebocoran Data Internal',
    sumber_ancaman_id: 1,
    sumber_ancaman_nama: 'Karyawan Internal',
    kerentanan_id: 3,
    kerentanan_nama: 'Enkripsi Database Lemah',
    pemilik_risiko_id: 7,
    pemilik_risiko_nama: 'Kepala Bidang Keuangan',
    dibuat_oleh: 1,
    dibuat_oleh_nama: 'Budi Santoso',
    kode: 'RISK-ASM002-01',
    skenario: 'Karyawan dengan akses database mengekstrak data keuangan tanpa otorisasi',
    uraian_kerentanan: 'Database tidak dienkripsi dengan baik, kontrol akses lemah',
    uraian_dampak: 'Kehilangan data keuangan sensitif, potensi penyalahgunaan data',
    arah_dampak: 'negatif',
    uraian_pelaksanaan_rencana_kerja: null,
    uraian_penerapan_sop_kontrol: 'SOP akses database ada namun enforcement lemah',
    dibuat_pada: '2026-11-15T11:00:00Z',
    diperbarui_pada: '2026-11-15T11:00:00Z',
  },
];

let nextRisikoId = MOCK_RISIKO.length + 1;

// ─── Mock Data: Risk-Asset Relationships ─────────────────────────────────────

let MOCK_RISIKO_ASET: RisikoAset[] = [
  {
    id: 1,
    risiko_id: 1,
    aset_id: 2,
    kode_aset_snapshot: 'AST-002',
    nama_aset_snapshot: 'Server Database MySQL',
    kategori_snapshot: 'Hardware',
    klasifikasi_snapshot: 'K:5/I:5/A:5',
    unit_pemilik_snapshot: 'Bidang TI',
    lokasi_snapshot: 'Data Center',
    kondisi_snapshot: 'Baik',
    direkam_pada: '2026-11-01T09:00:00Z',
  },
  {
    id: 2,
    risiko_id: 1,
    aset_id: 8,
    kode_aset_snapshot: 'AST-008',
    nama_aset_snapshot: 'Email Server',
    kategori_snapshot: 'Software',
    klasifikasi_snapshot: 'K:4/I:4/A:4',
    unit_pemilik_snapshot: 'Bidang TI',
    lokasi_snapshot: 'Data Center',
    kondisi_snapshot: 'Baik',
    direkam_pada: '2026-11-01T09:00:00Z',
  },
  {
    id: 3,
    risiko_id: 2,
    aset_id: 8,
    kode_aset_snapshot: 'AST-008',
    nama_aset_snapshot: 'Email Server',
    kategori_snapshot: 'Software',
    klasifikasi_snapshot: 'K:4/I:4/A:4',
    unit_pemilik_snapshot: 'Bidang TI',
    lokasi_snapshot: 'Data Center',
    kondisi_snapshot: 'Baik',
    direkam_pada: '2026-11-02T10:00:00Z',
  },
  {
    id: 4,
    risiko_id: 3,
    aset_id: 4,
    kode_aset_snapshot: 'AST-004',
    nama_aset_snapshot: 'Sistem Informasi Keuangan',
    kategori_snapshot: 'Software',
    klasifikasi_snapshot: 'K:5/I:5/A:4',
    unit_pemilik_snapshot: 'Bidang Keuangan',
    lokasi_snapshot: 'Server Utama',
    kondisi_snapshot: 'Baik',
    direkam_pada: '2026-11-15T11:00:00Z',
  },
];

let nextRisikoAsetId = MOCK_RISIKO_ASET.length + 1;

// ─── Mock Data: Risk Assessments (MOCK MATRIX CONFIGURATION) ─────────────────

let MOCK_PENILAIAN: PenilaianRisiko[] = [
  {
    id: 1,
    risiko_id: 1,
    matriks_id: 1,
    dinilai_oleh: 5,
    dinilai_oleh_nama: 'Kepala Bidang TI',
    jenis_penilaian: 'initial',
    kemungkinan: 4,
    dampak: 5,
    skor_risiko: 20,
    tingkat_risiko_id: 1,
    tingkat_risiko_nama: 'Ekstrem',
    tingkat_risiko_warna: '#762127',
    alasan_kemungkinan: 'Patch management tidak berjalan dengan baik, serangan malware semakin sering terjadi',
    alasan_dampak: 'Server aplikasi adalah aset kritikal, downtime berdampak pada seluruh operasional organisasi',
    kesimpulan: 'Risiko sangat tinggi dan memerlukan mitigasi segera melalui penerapan patch management yang konsisten',
    dinilai_pada: '2026-11-20T14:00:00Z',
  },
  {
    id: 2,
    risiko_id: 2,
    matriks_id: 1,
    dinilai_oleh: 5,
    dinilai_oleh_nama: 'Kepala Bidang TI',
    jenis_penilaian: 'initial',
    kemungkinan: 3,
    dampak: 5,
    skor_risiko: 15,
    tingkat_risiko_id: 2,
    tingkat_risiko_nama: 'Tinggi',
    tingkat_risiko_warna: '#A65316',
    alasan_kemungkinan: 'Serangan phishing cukup sering terjadi pada organisasi serupa',
    alasan_dampak: 'Email berisi informasi sensitif, kebocoran dapat merugikan organisasi',
    kesimpulan: 'Perlu implementasi MFA secepat mungkin untuk mengurangi risiko',
    dinilai_pada: '2026-11-18T15:30:00Z',
  },
  {
    id: 3,
    risiko_id: 3,
    matriks_id: 1,
    dinilai_oleh: 7,
    dinilai_oleh_nama: 'Kepala Bidang Keuangan',
    jenis_penilaian: 'initial',
    kemungkinan: 2,
    dampak: 4,
    skor_risiko: 8,
    tingkat_risiko_id: 3,
    tingkat_risiko_nama: 'Sedang',
    tingkat_risiko_warna: '#8A6B00',
    alasan_kemungkinan: 'Kontrol akses sudah ada meskipun belum optimal',
    alasan_dampak: 'Data keuangan sensitif, kebocoran berpotensi merugikan',
    kesimpulan: 'Perkuat enkripsi database dan monitoring akses',
    dinilai_pada: '2026-11-15T16:00:00Z',
  },
];

let nextPenilaianId = MOCK_PENILAIAN.length + 1;

// ─── Helper Functions ────────────────────────────────────────────────────────

function delay(ms = 500) {
  return new Promise((r) => setTimeout(r, ms));
}

function calculateSummary(): AsesmenSummary {
  return {
    totalAsesmen: MOCK_ASESMEN.length,
    draft: MOCK_ASESMEN.filter(a => a.status === 'draft').length,
    active: MOCK_ASESMEN.filter(a => a.status === 'active').length,
    completed: MOCK_ASESMEN.filter(a => a.status === 'completed').length,
  };
}

// ─── Services ────────────────────────────────────────────────────────────────

export const asesmenService = {
  /**
   * Get assessment summary statistics
   */
  async getSummary(): Promise<AsesmenSummary> {
    await delay(300);
    return calculateSummary();
  },

  /**
   * List assessments with filtering and pagination
   */
  async list(params?: {
    q?: string;
    status?: string;
    page?: number;
  }): Promise<PaginatedResponse<AsesmenRisiko>> {
    await delay();
    const perPage = 10;
    const page = params?.page ?? 1;

    let filtered = [...MOCK_ASESMEN];

    if (params?.q) {
      const q = params.q.toLowerCase();
      filtered = filtered.filter(a =>
        a.kode.toLowerCase().includes(q) ||
        a.nama.toLowerCase().includes(q)
      );
    }

    if (params?.status) {
      filtered = filtered.filter(a => a.status === params.status);
    }

    const total = filtered.length;
    const lastPage = Math.max(1, Math.ceil(total / perPage));
    const from = (page - 1) * perPage;
    const data = filtered.slice(from, from + perPage);

    return {
      data,
      links: { first: null, last: null, prev: null, next: null },
      meta: {
        current_page: page,
        from: data.length > 0 ? from + 1 : null,
        last_page: lastPage,
        per_page: perPage,
        to: data.length > 0 ? from + data.length : null,
        total,
        path: '/api/v1/asesmen',
      },
    };
  },

  /**
   * Get single assessment by ID
   */
  async get(id: number): Promise<AsesmenRisiko> {
    await delay(300);
    const asesmen = MOCK_ASESMEN.find(a => a.id === id);
    if (!asesmen) throw new Error('Asesmen tidak ditemukan.');
    return asesmen;
  },

  /**
   * Create new assessment
   */
  async create(payload: Omit<AsesmenRisiko, 'id' | 'dibuat_pada' | 'diperbarui_pada'>): Promise<AsesmenRisiko> {
    await delay(600);
    const now = new Date().toISOString();
    const newAsesmen: AsesmenRisiko = {
      ...payload,
      id: nextAsesmenId++,
      dibuat_pada: now,
      diperbarui_pada: now,
    };
    MOCK_ASESMEN.push(newAsesmen);
    return newAsesmen;
  },

  /**
   * Update existing assessment
   */
  async update(id: number, payload: Partial<Omit<AsesmenRisiko, 'id' | 'dibuat_pada'>>): Promise<AsesmenRisiko> {
    await delay(600);
    const index = MOCK_ASESMEN.findIndex(a => a.id === id);
    if (index === -1) throw new Error('Asesmen tidak ditemukan.');
    MOCK_ASESMEN[index] = {
      ...MOCK_ASESMEN[index],
      ...payload,
      diperbarui_pada: new Date().toISOString(),
    };
    return MOCK_ASESMEN[index];
  },

  /**
   * Delete assessment
   */
  async delete(id: number): Promise<void> {
    await delay(400);
    const index = MOCK_ASESMEN.findIndex(a => a.id === id);
    if (index === -1) throw new Error('Asesmen tidak ditemukan.');
    MOCK_ASESMEN = MOCK_ASESMEN.filter(a => a.id !== id);
  },
};

export const risikoService = {
  /**
   * List risks by assessment ID
   */
  async listByAsesmen(asesmen_id: number): Promise<Risiko[]> {
    await delay();
    return MOCK_RISIKO.filter(r => r.asesmen_id === asesmen_id);
  },

  /**
   * Get single risk by ID
   */
  async get(id: number): Promise<Risiko> {
    await delay(300);
    const risiko = MOCK_RISIKO.find(r => r.id === id);
    if (!risiko) throw new Error('Risiko tidak ditemukan.');
    return risiko;
  },

  /**
   * Create new risk
   */
  async create(payload: Omit<Risiko, 'id' | 'dibuat_pada' | 'diperbarui_pada'>): Promise<Risiko> {
    await delay(600);
    const now = new Date().toISOString();
    const newRisiko: Risiko = {
      ...payload,
      id: nextRisikoId++,
      dibuat_pada: now,
      diperbarui_pada: now,
    };
    MOCK_RISIKO.push(newRisiko);
    return newRisiko;
  },

  /**
   * Update existing risk
   */
  async update(id: number, payload: Partial<Omit<Risiko, 'id' | 'dibuat_pada'>>): Promise<Risiko> {
    await delay(600);
    const index = MOCK_RISIKO.findIndex(r => r.id === id);
    if (index === -1) throw new Error('Risiko tidak ditemukan.');
    MOCK_RISIKO[index] = {
      ...MOCK_RISIKO[index],
      ...payload,
      diperbarui_pada: new Date().toISOString(),
    };
    return MOCK_RISIKO[index];
  },

  /**
   * Delete risk
   */
  async delete(id: number): Promise<void> {
    await delay(400);
    MOCK_RISIKO = MOCK_RISIKO.filter(r => r.id !== id);
  },
};

export const risikoAsetService = {
  /**
   * List assets for a risk
   */
  async listByRisiko(risiko_id: number): Promise<RisikoAset[]> {
    await delay(300);
    return MOCK_RISIKO_ASET.filter(ra => ra.risiko_id === risiko_id);
  },

  /**
   * Add asset to risk (with snapshot)
   */
  async add(risiko_id: number, aset_id: number, snapshot: Omit<RisikoAset, 'id' | 'risiko_id' | 'aset_id'>): Promise<RisikoAset> {
    await delay(400);
    const newRisikoAset: RisikoAset = {
      ...snapshot,
      id: nextRisikoAsetId++,
      risiko_id,
      aset_id,
    };
    MOCK_RISIKO_ASET.push(newRisikoAset);
    return newRisikoAset;
  },

  /**
   * Remove asset from risk
   */
  async remove(id: number): Promise<void> {
    await delay(300);
    MOCK_RISIKO_ASET = MOCK_RISIKO_ASET.filter(ra => ra.id !== id);
  },
};

export const penilaianService = {
  /**
   * List assessments for a risk
   */
  async listByRisiko(risiko_id: number): Promise<PenilaianRisiko[]> {
    await delay(300);
    return MOCK_PENILAIAN.filter(p => p.risiko_id === risiko_id);
  },

  /**
   * Get single assessment
   */
  async get(id: number): Promise<PenilaianRisiko> {
    await delay(300);
    const penilaian = MOCK_PENILAIAN.find(p => p.id === id);
    if (!penilaian) throw new Error('Penilaian tidak ditemukan.');
    return penilaian;
  },

  /**
   * Create new assessment
   */
  async create(payload: Omit<PenilaianRisiko, 'id' | 'dinilai_pada'>): Promise<PenilaianRisiko> {
    await delay(600);
    const newPenilaian: PenilaianRisiko = {
      ...payload,
      id: nextPenilaianId++,
      dinilai_pada: new Date().toISOString(),
    };
    MOCK_PENILAIAN.push(newPenilaian);
    return newPenilaian;
  },

  /**
   * Update existing assessment
   */
  async update(id: number, payload: Partial<Omit<PenilaianRisiko, 'id'>>): Promise<PenilaianRisiko> {
    await delay(600);
    const index = MOCK_PENILAIAN.findIndex(p => p.id === id);
    if (index === -1) throw new Error('Penilaian tidak ditemukan.');
    MOCK_PENILAIAN[index] = { ...MOCK_PENILAIAN[index], ...payload };
    return MOCK_PENILAIAN[index];
  },

  /**
   * Delete assessment
   */
  async delete(id: number): Promise<void> {
    await delay(400);
    MOCK_PENILAIAN = MOCK_PENILAIAN.filter(p => p.id !== id);
  },
};

export const sumberAncamanService = {
  /**
   * List all threat sources
   */
  async list(): Promise<SumberAncaman[]> {
    await delay(300);
    return MOCK_SUMBER_ANCAMAN.filter(sa => sa.status === 'aktif');
  },

  /**
   * Get single threat source
   */
  async get(id: number): Promise<SumberAncaman> {
    await delay(200);
    const sumber = MOCK_SUMBER_ANCAMAN.find(sa => sa.id === id);
    if (!sumber) throw new Error('Sumber ancaman tidak ditemukan.');
    return sumber;
  },
};
