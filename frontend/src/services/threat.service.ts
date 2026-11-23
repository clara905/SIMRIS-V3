import type { Threat } from '@/types/risk.types';
import type { PaginatedResponse } from '@/types/common.types';

let MOCK_THREATS: Threat[] = [
  { id: 1, kode: 'THR-001', nama: 'Serangan Phishing', kategori: 'Manusia', sumber: 'Eksternal', deskripsi: 'Upaya penipuan melalui email palsu', status: 'aktif' },
  { id: 2, kode: 'THR-002', nama: 'Malware / Ransomware', kategori: 'Teknis', sumber: 'Eksternal', deskripsi: 'Perangkat lunak berbahaya yang mengenkripsi data', status: 'aktif' },
  { id: 3, kode: 'THR-003', nama: 'Kebocoran Data Internal', kategori: 'Manusia', sumber: 'Internal', deskripsi: 'Pegawai membocorkan data sensitif', status: 'aktif' },
  { id: 4, kode: 'THR-004', nama: 'Pemadaman Listrik', kategori: 'Lingkungan', sumber: 'Lingkungan', deskripsi: 'Gangguan aliran listrik yang tidak terduga', status: 'aktif' },
  { id: 5, kode: 'THR-005', nama: 'SQL Injection', kategori: 'Teknis', sumber: 'Eksternal', deskripsi: 'Injeksi perintah SQL pada form input', status: 'aktif' },
  { id: 6, kode: 'THR-006', nama: 'Bencana Alam', kategori: 'Lingkungan', sumber: 'Lingkungan', deskripsi: 'Gempa bumi, banjir, atau kebakaran', status: 'aktif' },
  { id: 7, kode: 'THR-007', nama: 'DDoS Attack', kategori: 'Teknis', sumber: 'Eksternal', deskripsi: 'Serangan penolakan layanan terdistribusi', status: 'nonaktif' },
];
let nextId = MOCK_THREATS.length + 1;

function delay(ms = 500) { return new Promise((r) => setTimeout(r, ms)); }

export const threatService = {
  async list(params?: { q?: string; kategori?: string; status?: string; page?: number }): Promise<PaginatedResponse<Threat>> {
    await delay();
    const perPage = 10;
    const page = params?.page ?? 1;
    let filtered = [...MOCK_THREATS];
    if (params?.q) { const q = params.q.toLowerCase(); filtered = filtered.filter((t) => t.nama.toLowerCase().includes(q)); }
    if (params?.kategori) filtered = filtered.filter((t) => t.kategori === params.kategori);
    if (params?.status) filtered = filtered.filter((t) => t.status === params.status);
    const total = filtered.length;
    const lastPage = Math.max(1, Math.ceil(total / perPage));
    const from = (page - 1) * perPage;
    const data = filtered.slice(from, from + perPage);
    return { data, links: { first: null, last: null, prev: null, next: null }, meta: { current_page: page, from: from + 1, last_page: lastPage, per_page: perPage, to: from + data.length, total, path: '' } };
  },

  async get(id: number): Promise<Threat> {
    await delay(300);
    const t = MOCK_THREATS.find((x) => x.id === id);
    if (!t) throw new Error('Ancaman tidak ditemukan.');
    return t;
  },

  async create(payload: Omit<Threat, 'id'>): Promise<Threat> {
    await delay(600);
    const item: Threat = { ...payload, id: nextId++ };
    MOCK_THREATS.push(item);
    return item;
  },

  async update(id: number, payload: Partial<Omit<Threat, 'id'>>): Promise<Threat> {
    await delay(600);
    const i = MOCK_THREATS.findIndex((x) => x.id === id);
    if (i === -1) throw new Error('Ancaman tidak ditemukan.');
    MOCK_THREATS[i] = { ...MOCK_THREATS[i], ...payload };
    return MOCK_THREATS[i];
  },

  async delete(id: number): Promise<void> {
    await delay(400);
    MOCK_THREATS = MOCK_THREATS.filter((x) => x.id !== id);
  },
};
