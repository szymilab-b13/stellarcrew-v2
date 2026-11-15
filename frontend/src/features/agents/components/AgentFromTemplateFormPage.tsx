import { ArrowLeft, Loader2 } from 'lucide-react';
import { useMemo } from 'react';
import { useTranslation } from 'react-i18next';
import { Link, Navigate, useNavigate, useParams } from 'react-router-dom';

import { usePageBreadcrumbs } from '@/components/app/useBreadcrumbs';
import { ApiError } from '@/lib/apiClient';

import { useCreateAgentFromTemplate } from '../api/useAgents';
import { useAgentTemplate } from '../api/useAgentTemplates';
import { type AgentFormValues } from '../lib/agentFormSchema';
import { toAgentSlug } from '../lib/agentSlug';
import { AgentForm } from './AgentForm';

export function AgentFromTemplateFormPage() {
  const { t } = useTranslation();
  const params = useParams<{ slug: string }>();
  const templateSlug = params.slug ?? null;
  const navigate = useNavigate();

  const templateQuery = useAgentTemplate(templateSlug);
  const createMutation = useCreateAgentFromTemplate();

  const breadcrumbs = useMemo(
    () => [
      { label: t('agents.breadcrumb'), to: '/agents' },
      { label: t('agents.templatePicker.breadcrumb'), to: '/agents/new/template' },
      {
        label: templateQuery.data
          ? templateQuery.data.name
          : t('agents.fromTemplate.breadcrumbFallback'),
      },
    ],
    [t, templateQuery.data],
  );
  usePageBreadcrumbs(breadcrumbs);

  if (templateSlug === null) {
    return <Navigate to="/agents/new/template" replace />;
  }

  const notFound =
    templateQuery.isError &&
    templateQuery.error instanceof ApiError &&
    templateQuery.error.status === 404;
  if (notFound) {
    return <Navigate to="/agents/new/template" replace />;
  }

  const serverError = resolveServerError(createMutation.error, t);

  function handleSubmit(values: AgentFormValues) {
    if (templateSlug === null) return;
    createMutation.reset();
    createMutation.mutate(
      {
        templateSlug,
        name: values.name,
        role: values.role,
        slug: values.slug,
        roleDescription: values.roleDescription,
        expertise: values.expertise,
        workflow: values.workflow,
        style: values.style,
        outputFormat: values.outputFormat,
      },
      {
        onSuccess: (agent) => {
          navigate(`/agents/${agent.id}/overview`, { replace: true });
        },
      },
    );
  }

  return (
    <div className="flex w-full flex-col gap-6">
      <header className="flex flex-col gap-2">
        <Link
          to="/agents/new/template"
          className="inline-flex w-fit items-center gap-1 text-xs font-medium text-sc-text-muted transition-colors hover:text-sc-text"
        >
          <ArrowLeft size={12} aria-hidden />
          {t('agents.fromTemplate.back')}
        </Link>
        <h1 className="text-2xl font-semibold tracking-tight text-sc-text">
          {templateQuery.data
            ? t('agents.fromTemplate.title', { name: templateQuery.data.name })
            : t('agents.fromTemplate.titleFallback')}
        </h1>
        <p className="text-sm text-sc-text-muted">{t('agents.fromTemplate.subtitle')}</p>
      </header>

      {templateQuery.isPending ? (
        <p className="flex items-center gap-2 text-sm text-sc-text-muted">
          <Loader2 className="animate-spin" size={14} aria-hidden />
          {t('agents.fromTemplate.loading')}
        </p>
      ) : null}

      {templateQuery.isError && !notFound ? (
        <p
          role="alert"
          className="rounded-md border border-red-300 bg-red-50 px-3 py-2 text-sm text-red-900"
        >
          {t('agents.fromTemplate.loadError')}
        </p>
      ) : null}

      {templateQuery.isSuccess ? (
        <section
          aria-label={t('agents.fromTemplate.sectionAria')}
          className="flex flex-col gap-4 rounded-xl border border-sc-border bg-sc-bg-card p-6"
        >
          <AgentForm
            key={templateQuery.data.id}
            defaultValues={{
              name: templateQuery.data.name,
              role: templateQuery.data.role,
              slug: toAgentSlug(templateQuery.data.name),
              roleDescription: templateQuery.data.roleDescription,
              expertise: templateQuery.data.expertise,
              workflow: templateQuery.data.workflow,
              style: templateQuery.data.style,
              outputFormat: templateQuery.data.outputFormat,
            }}
            onSubmit={handleSubmit}
            isSubmitting={createMutation.isPending}
            serverError={serverError}
            submitLabel={t('agents.fromTemplate.submit')}
            submittingLabel={t('agents.fromTemplate.submitting')}
          />
        </section>
      ) : null}
    </div>
  );
}

function resolveServerError(error: unknown, t: (key: string) => string): string | null {
  if (!(error instanceof ApiError)) return null;
  if (error.status === 401) return t('agents.form.errors.server.unauthorized');
  if (error.status === 404) return t('agents.form.errors.server.templateNotFound');
  if (error.status === 409) {
    const body = (error.body ?? null) as { code?: string } | null;
    if (body?.code === 'AGENT_SLUG_ALREADY_TAKEN') return t('agents.form.errors.server.slugTaken');
    if (body?.code === 'AGENT_LIMIT_REACHED') return t('agents.form.errors.server.limitReached');
    if (body?.code === 'OWNER_HAS_NO_ORGANIZATION') {
      return t('agents.form.errors.server.noOrganization');
    }
    return t('agents.form.errors.server.conflict');
  }
  if (error.status === 422) return t('agents.form.errors.server.validation');
  return t('agents.form.errors.server.generic');
}
