/**
 * Dashboard Service — STAFF (SATKER) role
 * 
 * Mock data for STAFF personal workspace until backend endpoints are available.
 * Focuses on individual staff member's tasks, assignments, and personal activity.
 */

// ─── STAFF Dashboard Types ───────────────────────────────────────────────────

export interface StaffDashboardStats {
  tugasSaya: number;           // Personal tasks assigned to this staff
  asesmenAktif: number;        // Active risk assessments assigned to staff
  asetTerkait: number;         // Assets related to staff's responsibility
  butuhPerhatian: number;      // Items requiring staff's attention
}

export interface StaffTask {
  id: number;
  nama: string;
  deskripsi?: string;
  prioritas: 'tinggi' | 'sedang' | 'rendah';
  tenggat: string;             // Deadline date
  status: 'baru' | 'dalam_proses' | 'selesai';
}

export interface StaffRecentActivity {
  id: number;
  aksi: string;                // Description of action performed
  waktu: string;               // Relative time (e.g., "2 jam lalu")
  tipe: 'asesmen' | 'aset' | 'penanganan';
}

export interface StaffNotification {
  id: number;
  judul: string;
  isi: string;
  tipe: 'penting' | 'peringatan' | 'info';
  dibaca: boolean;
  waktu: string;
}

// ─── Mock Data ───────────────────────────────────────────────────────────────

const MOCK_STAFF_STATS: StaffDashboardStats = {
  tugasSaya: 5,
  asesmenAktif: 3,
  asetTerkait: 12,
  butuhPerhatian: 2,
};

const MOCK_STAFF_TASKS: StaffTask[] = [
  {
    id: 1,
    nama: 'Asesmen Risiko Sistem Email',
    deskripsi: 'Lakukan penilaian risiko untuk sistem email internal',
    prioritas: 'tinggi',
    tenggat: '2026-12-15',
    status: 'dalam_proses',
  },
  {
    id: 2,
    nama: 'Update Data Aset Server',
    deskripsi: 'Perbarui informasi aset server database',
    prioritas: 'sedang',
    tenggat: '2026-12-20',
    status: 'baru',
  },
  {
    id: 3,
    nama: 'Review Rencana Penanganan Risiko',
    deskripsi: 'Tinjau dan berikan input untuk penanganan risiko',
    prioritas: 'sedang',
    tenggat: '2026-12-18',
    status: 'dalam_proses',
  },
  {
    id: 4,
    nama: 'Dokumentasi SOP Backup',
    deskripsi: 'Lengkapi dokumentasi SOP backup harian',
    prioritas: 'rendah',
    tenggat: '2026-12-25',
    status: 'baru',
  },
  {
    id: 5,
    nama: 'Verifikasi Data Aset Hardware',
    prioritas: 'tinggi',
    tenggat: '2026-12-12',
    status: 'dalam_proses',
  },
];

const MOCK_STAFF_ACTIVITIES: StaffRecentActivity[] = [
  {
    id: 1,
    aksi: 'Menyelesaikan asesmen risiko untuk Sistem Keuangan',
    waktu: '30 menit lalu',
    tipe: 'asesmen',
  },
  {
    id: 2,
    aksi: 'Memperbarui data aset: Laptop Dell Latitude 5420',
    waktu: '2 jam lalu',
    tipe: 'aset',
  },
  {
    id: 3,
    aksi: 'Menambahkan rencana mitigasi untuk risiko keamanan jaringan',
    waktu: '1 hari lalu',
    tipe: 'penanganan',
  },
  {
    id: 4,
    aksi: 'Mengupload dokumentasi SOP instalasi software',
    waktu: '1 hari lalu',
    tipe: 'aset',
  },
  {
    id: 5,
    aksi: 'Memulai asesmen risiko untuk Server Email',
    waktu: '2 hari lalu',
    tipe: 'asesmen',
  },
];

const MOCK_STAFF_NOTIFICATIONS: StaffNotification[] = [
  {
    id: 1,
    judul: 'Tenggat Asesmen Mendekati',
    isi: 'Asesmen risiko sistem email harus diselesaikan dalam 3 hari.',
    tipe: 'penting',
    dibaca: false,
    waktu: '1 jam lalu',
  },
  {
    id: 2,
    judul: 'Update Data Aset Diperlukan',
    isi: 'Mohon perbarui data aset server database sebelum 20 Desember.',
    tipe: 'peringatan',
    dibaca: false,
    waktu: '5 jam lalu',
  },
  {
    id: 3,
    judul: 'SOP Baru Tersedia',
    isi: 'SOP terbaru untuk pengelolaan backup telah dipublikasikan.',
    tipe: 'info',
    dibaca: true,
    waktu: '1 hari lalu',
  },
];

// ─── Service ─────────────────────────────────────────────────────────────────

function delay(ms = 500): Promise<void> {
  return new Promise((r) => setTimeout(r, ms));
}

export const dashboardService = {
  /**
   * Get STAFF personal dashboard statistics
   */
  async getStaffStats(): Promise<StaffDashboardStats> {
    await delay();
    return MOCK_STAFF_STATS;
  },

  /**
   * Get STAFF assigned tasks
   */
  async getStaffTasks(): Promise<StaffTask[]> {
    await delay(400);
    return MOCK_STAFF_TASKS;
  },

  /**
   * Get STAFF recent personal activities
   */
  async getStaffActivities(): Promise<StaffRecentActivity[]> {
    await delay(300);
    return MOCK_STAFF_ACTIVITIES;
  },

  /**
   * Get STAFF notifications
   */
  async getStaffNotifications(): Promise<StaffNotification[]> {
    await delay(300);
    return MOCK_STAFF_NOTIFICATIONS;
  },
};
