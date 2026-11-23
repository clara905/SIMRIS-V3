import { useEffect, useState } from 'react';
import {
  BarChart, Bar, XAxis, YAxis, CartesianGrid, Tooltip, ResponsiveContainer,
  PieChart, Pie, Cell, Legend,
} from 'recharts';
import {
  Server, ShieldAlert, AlertTriangle, Wrench,
  Clock, Activity, Bell, TrendingUp,
} from 'lucide-react';
import { dashboardService } from '@/services/dashboard.service';
import type {
  DashboardStats,
  RiskDistribution,
  RecentRisk,
  ActivityItem,
  Notification,
} from '@/services/dashboard.service';
import { StatCard } from '@/components/ui/StatCard';
import { Card, CardHeader } from '@/components/ui/Card';
import { Badge } from '@/components/ui/Badge';
import { PageLoader } from '@/components/ui/LoadingSpinner';
import { PageHeader } from '@/components/common/PageHeader';
import { useAuth } from '@/contexts/AuthContext';
import type { BadgeVariant } from '@/types/badge.types';

function riskLevelToBadge(level: RecentRisk['level']): BadgeVariant {
  const map: Record<RecentRisk['level'], BadgeVariant> = {
    critical: 'critical', high: 'high', medium: 'medium', low: 'low',
  };
  return map[level];
}

function riskLevelLabel(level: RecentRisk['level']): string {
  const map: Record<RecentRisk['level'], string> = {
    critical: 'Kritis', high: 'Tinggi', medium: 'Sedang', low: 'Rendah',
  };
  return map[level];
}

function activityIcon(tipe: ActivityItem['tipe']) {
  const map: Record<ActivityItem['tipe'], { icon: typeof Activity; color: string }> = {
    create: { icon: TrendingUp, color: 'text-green-600' },
    update: { icon: Activity, color: 'text-blue-600' },
    delete: { icon: AlertTriangle, color: 'text-status-error' },
    login: { icon: Clock, color: 'text-text-muted' },
  };
  return map[tipe];
}

function notifVariant(tipe: Notification['tipe']): BadgeVariant {
  const map: Record<Notification['tipe'], BadgeVariant> = {
    critical: 'critical', warning: 'warning', info: 'info',
  };
  return map[tipe];
}

export function DashboardPage() {
  const { user } = useAuth();
  const [stats, setStats] = useState<DashboardStats | null>(null);
  const [distribution, setDistribution] = useState<RiskDistribution[]>([]);
  const [recentRisks, setRecentRisks] = useState<RecentRisk[]>([]);
  const [activities, setActivities] = useState<ActivityItem[]>([]);
  const [notifications, setNotifications] = useState<Notification[]>([]);
  const [loading, setLoading] = useState(true);

  useEffect(() => {
    Promise.all([
      dashboardService.getStats(),
      dashboardService.getRiskDistribution(),
      dashboardService.getRecentRisks(),
      dashboardService.getActivities(),
      dashboardService.getNotifications(),
    ]).then(([s, d, r, a, n]) => {
      setStats(s);
      setDistribution(d);
      setRecentRisks(r);
      setActivities(a);
      setNotifications(n);
    }).finally(() => setLoading(false));
  }, []);

  if (loading) return <PageLoader />;

  const displayName = user?.pegawai?.nama ?? user?.username ?? 'Pengguna';

  return (
    <div className="space-y-6">
      <PageHeader
        title={`Selamat datang, ${displayName.split(' ')[0]}`}
        description="Ringkasan kondisi risiko keamanan informasi organisasi"
      />

      {/* Stats */}
      <div className="grid grid-cols-1 gap-4 sm:grid-cols-2 xl:grid-cols-4">
        <StatCard label="Total Aset" value={stats?.totalAset ?? 0} icon={Server} supporting="Aset terdaftar aktif" />
        <StatCard label="Total Risiko" value={stats?.totalRisiko ?? 0} icon={ShieldAlert} supporting="Semua level risiko" />
        <StatCard label="Risiko Kritis" value={stats?.risikoKritis ?? 0} icon={AlertTriangle} supporting="Memerlukan tindakan segera" variant="critical" />
        <StatCard label="Dalam Penanganan" value={stats?.risikodalamPenanganan ?? 0} icon={Wrench} supporting="Rencana sedang berjalan" variant="warning" />
      </div>

      <div className="grid grid-cols-1 gap-6 lg:grid-cols-3">
        {/* Risk Distribution Chart */}
        <Card className="lg:col-span-2" padding="md">
          <CardHeader title="Distribusi Risiko" description="Jumlah risiko berdasarkan tingkat keparahan" className="mb-4" />
          <div className="h-56">
            <ResponsiveContainer width="100%" height="100%">
              <BarChart data={distribution} barSize={36} margin={{ top: 4, right: 8, left: -16, bottom: 0 }}>
                <CartesianGrid strokeDasharray="3 3" stroke="#E5E7EB" vertical={false} />
                <XAxis dataKey="level" tick={{ fontSize: 12, fill: '#6B7280' }} axisLine={false} tickLine={false} />
                <YAxis tick={{ fontSize: 12, fill: '#6B7280' }} axisLine={false} tickLine={false} allowDecimals={false} />
                <Tooltip
                  contentStyle={{ borderRadius: 8, border: '1px solid #E5E7EB', fontSize: 12 }}
                  cursor={{ fill: '#F5F6F8' }}
                />
                <Bar dataKey="jumlah" name="Jumlah Risiko" radius={[4, 4, 0, 0]}>
                  {distribution.map((entry, i) => (
                    <Cell key={i} fill={entry.color} />
                  ))}
                </Bar>
              </BarChart>
            </ResponsiveContainer>
          </div>
        </Card>

        {/* Pie chart */}
        <Card padding="md">
          <CardHeader title="Komposisi Risiko" className="mb-4" />
          <div className="h-56">
            <ResponsiveContainer width="100%" height="100%">
              <PieChart>
                <Pie data={distribution} dataKey="jumlah" nameKey="level" cx="50%" cy="45%" outerRadius={80} innerRadius={48}>
                  {distribution.map((entry, i) => (
                    <Cell key={i} fill={entry.color} />
                  ))}
                </Pie>
                <Legend iconType="circle" iconSize={8} wrapperStyle={{ fontSize: 12 }} />
                <Tooltip contentStyle={{ borderRadius: 8, border: '1px solid #E5E7EB', fontSize: 12 }} />
              </PieChart>
            </ResponsiveContainer>
          </div>
        </Card>
      </div>

      <div className="grid grid-cols-1 gap-6 lg:grid-cols-3">
        {/* Recent Risks */}
        <Card className="lg:col-span-2" padding="none">
          <div className="border-b border-border px-5 py-4">
            <CardHeader title="Risiko Terbaru" description="Risiko yang baru diidentifikasi atau diperbarui" />
          </div>
          <div className="divide-y divide-border">
            {recentRisks.map((risk) => (
              <div key={risk.id} className="flex items-start justify-between gap-4 px-5 py-3.5">
                <div className="min-w-0">
                  <p className="truncate text-sm font-medium text-text-primary">{risk.nama}</p>
                  <p className="mt-0.5 text-xs text-text-muted">
                    {risk.aset} · {risk.tanggal}
                  </p>
                </div>
                <div className="flex shrink-0 items-center gap-2">
                  <Badge variant={riskLevelToBadge(risk.level)}>{riskLevelLabel(risk.level)}</Badge>
                  <span className="hidden text-xs text-text-muted sm:inline">{risk.status}</span>
                </div>
              </div>
            ))}
          </div>
        </Card>

        {/* Notifications + Activity */}
        <div className="space-y-4">
          {/* Notifications */}
          <Card padding="none">
            <div className="flex items-center justify-between border-b border-border px-5 py-4">
              <h3 className="text-base font-semibold text-text-primary">Notifikasi</h3>
              <Bell className="h-4 w-4 text-text-muted" />
            </div>
            <div className="divide-y divide-border">
              {notifications.map((notif) => (
                <div key={notif.id} className={`px-5 py-3 ${!notif.dibaca ? 'bg-brand-muted/40' : ''}`}>
                  <div className="flex items-start justify-between gap-2">
                    <p className="text-xs font-medium text-text-primary">{notif.judul}</p>
                    <Badge variant={notifVariant(notif.tipe)} className="shrink-0">
                      {notif.tipe === 'critical' ? 'Kritis' : notif.tipe === 'warning' ? 'Peringatan' : 'Info'}
                    </Badge>
                  </div>
                  <p className="mt-1 text-xs text-text-secondary">{notif.isi}</p>
                  <p className="mt-1 text-[10px] text-text-muted">{notif.waktu}</p>
                </div>
              ))}
            </div>
          </Card>

          {/* Recent Activity */}
          <Card padding="none">
            <div className="border-b border-border px-5 py-4">
              <h3 className="text-base font-semibold text-text-primary">Aktivitas Terkini</h3>
            </div>
            <div className="divide-y divide-border">
              {activities.slice(0, 4).map((act) => {
                const { icon: Icon, color } = activityIcon(act.tipe);
                return (
                  <div key={act.id} className="flex items-start gap-3 px-5 py-3">
                    <div className="mt-0.5 shrink-0">
                      <Icon className={`h-3.5 w-3.5 ${color}`} />
                    </div>
                    <div className="min-w-0">
                      <p className="text-xs text-text-primary">{act.aksi}</p>
                      <p className="mt-0.5 text-[10px] text-text-muted">{act.pelaku} · {act.waktu}</p>
                    </div>
                  </div>
                );
              })}
            </div>
          </Card>
        </div>
      </div>
    </div>
  );
}
