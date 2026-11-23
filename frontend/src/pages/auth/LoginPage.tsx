import { useState } from 'react';
import { useNavigate } from 'react-router-dom';
import { useForm } from 'react-hook-form';
import { zodResolver } from '@hookform/resolvers/zod';
import { z } from 'zod';
import { Eye, EyeOff, Mail, Lock } from 'lucide-react';
import { useAuth } from '@/contexts/AuthContext';
import { Input } from '@/components/ui/Input';
import { Button } from '@/components/ui/Button';

const schema = z.object({
  email: z.string().email('Format email tidak valid.'),
  password: z.string().min(6, 'Password minimal 6 karakter.'),
  remember: z.boolean().optional(),
});

type FormValues = z.infer<typeof schema>;

export function LoginPage() {
  const { login } = useAuth();
  const navigate = useNavigate();
  const [showPassword, setShowPassword] = useState(false);
  const [serverError, setServerError] = useState<string | null>(null);

  const {
    register,
    handleSubmit,
    formState: { errors, isSubmitting },
  } = useForm<FormValues>({
    resolver: zodResolver(schema),
    defaultValues: { email: '', password: '', remember: false },
  });

  const onSubmit = async (data: FormValues) => {
    setServerError(null);
    try {
      await login({ email: data.email, password: data.password, remember: data.remember });
      navigate('/dashboard', { replace: true });
    } catch (err: unknown) {
      const anyErr = err as { response?: { data?: { message?: string } } };
      setServerError(anyErr?.response?.data?.message ?? 'Terjadi kesalahan. Coba lagi.');
    }
  };

  return (
    <div className="space-y-6">
      <div>
        <h1 className="text-2xl font-bold text-text-primary">Masuk ke SIMRIS</h1>
        <p className="mt-1.5 text-sm text-text-secondary">
          Gunakan akun yang telah diberikan oleh administrator.
        </p>
      </div>

      {serverError && (
        <div
          role="alert"
          className="rounded-md border border-red-200 bg-red-50 px-4 py-3 text-sm text-status-error"
        >
          {serverError}
        </div>
      )}

      <form onSubmit={handleSubmit(onSubmit)} noValidate className="space-y-4">
        <Input
          label="Email"
          type="email"
          autoComplete="email"
          placeholder="nama@instansi.go.id"
          required
          leftIcon={<Mail className="h-4 w-4" />}
          error={errors.email?.message}
          {...register('email')}
        />

        <Input
          label="Password"
          type={showPassword ? 'text' : 'password'}
          autoComplete="current-password"
          placeholder="••••••••"
          required
          leftIcon={<Lock className="h-4 w-4" />}
          error={errors.password?.message}
          rightElement={
            <button
              type="button"
              onClick={() => setShowPassword((v) => !v)}
              className="text-text-muted hover:text-text-primary"
              aria-label={showPassword ? 'Sembunyikan password' : 'Tampilkan password'}
            >
              {showPassword ? <EyeOff className="h-4 w-4" /> : <Eye className="h-4 w-4" />}
            </button>
          }
          {...register('password')}
        />

        <div className="flex items-center justify-between">
          <label className="flex cursor-pointer items-center gap-2 text-sm text-text-secondary">
            <input
              type="checkbox"
              className="h-4 w-4 rounded border-border accent-brand"
              {...register('remember')}
            />
            Ingat saya
          </label>
        </div>

        <Button
          type="submit"
          variant="primary"
          size="lg"
          loading={isSubmitting}
          className="w-full"
        >
          Masuk
        </Button>
      </form>

      <div className="rounded-md border border-border bg-surface-muted px-4 py-3">
        <p className="text-xs font-medium text-text-secondary">Akun Demo:</p>
        <div className="mt-1.5 space-y-0.5 text-xs text-text-muted">
          <p><span className="font-medium">Super Admin:</span> admin@simris.test / password</p>
          <p><span className="font-medium">Kasatker:</span> kasatker@simris.test / password</p>
        </div>
      </div>
    </div>
  );
}
