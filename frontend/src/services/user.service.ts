import type { AuthUser } from '@/types/auth.types';
import type { PaginatedResponse } from '@/types/common.types';
import api from './api';

export interface UserListItem {
  id: number;
  username: string;
  email: string;
  aktif: boolean;
  role?: { kode: string; nama: string };
  pegawai?: { nip: string; nama: string } | null;
}

// Mock data for the user list
const MOCK_USERS: UserListItem[] = [
  { id: 1, username: 'superadmin', email: 'admin@simris.test', aktif: true, role: { kode: 'SUPER_ADMIN', nama: 'Super Admin' }, pegawai: { nip: '199001012020011001', nama: 'Admin Sistem' } },
  { id: 2, username: 'kasatker01', email: 'kasatker@simris.test', aktif: true, role: { kode: 'KASATKER', nama: 'Kepala Satuan Kerja' }, pegawai: { nip: '197505152000031002', nama: 'Kepala Satker' } },
  { id: 3, username: 'adminsatker01', email: 'adminsatker@simris.test', aktif: true, role: { kode: 'ADMIN_SATKER', nama: 'Admin Satker' }, pegawai: { nip: '198803042015041003', nama: 'Budi Santoso' } },
  { id: 4, username: 'kabid.ti', email: 'kabid.ti@simris.test', aktif: true, role: { kode: 'KABID', nama: 'Kepala Bidang' }, pegawai: { nip: '199106112019031004', nama: 'Rina Wulandari' } },
  { id: 5, username: 'staff.ti01', email: 'staff01@simris.test', aktif: false, role: { kode: 'STAFF', nama: 'Staff' }, pegawai: { nip: '199507202021011005', nama: 'Doni Prasetyo' } },
];

function delay(ms = 500) { return new Promise((r) => setTimeout(r, ms)); }

export const userService = {
  async list(params?: { q?: string; aktif?: boolean; page?: number }): Promise<PaginatedResponse<UserListItem>> {
    await delay();
    const perPage = 10;
    const page = params?.page ?? 1;
    let filtered = [...MOCK_USERS];
    if (params?.q) { const q = params.q.toLowerCase(); filtered = filtered.filter((u) => u.username.toLowerCase().includes(q) || u.email.toLowerCase().includes(q) || u.pegawai?.nama.toLowerCase().includes(q)); }
    if (params?.aktif !== undefined) filtered = filtered.filter((u) => u.aktif === params.aktif);
    const total = filtered.length;
    const lastPage = Math.max(1, Math.ceil(total / perPage));
    const from = (page - 1) * perPage;
    const data = filtered.slice(from, from + perPage);
    return { data, links: { first: null, last: null, prev: null, next: null }, meta: { current_page: page, from: from + 1, last_page: lastPage, per_page: perPage, to: from + data.length, total, path: '' } };
  },

  async deactivate(userId: number, catatan?: string): Promise<void> {
    // Real endpoint: PATCH /api/v1/administrasi/pengguna/{user}/nonaktifkan
    await api.patch(`/v1/administrasi/pengguna/${userId}/nonaktifkan`, { catatan });
  },

  async reactivate(userId: number, catatan?: string): Promise<void> {
    // Real endpoint: PATCH /api/v1/administrasi/pengguna/{user}/aktifkan-kembali
    await api.patch(`/v1/administrasi/pengguna/${userId}/aktifkan-kembali`, { catatan });
  },
};

// Re-export for convenience
export type { AuthUser };
