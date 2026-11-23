import type { Asset } from '@/types/risk.types';
import type { PaginatedResponse } from '@/types/common.types';

// ─── Summary Stats ───────────────────────────────────────────────────────────

export interface AssetSummary {
  totalAset: number;
  asetAktif: number;
  asetPemeliharaan: number;
  asetTidakAktif: number;
}

// ─── Mock Data ───────────────────────────────────────────────────────────────

let MOCK_ASSETS: Asset[] = [
  { 
    id: 1, 
    kode: 'AST-001', 
    nama: 'Laptop Dell Latitude 5420', 
    kategori: 'Hardware', 
    pemilik: 'Bidang TI', 
    lokasi: 'Ruang Kerja IT', 
    deskripsi: 'Laptop kerja untuk staff IT', 
    nilai_aset: 15000000, 
    tanggal_perolehan: '2024-01-15',
    kondisi: 'Baik',
    status: 'Aktif',
    pic: 'Budi Santoso',
    kerahasiaan: 3, 
    integritas: 3, 
    ketersediaan: 4, 
    unit_id: 1 
  },
  { 
    id: 2, 
    kode: 'AST-002', 
    nama: 'Server Database MySQL', 
    kategori: 'Hardware', 
    pemilik: 'Bidang TI', 
    lokasi: 'Data Center', 
    deskripsi: 'Server untuk database aplikasi internal', 
    nilai_aset: 150000000, 
    tanggal_perolehan: '2023-06-10',
    kondisi: 'Baik',
    status: 'Aktif',
    pic: 'Ahmad Fauzi',
    kerahasiaan: 5, 
    integritas: 5, 
    ketersediaan: 5, 
    unit_id: 1 
  },
  { 
    id: 3, 
    kode: 'AST-003', 
    nama: 'Printer Epson L3110', 
    kategori: 'Hardware', 
    pemilik: 'Bidang Administrasi', 
    lokasi: 'Ruang Administrasi', 
    deskripsi: null, 
    nilai_aset: 2500000, 
    tanggal_perolehan: '2024-03-20',
    kondisi: 'Rusak Ringan',
    status: 'Pemeliharaan',
    pic: 'Siti Nurhaliza',
    kerahasiaan: 1, 
    integritas: 2, 
    ketersediaan: 2, 
    unit_id: 2 
  },
  { 
    id: 4, 
    kode: 'AST-004', 
    nama: 'Sistem Informasi Keuangan', 
    kategori: 'Software', 
    pemilik: 'Bidang Keuangan', 
    lokasi: 'Server Utama', 
    deskripsi: 'Aplikasi manajemen keuangan dan akuntansi', 
    nilai_aset: 80000000, 
    tanggal_perolehan: '2023-01-05',
    kondisi: 'Baik',
    status: 'Aktif',
    pic: 'Rina Wijaya',
    kerahasiaan: 5, 
    integritas: 5, 
    ketersediaan: 4, 
    unit_id: 2 
  },
  { 
    id: 5, 
    kode: 'AST-005', 
    nama: 'Database Kepegawaian', 
    kategori: 'Data', 
    pemilik: 'Bidang SDM', 
    lokasi: 'Server Database', 
    deskripsi: 'Database data pegawai aktif dan arsip', 
    nilai_aset: 50000000, 
    tanggal_perolehan: '2022-11-10',
    kondisi: 'Baik',
    status: 'Aktif',
    pic: 'Dedi Kurniawan',
    kerahasiaan: 5, 
    integritas: 4, 
    ketersediaan: 3, 
    unit_id: 1 
  },
  { 
    id: 6, 
    kode: 'AST-006', 
    nama: 'Jaringan LAN Internal', 
    kategori: 'Jaringan', 
    pemilik: 'Bidang TI', 
    lokasi: 'Seluruh Gedung', 
    deskripsi: 'Infrastruktur jaringan area lokal', 
    nilai_aset: 200000000, 
    tanggal_perolehan: '2022-08-15',
    kondisi: 'Baik',
    status: 'Aktif',
    pic: 'Ahmad Fauzi',
    kerahasiaan: 3, 
    integritas: 4, 
    ketersediaan: 5, 
    unit_id: 1 
  },
  { 
    id: 7, 
    kode: 'AST-007', 
    nama: 'Laptop HP ProBook 450', 
    kategori: 'Hardware', 
    pemilik: 'Kasatker', 
    lokasi: 'Ruang Pimpinan', 
    deskripsi: 'Laptop dinas pimpinan', 
    nilai_aset: 20000000, 
    tanggal_perolehan: '2024-02-01',
    kondisi: 'Baik',
    status: 'Aktif',
    pic: null,
    kerahasiaan: 4, 
    integritas: 3, 
    ketersediaan: 3, 
    unit_id: null 
  },
  { 
    id: 8, 
    kode: 'AST-008', 
    nama: 'Email Server', 
    kategori: 'Software', 
    pemilik: 'Bidang TI', 
    lokasi: 'Data Center', 
    deskripsi: 'Server layanan email internal', 
    nilai_aset: 75000000, 
    tanggal_perolehan: '2023-04-12',
    kondisi: 'Baik',
    status: 'Aktif',
    pic: 'Ahmad Fauzi',
    kerahasiaan: 4, 
    integritas: 4, 
    ketersediaan: 4, 
    unit_id: 1 
  },
  { 
    id: 9, 
    kode: 'AST-009', 
    nama: 'UPS Data Center 10KVA', 
    kategori: 'Hardware', 
    pemilik: 'Bidang TI', 
    lokasi: 'Data Center', 
    deskripsi: 'Uninterruptible Power Supply untuk data center', 
    nilai_aset: 60000000, 
    tanggal_perolehan: '2021-05-20',
    kondisi: 'Rusak Berat',
    status: 'Tidak Aktif',
    pic: 'Budi Santoso',
    kerahasiaan: 1, 
    integritas: 2, 
    ketersediaan: 5, 
    unit_id: 1 
  },
  { 
    id: 10, 
    kode: 'AST-010', 
    nama: 'Aplikasi Presensi Digital', 
    kategori: 'Software', 
    pemilik: 'Bidang SDM', 
    lokasi: 'Server Internal', 
    deskripsi: 'Sistem absensi digital berbasis fingerprint', 
    nilai_aset: 30000000, 
    tanggal_perolehan: '2023-09-01',
    kondisi: 'Baik',
    status: 'Aktif',
    pic: 'Dedi Kurniawan',
    kerahasiaan: 3, 
    integritas: 4, 
    ketersediaan: 4, 
    unit_id: 2 
  },
  { 
    id: 11, 
    kode: 'AST-011', 
    nama: 'Router Cisco 2900 Series', 
    kategori: 'Jaringan', 
    pemilik: 'Bidang TI', 
    lokasi: 'Ruang Network', 
    deskripsi: 'Router utama untuk koneksi internet', 
    nilai_aset: 45000000, 
    tanggal_perolehan: '2022-10-15',
    kondisi: 'Baik',
    status: 'Aktif',
    pic: 'Ahmad Fauzi',
    kerahasiaan: 3, 
    integritas: 4, 
    ketersediaan: 5, 
    unit_id: 1 
  },
  { 
    id: 12, 
    kode: 'AST-012', 
    nama: 'AC Data Center 5PK', 
    kategori: 'Fasilitas', 
    pemilik: 'Bidang TI', 
    lokasi: 'Data Center', 
    deskripsi: 'Air conditioning untuk ruang server', 
    nilai_aset: 35000000, 
    tanggal_perolehan: '2022-07-01',
    kondisi: 'Rusak Ringan',
    status: 'Pemeliharaan',
    pic: 'Budi Santoso',
    kerahasiaan: 1, 
    integritas: 1, 
    ketersediaan: 4, 
    unit_id: 1 
  },
];

let nextId = MOCK_ASSETS.length + 1;

// ─── Helper Functions ────────────────────────────────────────────────────────

function delay(ms = 500) {
  return new Promise((r) => setTimeout(r, ms));
}

function calculateSummary(): AssetSummary {
  return {
    totalAset: MOCK_ASSETS.length,
    asetAktif: MOCK_ASSETS.filter(a => a.status === 'Aktif').length,
    asetPemeliharaan: MOCK_ASSETS.filter(a => a.status === 'Pemeliharaan').length,
    asetTidakAktif: MOCK_ASSETS.filter(a => a.status === 'Tidak Aktif').length,
  };
}

// ─── Service ─────────────────────────────────────────────────────────────────

export const assetService = {
  /**
   * Get asset summary statistics
   */
  async getSummary(): Promise<AssetSummary> {
    await delay(300);
    return calculateSummary();
  },

  /**
   * List assets with filtering and pagination
   */
  async list(params?: { 
    q?: string; 
    kategori?: string; 
    status?: string; 
    kondisi?: string;
    page?: number;
  }): Promise<PaginatedResponse<Asset>> {
    await delay();
    const perPage = 10;
    const page = params?.page ?? 1;

    let filtered = [...MOCK_ASSETS];
    
    // Search by name or code
    if (params?.q) {
      const q = params.q.toLowerCase();
      filtered = filtered.filter((a) => 
        a.nama.toLowerCase().includes(q) || 
        a.kode.toLowerCase().includes(q)
      );
    }
    
    // Filter by category
    if (params?.kategori) {
      filtered = filtered.filter((a) => a.kategori === params.kategori);
    }
    
    // Filter by status
    if (params?.status) {
      filtered = filtered.filter((a) => a.status === params.status);
    }

    // Filter by condition
    if (params?.kondisi) {
      filtered = filtered.filter((a) => a.kondisi === params.kondisi);
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
        path: '/api/v1/assets' 
      },
    };
  },

  /**
   * Get single asset by ID
   */
  async get(id: number): Promise<Asset> {
    await delay(300);
    const asset = MOCK_ASSETS.find((a) => a.id === id);
    if (!asset) throw new Error('Aset tidak ditemukan.');
    return asset;
  },

  /**
   * Create new asset
   */
  async create(payload: Omit<Asset, 'id'>): Promise<Asset> {
    await delay(600);
    
    // Check for duplicate kode
    const exists = MOCK_ASSETS.find(a => a.kode === payload.kode);
    if (exists) {
      throw new Error(`Kode aset "${payload.kode}" sudah digunakan.`);
    }
    
    const newAsset: Asset = { ...payload, id: nextId++ };
    MOCK_ASSETS.push(newAsset);
    return newAsset;
  },

  /**
   * Update existing asset
   */
  async update(id: number, payload: Partial<Omit<Asset, 'id'>>): Promise<Asset> {
    await delay(600);
    const index = MOCK_ASSETS.findIndex((a) => a.id === id);
    if (index === -1) throw new Error('Aset tidak ditemukan.');
    
    // Check for duplicate kode if updating kode
    if (payload.kode && payload.kode !== MOCK_ASSETS[index].kode) {
      const exists = MOCK_ASSETS.find(a => a.kode === payload.kode && a.id !== id);
      if (exists) {
        throw new Error(`Kode aset "${payload.kode}" sudah digunakan.`);
      }
    }
    
    MOCK_ASSETS[index] = { ...MOCK_ASSETS[index], ...payload };
    return MOCK_ASSETS[index];
  },

  /**
   * Delete asset
   */
  async delete(id: number): Promise<void> {
    await delay(400);
    const index = MOCK_ASSETS.findIndex((a) => a.id === id);
    if (index === -1) throw new Error('Aset tidak ditemukan.');
    MOCK_ASSETS = MOCK_ASSETS.filter((a) => a.id !== id);
  },
};
