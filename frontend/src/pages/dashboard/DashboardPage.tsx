import { useEffect, useState } from 'react';
import { Link } from 'react-router-dom';
import {
  ClipboardList,
  ShieldCheck,
  Server,
  CheckCircle,
  Clock,
  AlertTriangle,
  ArrowRight,
} from 'lucide-react';
import { dashboardService } from '@/services/dashboard.service';
import type {
  StaffDashboardStats,
  StaffTask,
  StaffRecentActivity,
  StaffNotification,
} from '@/services/dashboard.service';
import { StatCard } from '@/components/ui/StatCard';
import { Card, CardHeader } from '@/components/ui/Card';
import { Badge } from '@/components/ui/Badge';
import { Button } from '@/components/ui/Button';
import { PageLoader } from '@/components/ui/LoadingSpinner';
import { PageHeader } from '@/components/common/PageHeader';
import { useAuth } from '@/contexts/AuthContext';
import type { BadgeVariant } from '@/types/badge.types';

function taskPriorityBadge(priority: StaffTask['prioritas']): BadgeVariant {
  return { tinggi: 'high', sedang: 'medium', rendah: 'low' }[priority] as BadgeVariant;
}

function taskPriorityLabel(priority: StaffTask['prioritas']): string {
  return { tinggi: 'Tinggi', sedang: 'Sedang', rendah: 'Rendah' }[priority];
}

function notifVariant(tipe: StaffNotification['tipe']): BadgeVariant {
  return { penting: 'high', peringatan: 'warning', info: 'info' }[tipe] as BadgeVariant;
}

export function DashboardPage() {
  const { user } = useAuth();
  const [stats, setStats] = useState<StaffDashboardStats | null>(null);
  const [tasks, setTasks] = useState<StaffTask[]>([]);
  const [activities, setActivities] = useState<StaffRecentActivity[]>([]);
  const [notifications, setNotifications] = useState<StaffNotification[]>([]);
  const [loading, setLoading] = useState(true);

  useEffect(() => {
    Promise.all([
      dashboardService.getStaffStats(),
      dashboardService.getStaffTasks(),
      dashboardService.getStaffActivities(),
      dashboardService.getStaffNotifications(),
    ]).then(([s, t, a, n]) => {
      setStats(s);
      setTasks(t);
      setActivities(a);
      setNotifications(n);
    }).finally(() => setLoading(false));
  }, []);

  if (loading) return <PageLoader />;

  const displayName = user?.pegawai?.nama ?? user?.username ?? 'Pengguna';
  const firstName = displayName.split(' ')[0];

  return (
    <div className="space-y-6">
      {/* Welcome Section */}
      <div className="rounded-lg bg-gradient-to-r from-brand to-brand-dark p-6 text-white">
        <h1 className="text-2xl font-bold">Selamat datang, {firstName}</h1>
        <p className="mt-1 text-sm text-white/90">
          Kelola risiko keamanan informasi dan aset Anda dengan mudah
        </p>
      </div>

      {/* Stats — Personal workspace metrics */}
      <div className="grid grid-cols-1 gap-4 sm:grid-cols-2 xl:grid-cols-4">
        <StatCard
          label="Tugas Saya"
          value={stats?.tugasSaya ?? 0}
          icon={ClipboardList}
          supporting="Total tugas yang ditugaskan"
        />
        <StatCard
          label="Asesmen Aktif"
          value={stats?.asesmenAktif ?? 0}
          icon={ShieldCheck}
          supporting="Asesmen yang sedang berjalan"
          variant="info"
        />
        <StatCard
          label="Aset Terkait"
          value={stats?.asetTerkait ?? 0}
          icon={Server}
          supporting="Aset dalam tanggung jawab"
        />
        <StatCard
          label="Butuh Perhatian"
          value={stats?.butuhPerhatian ?? 0}
          icon={AlertTriangle}
          supporting="Item yang memerlukan tindakan"
          variant="warning"
        />
      </div>

      <div className="grid grid-cols-1 gap-6 lg:grid-cols-3">
        {/* My Tasks */}
        <Card className="lg:col-span-2" padding="none">
          <div className="flex items-center justify-between border-b border-border px-5 py-4">
            <CardHeader title="Tugas Saya" description="Daftar tugas dan tanggung jawab yang ditugaskan" />
            <Link to="/asesmen-risiko">
              <Button size="sm" variant="secondary">
                Lihat Semua
              </Button>
            </Link>
          </div>
          <div className="divide-y divide-border">
            {tasks.length === 0 ? (
              <div className="px-5 py-8 text-center">
                <CheckCircle className="mx-auto h-10 w-10 text-green-600" />
                <p className="mt-2 text-sm font-medium text-text-primary">Semua tugas selesai!</p>
                <p className="mt-1 text-xs text-text-muted">Tidak ada tugas yang menunggu saat ini.</p>
              </div>
            ) : (
              tasks.map((task) => (
                <div key={task.id} className="flex items-start justify-between gap-4 px-5 py-3.5 hover:bg-surface-muted/50">
                  <div className="min-w-0 flex-1">
                    <div className="flex items-center gap-2">
                      <p className="truncate text-sm font-medium text-text-primary">{task.nama}</p>
                      <Badge variant={taskPriorityBadge(task.prioritas)} className="shrink-0">
                        {taskPriorityLabel(task.prioritas)}
                      </Badge>
                    </div>
                    <p className="mt-1 text-xs text-text-muted">
                      <Clock className="inline h-3 w-3 mr-1" />
                      Tenggat: {task.tenggat}
                    </p>
                  </div>
                  <Link to={`/asesmen-risiko/${task.id}`}>
                    <button className="shrink-0 rounded p-1.5 text-text-muted hover:bg-surface-muted hover:text-brand">
                      <ArrowRight className="h-4 w-4" />
                    </button>
                  </Link>
                </div>
              ))
            )}
          </div>
        </Card>

        {/* Quick Actions + Notifications */}
        <div className="space-y-4">
          {/* Quick Actions */}
          <Card padding="md">
            <CardHeader title="Aksi Cepat" className="mb-4" />
            <div className="space-y-2">
              <Link to="/asesmen-risiko" className="block">
                <Button variant="outline" className="w-full justify-start" leftIcon={<ClipboardList className="h-4 w-4" />}>
                  Mulai Asesmen Baru
                </Button>
              </Link>
              <Link to="/aset" className="block">
                <Button variant="outline" className="w-full justify-start" leftIcon={<Server className="h-4 w-4" />}>
                  Kelola Aset Saya
                </Button>
              </Link>
              <Link to="/penanganan-risiko" className="block">
                <Button variant="outline" className="w-full justify-start" leftIcon={<ShieldCheck className="h-4 w-4" />}>
                  Lihat Penanganan
                </Button>
              </Link>
            </div>
          </Card>

          {/* Notifications */}
          <Card padding="none">
            <div className="border-b border-border px-5 py-4">
              <h3 className="text-base font-semibold text-text-primary">Notifikasi</h3>
            </div>
            <div className="divide-y divide-border">
              {notifications.length === 0 ? (
                <div className="px-5 py-6 text-center text-xs text-text-muted">
                  Tidak ada notifikasi baru
                </div>
              ) : (
                notifications.map((notif) => (
                  <div key={notif.id} className={`px-5 py-3 ${!notif.dibaca ? 'bg-brand-muted/20' : ''}`}>
                    <div className="flex items-start justify-between gap-2">
                      <p className="text-xs font-medium text-text-primary">{notif.judul}</p>
                      <Badge variant={notifVariant(notif.tipe)} className="shrink-0 text-[10px]">
                        {notif.tipe === 'penting' ? 'Penting' : notif.tipe === 'peringatan' ? 'Peringatan' : 'Info'}
                      </Badge>
                    </div>
                    <p className="mt-1 text-xs text-text-secondary">{notif.isi}</p>
                    <p className="mt-1 text-[10px] text-text-muted">{notif.waktu}</p>
                  </div>
                ))
              )}
            </div>
          </Card>
        </div>
      </div>

      {/* Recent Activity */}
      <Card padding="none">
        <div className="border-b border-border px-5 py-4">
          <CardHeader title="Aktivitas Terkini" description="Riwayat aktivitas Anda" />
        </div>
        <div className="divide-y divide-border">
          {activities.length === 0 ? (
            <div className="px-5 py-6 text-center text-xs text-text-muted">
              Belum ada aktivitas tercatat
            </div>
          ) : (
            activities.map((act) => (
              <div key={act.id} className="flex items-start gap-3 px-5 py-3">
                <div className="mt-0.5 flex h-7 w-7 shrink-0 items-center justify-center rounded-full bg-brand-muted">
                  {act.tipe === 'asesmen' && <ClipboardList className="h-3.5 w-3.5 text-brand" />}
                  {act.tipe === 'aset' && <Server className="h-3.5 w-3.5 text-brand" />}
                  {act.tipe === 'penanganan' && <ShieldCheck className="h-3.5 w-3.5 text-brand" />}
                </div>
                <div className="min-w-0 flex-1">
                  <p className="text-xs text-text-primary">{act.aksi}</p>
                  <p className="mt-0.5 text-[10px] text-text-muted">{act.waktu}</p>
                </div>
              </div>
            ))
          )}
        </div>
      </Card>
    </div>
  );
}
