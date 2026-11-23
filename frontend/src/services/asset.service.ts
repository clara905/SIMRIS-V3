import type { Asset } from '@/types/risk.types';
import type { PaginatedResponse } from '@/types/common.types';

// Mock data
let MOCK_ASSETS: Asset[] = [
  { id: 1, kode: 'AST-001', nama: 'Server Aplikasi Utama', kategori: 'Hardware', pemilik: 'Bidang TI', lokasi: 'Data Center Lt.2', deskripsi: 'Server utama untuk aplikasi internal', nilai_aset: 150000000, kerahasiaan: 4, integritas: 5, ketersediaan: 5, status: 'aktif', unit_id: 1 },
  { id: 2, kode: 'AST-002', nama: 'Sistem Informasi Keuangan', kategori: 'Software', pemilik: 'Bidang Keuangan', lokasi: 'Server Utama', deskripsi: 'Aplikasi manajemen keuangan', nilai_aset: 80000000, kerahasiaan: 5, integritas: 5, ketersediaan: 4, status: 'aktif', unit_id: 2 },
  { id: 3, kode: 'AST-003', nama: 'Database Kepegawaian', kategori: 'Data', pemilik: 'Bidang SDM', lokasi: 'Server Database', deskripsi: 'Database data pegawai aktif', nilai_aset: 50000000, kerahasiaan: 5, integritas: 4, ketersediaan: 3, status: 'aktif', unit_id: 1 },
  { id: 4, kode: 'AST-004', nama: 'Jaringan LAN Internal', kategori: 'Jaringan', pemilik: 'Bidang TI', lokasi: 'Seluruh Gedung', deskripsi: 'Infrastruktur jaringan area lokal', nilai_aset: 200000000, kerahasiaan: 3, integritas: 4, ketersediaan: 5, status: 'aktif', unit_id: 1 },
  { id: 5, kode: 'AST-005', nama: 'Laptop Dinas Pimpinan', kategori: 'Hardware', pemilik: 'Kasatker', lokasi: 'Ruang Pimpinan', deskripsi: null, nilai_aset: 20000000, kerahasiaan: 4, integritas: 3, ketersediaan: 3, status: 'review', unit_id: null },
  { id: 6, kode: 'AST-006', nama: 'Email Server', kategori: 'Hardware', pemilik: 'Bidang TI', lokasi: 'Data Center', deskripsi: 'Server layanan email internal', nilai_aset: 75000000, kerahasiaan: 4, integritas: 4, ketersediaan: 4, status: 'aktif', unit_id: 1 },
  { id: 7, kode: 'AST-007', nama: 'Aplikasi Presensi', kategori: 'Software', pemilik: 'Bidang SDM', lokasi: 'Server Internal', deskripsi: 'Sistem absensi digital', nilai_aset: 30000000, kerahasiaan: 3, integritas: 4, ketersediaan: 4, status: 'aktif', unit_id: 2 },
  { id: 8, kode: 'AST-008', nama: 'UPS Data Center', kategori: 'Hardware', pemilik: 'Bidang TI', lokasi: 'Data Center', deskripsi: 'Uninterruptible Power Supply', nilai_aset: 60000000, kerahasiaan: 1, integritas: 2, ketersediaan: 5, status: 'nonaktif', unit_id: 1 },
];

let nextId = MOCK_ASSETS.length + 1;

function delay(ms = 500) {
  return new Promise((r) => setTimeout(r, ms));
}

export const assetService = {
  async list(params?: { q?: string; kategori?: string; status?: string; page?: number }): Promise<PaginatedResponse<Asset>> {
    await delay();
    const perPage = 10;
    const page = params?.page ?? 1;

    let filtered = [...MOCK_ASSETS];
    if (params?.q) {
      const q = params.q.toLowerCase();
      filtered = filtered.filter((a) => a.nama.toLowerCase().includes(q) || a.kode.toLowerCase().includes(q));
    }
    if (params?.kategori) filtered = filtered.filter((a) => a.kategori === params.kategori);
    if (params?.status) filtered = filtered.filter((a) => a.status === params.status);

    const total = filtered.length;
    const lastPage = Math.max(1, Math.ceil(total / perPage));
    const from = (page - 1) * perPage;
    const data = filtered.slice(from, from + perPage);

    return {
      data,
      links: { first: null, last: null, prev: null, next: null },
      meta: { current_page: page, from: from + 1, last_page: lastPage, per_page: perPage, to: from + data.length, total, path: '/api/v1/assets' },
    };
  },

  async get(id: number): Promise<Asset> {
    await delay(300);
    const asset = MOCK_ASSETS.find((a) => a.id === id);
    if (!asset) throw new Error('Aset tidak ditemukan.');
    return asset;
  },

  async create(payload: Omit<Asset, 'id'>): Promise<Asset> {
    await delay(600);
    const newAsset: Asset = { ...payload, id: nextId++ };
    MOCK_ASSETS.push(newAsset);
    return newAsset;
  },

  async update(id: number, payload: Partial<Omit<Asset, 'id'>>): Promise<Asset> {
    await delay(600);
    const index = MOCK_ASSETS.findIndex((a) => a.id === id);
    if (index === -1) throw new Error('Aset tidak ditemukan.');
    MOCK_ASSETS[index] = { ...MOCK_ASSETS[index], ...payload };
    return MOCK_ASSETS[index];
  },

  async delete(id: number): Promise<void> {
    await delay(400);
    MOCK_ASSETS = MOCK_ASSETS.filter((a) => a.id !== id);
  },
};
