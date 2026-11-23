/**
 * Dashboard Service — mock data until backend endpoints are available.
 */

export interface DashboardStats {
  totalAset: number;
  totalRisiko: number;
  risikoKritis: number;
  risikodalamPenanganan: number;
}

export interface RiskDistribution {
  level: string;
  jumlah: number;
  color: string;
}

export interface RecentRisk {
  id: number;
  nama: string;
  aset: string;
  level: 'critical' | 'high' | 'medium' | 'low';
  status: string;
  tanggal: string;
}

export interface ActivityItem {
  id: number;
  aksi: string;
  pelaku: string;
  waktu: string;
  tipe: 'create' | 'update' | 'delete' | 'login';
}

export interface Notification {
  id: number;
  judul: string;
  isi: string;
  tipe: 'critical' | 'warning' | 'info';
  dibaca: boolean;
  waktu: string;
}

const MOCK_STATS: DashboardStats = {
  totalAset: 142,
  totalRisiko: 38,
  risikoKritis: 7,
  risikodalamPenanganan: 15,
};

const MOCK_RISK_DISTRIBUTION: RiskDistribution[] = [
  { level: 'Kritis', jumlah: 7, color: '#762127' },
  { level: 'Tinggi', jumlah: 11, color: '#DC2626' },
  { level: 'Sedang', jumlah: 13, color: '#D97706' },
  { level: 'Rendah', jumlah: 7, color: '#16A34A' },
];

const MOCK_RECENT_RISKS: RecentRisk[] = [
  { id: 1, nama: 'Akses tidak sah ke sistem keuangan', aset: 'Sistem Keuangan', level: 'critical', status: 'Dalam Penanganan', tanggal: '2026-11-20' },
  { id: 2, nama: 'Kebocoran data pegawai melalui email', aset: 'Server Email', level: 'high', status: 'Draft', tanggal: '2026-11-19' },
  { id: 3, nama: 'Pemadaman layanan jaringan internal', aset: 'Jaringan LAN', level: 'high', status: 'Disetujui', tanggal: '2026-11-18' },
  { id: 4, nama: 'Software usang pada workstation', aset: 'Workstation Keuangan', level: 'medium', status: 'Draft', tanggal: '2026-11-17' },
  { id: 5, nama: 'Password default tidak diganti', aset: 'Router Gateway', level: 'medium', status: 'Dalam Penanganan', tanggal: '2026-11-16' },
];

const MOCK_ACTIVITIES: ActivityItem[] = [
  { id: 1, aksi: 'Menambahkan aset baru: Laptop Dell Latitude', pelaku: 'Admin Satker', waktu: '10 menit lalu', tipe: 'create' },
  { id: 2, aksi: 'Memperbarui penilaian risiko ID #12', pelaku: 'Kepala Bidang TI', waktu: '1 jam lalu', tipe: 'update' },
  { id: 3, aksi: 'Menghapus ancaman: Phishing Email (duplikat)', pelaku: 'Super Admin', waktu: '2 jam lalu', tipe: 'delete' },
  { id: 4, aksi: 'Login ke sistem', pelaku: 'Kepala Satker', waktu: '3 jam lalu', tipe: 'login' },
  { id: 5, aksi: 'Menambahkan penanganan risiko baru', pelaku: 'Staff TI', waktu: '5 jam lalu', tipe: 'create' },
];

const MOCK_NOTIFICATIONS: Notification[] = [
  { id: 1, judul: 'Risiko Kritis Belum Ditangani', isi: '7 risiko kritis memerlukan perhatian segera.', tipe: 'critical', dibaca: false, waktu: '1 jam lalu' },
  { id: 2, judul: 'Penanganan Risiko Terlambat', isi: '3 rencana penanganan melewati tenggat waktu.', tipe: 'warning', dibaca: false, waktu: '3 jam lalu' },
  { id: 3, judul: 'Penilaian Risiko Triwulan', isi: 'Jadwal penilaian risiko triwulan 4 mendekati.', tipe: 'info', dibaca: true, waktu: '1 hari lalu' },
];

function delay(ms = 500): Promise<void> {
  return new Promise((r) => setTimeout(r, ms));
}

export const dashboardService = {
  async getStats(): Promise<DashboardStats> {
    await delay();
    return MOCK_STATS;
  },

  async getRiskDistribution(): Promise<RiskDistribution[]> {
    await delay(400);
    return MOCK_RISK_DISTRIBUTION;
  },

  async getRecentRisks(): Promise<RecentRisk[]> {
    await delay(400);
    return MOCK_RECENT_RISKS;
  },

  async getActivities(): Promise<ActivityItem[]> {
    await delay(300);
    return MOCK_ACTIVITIES;
  },

  async getNotifications(): Promise<Notification[]> {
    await delay(300);
    return MOCK_NOTIFICATIONS;
  },
};
