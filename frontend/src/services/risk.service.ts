import type { RiskAssessment, RiskTreatment } from '@/types/risk.types';
import type { PaginatedResponse } from '@/types/common.types';
import type { RiskLevel } from '@/types/common.types';

function calculateRiskLevel(skor: number): RiskLevel {
  if (skor >= 20) return 'critical';
  if (skor >= 12) return 'high';
  if (skor >= 6) return 'medium';
  return 'low';
}

let MOCK_ASSESSMENTS: RiskAssessment[] = [
  { id: 1, aset_id: 1, aset_nama: 'Server Aplikasi Utama', ancaman_id: 2, ancaman_nama: 'Malware / Ransomware', kerentanan_id: 2, kerentanan_nama: 'Patch Keamanan Belum Diterapkan', kemungkinan: 4, dampak: 5, skor_risiko: 20, level_risiko: 'critical', tanggal_penilaian: '2026-11-01', penilai: 'Kepala Bidang TI', status: 'disetujui' },
  { id: 2, aset_id: 2, aset_nama: 'Sistem Informasi Keuangan', ancaman_id: 1, ancaman_nama: 'Serangan Phishing', kerentanan_id: 4, kerentanan_nama: 'Tidak Ada MFA pada Email Server', kemungkinan: 3, dampak: 5, skor_risiko: 15, level_risiko: 'high', tanggal_penilaian: '2026-11-02', penilai: 'Kepala Bidang TI', status: 'disetujui' },
  { id: 3, aset_id: 3, aset_nama: 'Database Kepegawaian', ancaman_id: 3, ancaman_nama: 'Kebocoran Data Internal', kerentanan_id: 3, kerentanan_nama: 'Enkripsi Database Lemah', kemungkinan: 3, dampak: 4, skor_risiko: 12, level_risiko: 'high', tanggal_penilaian: '2026-11-05', penilai: 'Admin Satker', status: 'draft' },
  { id: 4, aset_id: 4, aset_nama: 'Jaringan LAN Internal', ancaman_id: 7, ancaman_nama: 'DDoS Attack', kerentanan_id: 6, kerentanan_nama: 'Firewall Rule Terlalu Permisif', kemungkinan: 2, dampak: 4, skor_risiko: 8, level_risiko: 'medium', tanggal_penilaian: '2026-11-08', penilai: 'Admin Satker', status: 'disetujui' },
  { id: 5, aset_id: 5, aset_nama: 'Laptop Dinas Pimpinan', ancaman_id: 1, ancaman_nama: 'Serangan Phishing', kerentanan_id: 1, kerentanan_nama: 'Kata Sandi Default Tidak Diganti', kemungkinan: 3, dampak: 3, skor_risiko: 9, level_risiko: 'medium', tanggal_penilaian: '2026-11-10', penilai: 'Admin Satker', status: 'draft' },
];
let nextAssessmentId = MOCK_ASSESSMENTS.length + 1;

let MOCK_TREATMENTS: RiskTreatment[] = [
  { id: 1, penilaian_risiko_id: 1, risiko_ringkasan: 'Malware pada Server Aplikasi', strategi: 'mitigasi', rencana: 'Segera terapkan patch keamanan OS dan aktifkan antivirus enterprise', penanggung_jawab: 'Kepala Bidang TI', tenggat_waktu: '2026-12-01', status: 'sedang_berjalan', progres: 60, catatan: 'Patch sudah diuji di lingkungan staging' },
  { id: 2, penilaian_risiko_id: 2, risiko_ringkasan: 'Phishing pada Sistem Keuangan', strategi: 'mitigasi', rencana: 'Aktifkan MFA untuk semua pengguna sistem keuangan', penanggung_jawab: 'Admin Satker', tenggat_waktu: '2026-11-30', status: 'sedang_berjalan', progres: 40, catatan: null },
  { id: 3, penilaian_risiko_id: 4, risiko_ringkasan: 'DDoS pada Jaringan LAN', strategi: 'transfer', rencana: 'Asuransikan aset jaringan dan pasang layanan anti-DDoS pihak ketiga', penanggung_jawab: 'Kepala Satker', tenggat_waktu: '2026-12-15', status: 'belum_dimulai', progres: 0, catatan: null },
];
let nextTreatmentId = MOCK_TREATMENTS.length + 1;

function delay(ms = 500) { return new Promise((r) => setTimeout(r, ms)); }

export const riskService = {
  // Penilaian
  async listAssessments(params?: { q?: string; level?: string; status?: string; page?: number }): Promise<PaginatedResponse<RiskAssessment>> {
    await delay();
    const perPage = 10;
    const page = params?.page ?? 1;
    let filtered = [...MOCK_ASSESSMENTS];
    if (params?.q) { const q = params.q.toLowerCase(); filtered = filtered.filter((r) => r.aset_nama.toLowerCase().includes(q) || r.ancaman_nama.toLowerCase().includes(q)); }
    if (params?.level) filtered = filtered.filter((r) => r.level_risiko === params.level);
    if (params?.status) filtered = filtered.filter((r) => r.status === params.status);
    const total = filtered.length;
    const lastPage = Math.max(1, Math.ceil(total / perPage));
    const from = (page - 1) * perPage;
    const data = filtered.slice(from, from + perPage);
    return { data, links: { first: null, last: null, prev: null, next: null }, meta: { current_page: page, from: from + 1, last_page: lastPage, per_page: perPage, to: from + data.length, total, path: '' } };
  },

  async createAssessment(payload: Omit<RiskAssessment, 'id' | 'skor_risiko' | 'level_risiko'>): Promise<RiskAssessment> {
    await delay(600);
    const skor = payload.kemungkinan * payload.dampak;
    const item: RiskAssessment = { ...payload, id: nextAssessmentId++, skor_risiko: skor, level_risiko: calculateRiskLevel(skor) };
    MOCK_ASSESSMENTS.push(item);
    return item;
  },

  async updateAssessment(id: number, payload: Partial<Omit<RiskAssessment, 'id' | 'skor_risiko' | 'level_risiko'>>): Promise<RiskAssessment> {
    await delay(600);
    const i = MOCK_ASSESSMENTS.findIndex((x) => x.id === id);
    if (i === -1) throw new Error('Penilaian tidak ditemukan.');
    const updated = { ...MOCK_ASSESSMENTS[i], ...payload };
    if (payload.kemungkinan !== undefined || payload.dampak !== undefined) {
      updated.skor_risiko = updated.kemungkinan * updated.dampak;
      updated.level_risiko = calculateRiskLevel(updated.skor_risiko);
    }
    MOCK_ASSESSMENTS[i] = updated;
    return updated;
  },

  async deleteAssessment(id: number): Promise<void> {
    await delay(400);
    MOCK_ASSESSMENTS = MOCK_ASSESSMENTS.filter((x) => x.id !== id);
  },

  // Penanganan
  async listTreatments(params?: { q?: string; status?: string; page?: number }): Promise<PaginatedResponse<RiskTreatment>> {
    await delay();
    const perPage = 10;
    const page = params?.page ?? 1;
    let filtered = [...MOCK_TREATMENTS];
    if (params?.q) { const q = params.q.toLowerCase(); filtered = filtered.filter((t) => t.risiko_ringkasan.toLowerCase().includes(q)); }
    if (params?.status) filtered = filtered.filter((t) => t.status === params.status);
    const total = filtered.length;
    const lastPage = Math.max(1, Math.ceil(total / perPage));
    const from = (page - 1) * perPage;
    const data = filtered.slice(from, from + perPage);
    return { data, links: { first: null, last: null, prev: null, next: null }, meta: { current_page: page, from: from + 1, last_page: lastPage, per_page: perPage, to: from + data.length, total, path: '' } };
  },

  async createTreatment(payload: Omit<RiskTreatment, 'id'>): Promise<RiskTreatment> {
    await delay(600);
    const item: RiskTreatment = { ...payload, id: nextTreatmentId++ };
    MOCK_TREATMENTS.push(item);
    return item;
  },

  async updateTreatment(id: number, payload: Partial<Omit<RiskTreatment, 'id'>>): Promise<RiskTreatment> {
    await delay(600);
    const i = MOCK_TREATMENTS.findIndex((x) => x.id === id);
    if (i === -1) throw new Error('Penanganan tidak ditemukan.');
    MOCK_TREATMENTS[i] = { ...MOCK_TREATMENTS[i], ...payload };
    return MOCK_TREATMENTS[i];
  },

  async deleteTreatment(id: number): Promise<void> {
    await delay(400);
    MOCK_TREATMENTS = MOCK_TREATMENTS.filter((x) => x.id !== id);
  },
};
