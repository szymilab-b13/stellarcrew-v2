import { LogOut } from 'lucide-react';
import { useTranslation } from 'react-i18next';
import { Navigate, useNavigate } from 'react-router-dom';

import { Logo } from '@/features/branding/Logo';
import { useInstallStatus } from '@/features/install/api/useInstallStatus';
import { LanguageSwitcher } from '@/features/settings/LanguageSwitcher';
import { ThemeToggle } from '@/features/theme/ThemeToggle';
import { readAuthToken } from '@/lib/authToken';

import { useLogout } from '@/features/auth/api/useLogout';
import { useMe } from '@/features/auth/api/useMe';
import type { AuthUser } from '@/features/auth/types';

export function HomePage() {
  const install = useInstallStatus();
  const token = readAuthToken();
  const me = useMe();

  if (install.isPending) {
    return <CenteredNotice>Loading…</CenteredNotice>;
  }

  if (install.isError) {
    return (
      <CenteredNotice>Could not reach the API. Make sure the backend is running.</CenteredNotice>
    );
  }

  if (!install.data?.isInstalled) {
    return <Navigate to="/install" replace />;
  }

  if (token === null) {
    return <Navigate to="/login" replace />;
  }

  if (me.isPending) {
    return <CenteredNotice>Loading…</CenteredNotice>;
  }

  if (me.isError || !me.data) {
    return <Navigate to="/login" replace />;
  }

  return <AuthenticatedHome user={me.data} />;
}

function AuthenticatedHome({ user }: { user: AuthUser }) {
  const { t } = useTranslation();
  const navigate = useNavigate();
  const logout = useLogout();

  const displayName = user.displayName ?? user.email;

  function handleLogout() {
    logout.mutate(undefined, {
      onSettled: () => {
        navigate('/login', { replace: true });
      },
    });
  }

  return (
    <main className="min-h-screen bg-sc-bg text-sc-text">
      <header className="flex items-center justify-between border-b border-sc-border px-6 py-4 lg:px-10">
        <span className="flex items-center gap-3 text-base font-semibold tracking-tight">
          <Logo size={32} />
          StellarCrew
        </span>
        <div className="flex items-center gap-3">
          <span className="hidden text-sm text-sc-text-muted sm:inline">
            {t('home.greeting', { name: displayName })}
          </span>
          <LanguageSwitcher />
          <ThemeToggle />
          <button
            type="button"
            onClick={handleLogout}
            disabled={logout.isPending}
            className="inline-flex items-center gap-2 rounded-md border border-sc-border bg-sc-bg-card px-3 py-1.5 text-xs font-semibold text-sc-text transition-colors hover:bg-sc-bg-secondary disabled:opacity-60"
          >
            <LogOut size={14} aria-hidden />
            {t('home.logout')}
          </button>
        </div>
      </header>

      <div className="mx-auto flex max-w-3xl flex-col gap-6 px-6 py-12">
        <header className="flex flex-col gap-2">
          <h1 className="text-3xl font-semibold tracking-tight">{t('home.title')}</h1>
          <p className="text-sm text-sc-text-muted">{t('home.subtitle')}</p>
        </header>

        <section className="rounded-lg border border-sc-border bg-sc-bg-card p-5 shadow-sm">
          <h2 className="text-lg font-medium">{t('home.youAreIn.title')}</h2>
          <p className="mt-2 text-sm text-sc-text-muted">
            {t('home.youAreIn.body', { name: displayName })}
          </p>
        </section>

        <section className="rounded-lg border border-sc-border bg-sc-bg-secondary p-5">
          <h2 className="text-lg font-medium">{t('home.nextSteps.title')}</h2>
          <p className="mt-2 text-sm text-sc-text-muted">{t('home.nextSteps.body')}</p>
        </section>
      </div>
    </main>
  );
}

function CenteredNotice({ children }: { children: React.ReactNode }) {
  return (
    <main className="flex min-h-screen items-center justify-center bg-sc-bg text-sm text-sc-text-muted">
      {children}
    </main>
  );
}
