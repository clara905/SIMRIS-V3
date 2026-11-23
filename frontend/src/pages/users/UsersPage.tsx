import { PageHeader } from '@/components/common/PageHeader';
import { Card } from '@/components/ui/Card';

export function UsersPage() {
  return (
    <div className="space-y-6">
      <PageHeader
        title="Manajemen Pengguna"
        description="Kelola akun pengguna, peran, dan status akses sistem."
      />
      <Card>
        <p className="text-sm text-text-secondary">
          Halaman ini akan menampilkan daftar pengguna, peran, dan pengaturan akses.
          Konten sedang dalam pengembangan.
        </p>
      </Card>
    </div>
  );
}
