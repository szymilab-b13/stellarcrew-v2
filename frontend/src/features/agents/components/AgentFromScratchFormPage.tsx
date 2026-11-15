import { ArrowLeft } from 'lucide-react';
import { useMemo } from 'react';
import { useTranslation } from 'react-i18next';
import { Link, useNavigate } from 'react-router-dom';

import { usePageBreadcrumbs } from '@/components/app/useBreadcrumbs';
import { ApiError } from '@/lib/apiClient';

import { useCreateAgent } from '../api/useAgents';
import { EMPTY_AGENT_FORM_VALUES, type AgentFormValues } from '../lib/agentFormSchema';
import { AgentForm } from './AgentForm';

export function AgentFromScratchFormPage() {
  const { t } = useTranslation();
  const navigate = useNavigate();
  const createMutation = useCreateAgent();

  const breadcrumbs = useMemo(
    () => [
      { label: t('agents.breadcrumb'), to: '/agents' },
      { label: t('agents.fromScratch.breadcrumb') },
    ],
    [t],
  );
  usePageBreadcrumbs(breadcrumbs);

  const serverError = resolveServerError(createMutation.error, t);

  function handleSubmit(values: AgentFormValues) {
    createMutation.reset();
    createMutation.mutate(
      {
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
          to="/agents/new"
          className="inline-flex w-fit items-center gap-1 text-xs font-medium text-sc-text-muted transition-colors hover:text-sc-text"
        >
          <ArrowLeft size={12} aria-hidden />
          {t('agents.fromScratch.back')}
        </Link>
        <h1 className="text-2xl font-semibold tracking-tight text-sc-text">
          {t('agents.fromScratch.title')}
        </h1>
        <p className="text-sm text-sc-text-muted">{t('agents.fromScratch.subtitle')}</p>
      </header>

      <section
        aria-label={t('agents.fromScratch.sectionAria')}
        className="flex flex-col gap-4 rounded-xl border border-sc-border bg-sc-bg-card p-6"
      >
        <AgentForm
          defaultValues={EMPTY_AGENT_FORM_VALUES}
          onSubmit={handleSubmit}
          isSubmitting={createMutation.isPending}
          serverError={serverError}
          submitLabel={t('agents.fromScratch.submit')}
          submittingLabel={t('agents.fromScratch.submitting')}
        />
      </section>
    </div>
  );
}

function resolveServerError(error: unknown, t: (key: string) => string): string | null {
  if (!(error instanceof ApiError)) return null;
  if (error.status === 401) return t('agents.form.errors.server.unauthorized');
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
