import { PageHeader } from '@/components/common/PageHeader';
import { Card } from '@/components/ui/Card';

export function SettingsPage() {
  return (
    <div className="space-y-6">
      <PageHeader
        title="Pengaturan"
        description="Kelola preferensi akun dan konfigurasi aplikasi."
      />
      <Card>
        <p className="text-sm text-text-secondary">
          Halaman pengaturan sedang dalam pengembangan.
        </p>
      </Card>
    </div>
  );
}
