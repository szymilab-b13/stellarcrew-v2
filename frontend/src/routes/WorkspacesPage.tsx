import { useTranslation } from 'react-i18next';

export function WorkspacesPage() {
  const { t } = useTranslation();

  return (
    <div className="mx-auto flex max-w-4xl flex-col gap-6 px-6 py-10">
      <header className="flex flex-col gap-2">
        <span className="text-xs font-semibold uppercase tracking-widest text-sc-text-muted">
          {t('workspaces.eyebrow')}
        </span>
        <h1 className="text-3xl font-semibold tracking-tight">{t('workspaces.title')}</h1>
        <p className="text-sm text-sc-text-muted">{t('workspaces.subtitle')}</p>
      </header>

      <section className="rounded-lg border border-dashed border-sc-border bg-sc-bg-card p-6 text-sm text-sc-text-muted">
        {t('workspaces.comingSoon')}
      </section>
    </div>
  );
}
