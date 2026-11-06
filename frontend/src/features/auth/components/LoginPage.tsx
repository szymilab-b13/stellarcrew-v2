import { zodResolver } from '@hookform/resolvers/zod';
import { AtSign, KeyRound, Loader2, ShieldAlert } from 'lucide-react';
import { useForm } from 'react-hook-form';
import { useTranslation } from 'react-i18next';
import { Navigate, useNavigate } from 'react-router-dom';
import { z } from 'zod';

import { Logo } from '@/features/branding/Logo';
import { LanguageSwitcher } from '@/features/settings/LanguageSwitcher';
import { ThemeToggle } from '@/features/theme/ThemeToggle';
import { ApiError } from '@/lib/apiClient';
import { readAuthToken } from '@/lib/authToken';

import { useLogin } from '../api/useLogin';

const loginSchema = z.object({
  email: z.string().email('Enter a valid email address'),
  password: z.string().min(1, 'Password is required'),
});

type LoginFormValues = z.infer<typeof loginSchema>;

export function LoginPage() {
  const { t } = useTranslation();
  const navigate = useNavigate();
  const mutation = useLogin();

  const form = useForm<LoginFormValues>({
    resolver: zodResolver(loginSchema),
    defaultValues: { email: '', password: '' },
    mode: 'onTouched',
  });

  if (readAuthToken() !== null && !mutation.isPending) {
    return <Navigate to="/" replace />;
  }

  const serverError = mutation.error instanceof ApiError ? mutation.error : null;
  const serverMessage = resolveServerMessage(serverError, t);

  function submit(values: LoginFormValues) {
    mutation.mutate(values, {
      onSuccess: () => {
        navigate('/', { replace: true });
      },
    });
  }

  return (
    <div className="flex min-h-screen bg-sc-bg text-sc-text">
      <main className="flex min-h-screen flex-1 flex-col">
        <div className="flex items-center justify-between border-b border-sc-border px-6 py-4 lg:px-10">
          <span className="flex items-center gap-3 text-base font-semibold tracking-tight">
            <Logo size={32} />
            StellarCrew
          </span>
          <div className="flex items-center gap-2">
            <LanguageSwitcher />
            <ThemeToggle />
          </div>
        </div>

        <div className="mx-auto flex w-full max-w-md flex-1 flex-col justify-center gap-8 px-6 py-10">
          <header className="flex flex-col gap-2">
            <span className="text-xs font-semibold uppercase tracking-widest text-sc-text-muted">
              {t('login.eyebrow')}
            </span>
            <h1 className="text-3xl font-semibold tracking-tight text-sc-text">
              {t('login.title')}
            </h1>
            <p className="text-sm text-sc-text-muted">{t('login.description')}</p>
          </header>

          <form
            noValidate
            className="flex flex-col gap-5 rounded-lg border border-sc-border bg-sc-bg-card p-6 shadow-sm"
            onSubmit={form.handleSubmit(submit)}
          >
            <Field
              id="login-email"
              label={t('login.email')}
              icon={<AtSign size={16} aria-hidden />}
              error={form.formState.errors.email?.message}
            >
              <input
                id="login-email"
                type="email"
                autoComplete="username"
                placeholder={t('login.emailPlaceholder')}
                aria-invalid={Boolean(form.formState.errors.email)}
                className="w-full rounded-md border border-sc-border bg-sc-bg px-9 py-2 text-sm text-sc-text outline-none transition-colors focus:border-sc-text"
                {...form.register('email')}
              />
            </Field>

            <Field
              id="login-password"
              label={t('login.password')}
              icon={<KeyRound size={16} aria-hidden />}
              error={form.formState.errors.password?.message}
            >
              <input
                id="login-password"
                type="password"
                autoComplete="current-password"
                aria-invalid={Boolean(form.formState.errors.password)}
                className="w-full rounded-md border border-sc-border bg-sc-bg px-9 py-2 text-sm text-sc-text outline-none transition-colors focus:border-sc-text"
                {...form.register('password')}
              />
            </Field>

            {serverMessage ? (
              <p
                role="alert"
                className="flex items-center gap-2 rounded-md border border-red-300 bg-red-50 px-3 py-2 text-sm text-red-900"
              >
                <ShieldAlert size={16} aria-hidden /> {serverMessage}
              </p>
            ) : null}

            <button
              type="submit"
              disabled={mutation.isPending}
              className="inline-flex items-center justify-center gap-2 rounded-md bg-sc-primary px-4 py-2 text-sm font-semibold text-sc-primary-foreground shadow-sm transition-opacity hover:opacity-90 disabled:opacity-60"
            >
              {mutation.isPending ? (
                <>
                  <Loader2 className="animate-spin" size={16} aria-hidden />
                  {t('login.signingIn')}
                </>
              ) : (
                t('login.signIn')
              )}
            </button>
          </form>
        </div>
      </main>
    </div>
  );
}

interface FieldProps {
  id: string;
  label: string;
  icon: React.ReactNode;
  error?: string;
  children: React.ReactNode;
}

function Field({ id, label, icon, error, children }: FieldProps) {
  return (
    <div className="flex flex-col gap-1.5">
      <label htmlFor={id} className="text-xs font-medium text-sc-text">
        {label}
      </label>
      <div className="relative">
        <span className="pointer-events-none absolute left-3 top-1/2 -translate-y-1/2 text-sc-text-muted">
          {icon}
        </span>
        {children}
      </div>
      {error ? (
        <p role="alert" className="text-xs text-red-600">
          {error}
        </p>
      ) : null}
    </div>
  );
}

function resolveServerMessage(error: ApiError | null, t: (key: string) => string): string | null {
  if (!error) return null;
  if (error.status === 401) return t('login.invalidCredentials');
  if (error.status === 422) return t('login.validationFailed');
  return t('login.genericError');
}
