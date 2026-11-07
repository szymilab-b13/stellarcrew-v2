import { useTranslation } from 'react-i18next';

import { useMe } from '@/features/auth/api/useMe';
import { RoadmapPanel } from '@/features/dashboard';

export function DashboardPage() {
  const { t } = useTranslation();
  const me = useMe();
  const displayName = me.data?.displayName ?? me.data?.email ?? '';

  return (
    <div className="mx-auto flex max-w-4xl flex-col gap-6 px-6 py-10">
      <header className="flex flex-col gap-2">
        <h1 className="text-3xl font-semibold tracking-tight">
          {t('dashboard.greeting', { name: displayName })}
        </h1>
        <p className="text-sm text-sc-text-muted">{t('dashboard.subtitle')}</p>
      </header>

      <section className="rounded-lg border border-sc-border bg-sc-bg-card p-5 shadow-sm">
        <h2 className="text-lg font-medium">{t('dashboard.widgets.whatNow.title')}</h2>
        <p className="mt-2 text-sm text-sc-text-muted">{t('dashboard.widgets.whatNow.body')}</p>
      </section>

      <RoadmapPanel />
    </div>
  );
}
