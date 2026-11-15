import { ArrowLeft, Loader2 } from 'lucide-react';
import { useMemo } from 'react';
import { useTranslation } from 'react-i18next';
import { Link } from 'react-router-dom';

import { usePageBreadcrumbs } from '@/components/app/useBreadcrumbs';

import { useAgentTemplates } from '../api/useAgentTemplates';
import { AgentTemplateTile } from './AgentTemplateTile';

export function AgentTemplatePickerPage() {
  const { t } = useTranslation();
  const templatesQuery = useAgentTemplates();

  const breadcrumbs = useMemo(
    () => [
      { label: t('agents.breadcrumb'), to: '/agents' },
      { label: t('agents.templatePicker.breadcrumb') },
    ],
    [t],
  );
  usePageBreadcrumbs(breadcrumbs);

  return (
    <div className="flex w-full flex-col gap-6">
      <header className="flex flex-col gap-2">
        <Link
          to="/agents"
          className="inline-flex w-fit items-center gap-1 text-xs font-medium text-sc-text-muted transition-colors hover:text-sc-text"
        >
          <ArrowLeft size={12} aria-hidden />
          {t('agents.templatePicker.back')}
        </Link>
        <h1 className="text-2xl font-semibold tracking-tight text-sc-text">
          {t('agents.templatePicker.title')}
        </h1>
        <p className="text-sm text-sc-text-muted">{t('agents.templatePicker.subtitle')}</p>
      </header>

      {templatesQuery.isPending ? (
        <p className="flex items-center gap-2 text-sm text-sc-text-muted">
          <Loader2 className="animate-spin" size={14} aria-hidden />
          {t('agents.templatePicker.loading')}
        </p>
      ) : null}

      {templatesQuery.isError ? (
        <p
          role="alert"
          className="rounded-md border border-red-300 bg-red-50 px-3 py-2 text-sm text-red-900"
        >
          {t('agents.templatePicker.loadError')}
        </p>
      ) : null}

      {templatesQuery.isSuccess && templatesQuery.data.length === 0 ? (
        <section
          aria-label={t('agents.templatePicker.emptyAria')}
          className="flex min-h-[12rem] flex-col items-center justify-center gap-2 rounded-lg border border-dashed border-sc-border bg-sc-bg-card/40 p-8 text-center"
        >
          <p className="text-base font-medium text-sc-text">
            {t('agents.templatePicker.emptyTitle')}
          </p>
          <p className="max-w-md text-sm text-sc-text-muted">
            {t('agents.templatePicker.emptyBody')}
          </p>
        </section>
      ) : null}

      {templatesQuery.isSuccess && templatesQuery.data.length > 0 ? (
        <section
          aria-label={t('agents.templatePicker.listAria')}
          className="grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4"
        >
          {templatesQuery.data.map((template) => (
            <AgentTemplateTile key={template.id} template={template} />
          ))}
        </section>
      ) : null}
    </div>
  );
}
