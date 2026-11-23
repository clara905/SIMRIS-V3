export interface AuthUser {
  id: number;
  username: string;
  email: string;
  aktif: boolean;
  role?: {
    kode: string;
    nama: string;
  };
  unit?: {
    id: number;
    kode: string;
    nama: string;
    jenis: string;
  } | null;
  pegawai?: {
    id: number;
    nip: string;
    nama: string;
  } | null;
}

export interface LoginCredentials {
  email: string;
  password: string;
  remember?: boolean;
}

export interface AuthResponse {
  token: string;
  user: AuthUser;
}
