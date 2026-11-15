import { Loader2, Settings, Sparkles } from 'lucide-react';
import { useMemo } from 'react';
import { useTranslation } from 'react-i18next';
import { Link, Navigate, useParams } from 'react-router-dom';

import { usePageBreadcrumbs } from '@/components/app/useBreadcrumbs';
import { ApiError } from '@/lib/apiClient';

import { useAgent } from '../api/useAgents';
import { useAgentTemplate } from '../api/useAgentTemplates';
import { getAgentAvatar } from '../lib/agentAvatar';

type SectionKey = 'roleDescription' | 'expertise' | 'workflow' | 'style' | 'outputFormat';

const SECTION_KEYS: readonly SectionKey[] = [
  'roleDescription',
  'expertise',
  'workflow',
  'style',
  'outputFormat',
];

export function AgentOverviewPage() {
  const { t, i18n } = useTranslation();
  const params = useParams<{ id: string }>();
  const agentId = params.id ?? null;

  const agentQuery = useAgent(agentId);
  const agent = agentQuery.data ?? null;

  const templateId = agent?.sourceTemplateId ?? null;
  const templateQuery = useAgentTemplate(templateId);
  const template = templateQuery.data ?? null;

  const breadcrumbs = useMemo(
    () => [
      { label: t('agents.breadcrumb'), to: '/agents' },
      ...(agent ? [{ label: agent.name }] : []),
      { label: t('agents.overview.breadcrumb') },
    ],
    [t, agent],
  );
  usePageBreadcrumbs(breadcrumbs);

  const dateFormatter = new Intl.DateTimeFormat(i18n.resolvedLanguage ?? 'en', {
    year: 'numeric',
    month: 'short',
    day: 'numeric',
  });

  if (agentId === null) {
    return <Navigate to="/agents" replace />;
  }

  const notFound =
    agentQuery.isError && agentQuery.error instanceof ApiError && agentQuery.error.status === 404;
  if (notFound) {
    return <Navigate to="/agents" replace />;
  }

  const forbidden =
    agentQuery.isError && agentQuery.error instanceof ApiError && agentQuery.error.status === 403;
  const genericError = agentQuery.isError && !forbidden && !notFound;

  return (
    <div className="flex w-full flex-col gap-6">
      {agentQuery.isPending ? (
        <p className="flex items-center gap-2 text-sm text-sc-text-muted">
          <Loader2 className="animate-spin" size={14} aria-hidden />
          {t('agents.overview.loading')}
        </p>
      ) : null}

      {forbidden ? (
        <p
          role="alert"
          className="rounded-md border border-red-300 bg-red-50 px-3 py-2 text-sm text-red-900"
        >
          {t('agents.overview.forbidden')}
        </p>
      ) : null}

      {genericError ? (
        <p
          role="alert"
          className="rounded-md border border-red-300 bg-red-50 px-3 py-2 text-sm text-red-900"
        >
          {t('agents.overview.loadError')}
        </p>
      ) : null}

      {agent ? (
        <>
          <AgentOverviewHeader agent={agent} templateSlug={template?.slug ?? null} />

          <section
            aria-label={t('agents.overview.metadata.title')}
            className="grid grid-cols-1 gap-3 rounded-xl border border-sc-border bg-sc-bg-card p-5 sm:grid-cols-2 lg:grid-cols-4"
          >
            <MetadataField label={t('agents.overview.metadata.slug')} value={agent.slug} />
            <MetadataField
              label={t('agents.overview.metadata.createdAt')}
              value={dateFormatter.format(new Date(agent.createdAt))}
            />
            <MetadataField
              label={t('agents.overview.metadata.updatedAt')}
              value={dateFormatter.format(new Date(agent.updatedAt))}
            />
            <MetadataField
              label={t('agents.overview.metadata.sourceTemplate')}
              value={
                template
                  ? t('agents.overview.metadata.sourceTemplateValue', { name: template.name })
                  : t('agents.overview.metadata.sourceTemplateEmpty')
              }
            />
          </section>

          <section aria-label={t('agents.overview.promptAria')} className="flex flex-col gap-4">
            {SECTION_KEYS.map((key) => (
              <ReadOnlySection
                key={key}
                title={t(`agents.overview.sections.${key}`)}
                body={agent[key]}
              />
            ))}
          </section>

          <aside
            role="region"
            aria-label={t('agents.overview.activity.title')}
            className="relative flex flex-col gap-2 rounded-xl border border-dashed border-sc-border bg-sc-bg-card/40 p-5"
          >
            <span className="absolute right-3 top-3 rounded-full bg-sc-bg-muted px-2 py-0.5 text-[10px] font-semibold uppercase tracking-wide text-sc-text-muted">
              {t('agents.overview.activity.prototype')}
            </span>
            <h2 className="text-base font-semibold text-sc-text">
              {t('agents.overview.activity.title')}
            </h2>
            <p className="text-sm text-sc-text-muted">{t('agents.overview.activity.body')}</p>
            <p className="mt-auto text-xs text-sc-text-muted">
              {t('agents.overview.activity.comingSoon')}
            </p>
          </aside>
        </>
      ) : null}
    </div>
  );
}

interface AgentOverviewHeaderProps {
  agent: {
    id: string;
    slug: string;
    name: string;
    role: string;
    sourceTemplateId: string | null;
  };
  templateSlug: string | null;
}

function AgentOverviewHeader({ agent, templateSlug }: AgentOverviewHeaderProps) {
  const { t } = useTranslation();
  const avatar = getAgentAvatar(agent.slug, agent.name);

  return (
    <header className="flex flex-col gap-4 sm:flex-row sm:items-start sm:justify-between">
      <div className="flex min-w-0 items-start gap-4">
        <span
          aria-hidden
          className={`inline-flex h-12 w-12 flex-none items-center justify-center rounded-xl text-base font-semibold ${avatar.bgClass} ${avatar.textClass}`}
        >
          {avatar.initials}
        </span>
        <div className="flex min-w-0 flex-col gap-1">
          <h1 className="truncate text-2xl font-semibold tracking-tight text-sc-text">
            {agent.name}
          </h1>
          <p className="truncate text-sm text-sc-text-muted">{agent.role}</p>
          {agent.sourceTemplateId && templateSlug ? (
            <Link
              to={`/agents/new/template/${templateSlug}`}
              aria-label={t('agents.overview.sourceTemplateAria', { name: agent.name })}
              className="inline-flex w-fit items-center gap-1 rounded-full border border-sc-border bg-sc-bg px-2 py-0.5 text-[11px] font-medium text-sc-text-muted transition-colors hover:text-sc-text"
            >
              <Sparkles size={11} aria-hidden />
              {t('agents.overview.sourceTemplateBadge')}
            </Link>
          ) : null}
        </div>
      </div>
      <Link
        to={`/agents/${agent.id}/settings`}
        aria-label={t('agents.overview.openSettingsAria')}
        className="inline-flex w-fit items-center gap-2 rounded-md border border-sc-border bg-sc-bg px-3 py-1.5 text-xs font-medium text-sc-text transition-colors hover:border-sc-text-muted"
      >
        <Settings size={14} aria-hidden />
        {t('agents.overview.openSettings')}
      </Link>
    </header>
  );
}

interface MetadataFieldProps {
  label: string;
  value: string;
}

function MetadataField({ label, value }: MetadataFieldProps) {
  return (
    <div className="flex flex-col gap-1">
      <span className="text-[11px] font-semibold uppercase tracking-wide text-sc-text-muted">
        {label}
      </span>
      <span className="text-sm text-sc-text">{value}</span>
    </div>
  );
}

interface ReadOnlySectionProps {
  title: string;
  body: string;
}

function ReadOnlySection({ title, body }: ReadOnlySectionProps) {
  return (
    <article className="flex flex-col gap-2 rounded-xl border border-sc-border bg-sc-bg-card p-5">
      <h2 className="text-sm font-semibold uppercase tracking-wide text-sc-text-muted">{title}</h2>
      <p className="whitespace-pre-wrap text-sm text-sc-text">{body}</p>
    </article>
  );
}
