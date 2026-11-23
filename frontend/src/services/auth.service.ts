/**
 * Auth Service
 *
 * The backend uses Laravel Sanctum with Firebase UIDs for authentication.
 * A proper /login endpoint has not yet been implemented in the backend.
 * This service uses a mock implementation that can be replaced once the
 * backend provides a token-issuing endpoint.
 *
 * To switch to real API: replace the mock functions below with actual
 * api.post('/v1/auth/login', credentials) calls.
 */

import type { AuthUser, LoginCredentials, AuthResponse } from '@/types/auth.types';
import { setToken, clearToken } from './api';

const IS_MOCK = true; // flip to false once backend auth endpoint is ready

// ---------------------------------------------------------------------------
// Mock implementation
// ---------------------------------------------------------------------------

const MOCK_USERS: Array<{ email: string; password: string; user: AuthUser; token: string }> = [
  {
    email: 'admin@simris.test',
    password: 'password',
    token: 'mock-token-super-admin-001',
    user: {
      id: 1,
      username: 'superadmin',
      email: 'admin@simris.test',
      aktif: true,
      role: { kode: 'SUPER_ADMIN', nama: 'Super Admin' },
      unit: null,
      pegawai: { id: 1, nip: '199001012020011001', nama: 'Admin Sistem' },
    },
  },
  {
    email: 'kasatker@simris.test',
    password: 'password',
    token: 'mock-token-kasatker-002',
    user: {
      id: 2,
      username: 'kasatker01',
      email: 'kasatker@simris.test',
      aktif: true,
      role: { kode: 'KASATKER', nama: 'Kepala Satuan Kerja' },
      unit: { id: 1, kode: 'SATKER-001', nama: 'Satuan Kerja Utama', jenis: 'SATKER' },
      pegawai: { id: 2, nip: '197505152000031002', nama: 'Kepala Satker' },
    },
  },
];

async function mockLogin(credentials: LoginCredentials): Promise<AuthResponse> {
  await new Promise((r) => setTimeout(r, 800)); // simulate network

  const match = MOCK_USERS.find(
    (u) => u.email === credentials.email && u.password === credentials.password,
  );

  if (!match) {
    throw { response: { status: 401, data: { message: 'Email atau password salah.' } } };
  }

  return { token: match.token, user: match.user };
}

async function mockGetCurrentUser(token: string): Promise<AuthUser> {
  await new Promise((r) => setTimeout(r, 300));
  const match = MOCK_USERS.find((u) => u.token === token);
  if (!match) throw { response: { status: 401, data: { message: 'Token tidak valid.' } } };
  return match.user;
}

// ---------------------------------------------------------------------------
// Real implementation stubs (replace body when backend is ready)
// ---------------------------------------------------------------------------

// async function realLogin(credentials: LoginCredentials): Promise<AuthResponse> {
//   const { data } = await api.post<AuthResponse>('/v1/auth/login', credentials);
//   return data;
// }

// async function realGetCurrentUser(): Promise<AuthUser> {
//   const { data } = await api.get<{ data: AuthUser }>('/user');
//   return data.data;
// }

// ---------------------------------------------------------------------------
// Exported service
// ---------------------------------------------------------------------------

export const authService = {
  async login(credentials: LoginCredentials): Promise<AuthResponse> {
    const response = IS_MOCK
      ? await mockLogin(credentials)
      : await (async () => { throw new Error('Real login not implemented.'); })();

    setToken(response.token);
    return response;
  },

  async getCurrentUser(token: string): Promise<AuthUser> {
    return IS_MOCK
      ? mockGetCurrentUser(token)
      : (async () => { throw new Error('Real getCurrentUser not implemented.'); })();
  },

  logout(): void {
    clearToken();
  },
};
