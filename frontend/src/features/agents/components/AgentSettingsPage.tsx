import { ArrowLeft, Loader2, Trash2 } from 'lucide-react';
import { useMemo, useState } from 'react';
import { useTranslation } from 'react-i18next';
import { Link, Navigate, useNavigate, useParams } from 'react-router-dom';

import { usePageBreadcrumbs } from '@/components/app/useBreadcrumbs';
import { Button } from '@/components/ui/Button';
import { ApiError } from '@/lib/apiClient';

import { useAgent, useDeleteAgent, useUpdateAgent } from '../api/useAgents';
import type { AgentFormValues } from '../lib/agentFormSchema';
import type { Agent } from '../types';
import { AgentForm } from './AgentForm';
import { DeleteAgentConfirmDialog } from './DeleteAgentConfirmDialog';

export function AgentSettingsPage() {
  const { t } = useTranslation();
  const params = useParams<{ id: string }>();
  const agentId = params.id ?? null;
  const navigate = useNavigate();

  const agentQuery = useAgent(agentId);
  const agent = agentQuery.data ?? null;

  const updateMutation = useUpdateAgent();
  const deleteMutation = useDeleteAgent();
  const [toDelete, setToDelete] = useState<Agent | null>(null);
  const [savedAt, setSavedAt] = useState<number | null>(null);

  const breadcrumbs = useMemo(
    () => [
      { label: t('agents.breadcrumb'), to: '/agents' },
      ...(agent ? [{ label: agent.name, to: `/agents/${agent.id}/overview` }] : []),
      { label: t('agents.settings.breadcrumb') },
    ],
    [t, agent],
  );
  usePageBreadcrumbs(breadcrumbs);

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

  const serverError = resolveSaveServerMessage(updateMutation.error, t);

  function handleSubmit(values: AgentFormValues) {
    if (!agent) return;
    updateMutation.reset();
    updateMutation.mutate(
      {
        agentId: agent.id,
        input: {
          name: values.name,
          role: values.role,
          roleDescription: values.roleDescription,
          expertise: values.expertise,
          workflow: values.workflow,
          style: values.style,
          outputFormat: values.outputFormat,
        },
      },
      {
        onSuccess: () => {
          setSavedAt(Date.now());
        },
      },
    );
  }

  function openDelete() {
    if (!agent) return;
    deleteMutation.reset();
    setToDelete(agent);
  }

  function closeDelete() {
    setToDelete(null);
  }

  function handleDeleteConfirm() {
    if (!toDelete) return;
    deleteMutation.mutate(
      { agentId: toDelete.id },
      {
        onSuccess: () => {
          setToDelete(null);
          navigate('/agents', {
            state: { deletedAgentName: toDelete.name },
            replace: true,
          });
        },
      },
    );
  }

  return (
    <div className="flex w-full flex-col gap-6">
      <header className="flex flex-col gap-2">
        <Link
          to={agent ? `/agents/${agent.id}/overview` : '/agents'}
          className="inline-flex w-fit items-center gap-1 text-xs font-medium text-sc-text-muted transition-colors hover:text-sc-text"
        >
          <ArrowLeft size={12} aria-hidden />
          {t('agents.settings.back')}
        </Link>
        <h1 className="text-2xl font-semibold tracking-tight text-sc-text">
          {t('agents.settings.title')}
        </h1>
        {agent ? (
          <p className="text-sm text-sc-text-muted">
            {t('agents.settings.subtitle', { name: agent.name })}
          </p>
        ) : null}
      </header>

      {agentQuery.isPending ? (
        <p className="flex items-center gap-2 text-sm text-sc-text-muted">
          <Loader2 className="animate-spin" size={14} aria-hidden />
          {t('agents.settings.loading')}
        </p>
      ) : null}

      {forbidden ? (
        <p
          role="alert"
          className="rounded-md border border-red-300 bg-red-50 px-3 py-2 text-sm text-red-900"
        >
          {t('agents.settings.forbidden')}
        </p>
      ) : null}

      {genericError ? (
        <p
          role="alert"
          className="rounded-md border border-red-300 bg-red-50 px-3 py-2 text-sm text-red-900"
        >
          {t('agents.settings.loadError')}
        </p>
      ) : null}

      {agent ? (
        <>
          {savedAt !== null && !updateMutation.isPending && !updateMutation.isError ? (
            <p
              role="status"
              className="rounded-md border border-emerald-400/40 bg-emerald-500/10 px-3 py-2 text-sm text-emerald-700 dark:text-emerald-300"
            >
              {t('agents.settings.saved')}
            </p>
          ) : null}

          <section
            aria-label={t('agents.settings.sectionAria')}
            className="flex flex-col gap-4 rounded-xl border border-sc-border bg-sc-bg-card p-6"
          >
            <AgentForm
              key={agent.id}
              defaultValues={{
                name: agent.name,
                role: agent.role,
                slug: agent.slug,
                roleDescription: agent.roleDescription,
                expertise: agent.expertise,
                workflow: agent.workflow,
                style: agent.style,
                outputFormat: agent.outputFormat,
              }}
              onSubmit={handleSubmit}
              isSubmitting={updateMutation.isPending}
              serverError={serverError}
              submitLabel={t('agents.settings.submit')}
              submittingLabel={t('agents.settings.saving')}
              slugLocked
            />
          </section>

          <section
            aria-label={t('agents.settings.dangerZone.sectionAria')}
            className="flex flex-col gap-3 rounded-xl border border-sc-danger/40 bg-sc-danger/5 p-6"
          >
            <header className="flex flex-col gap-1">
              <h2 className="text-lg font-semibold text-sc-danger">
                {t('agents.settings.dangerZone.title')}
              </h2>
              <p className="text-sm text-sc-text-muted">
                {t('agents.settings.dangerZone.body', { name: agent.name })}
              </p>
            </header>
            <div>
              <Button
                variant="destructive"
                onClick={openDelete}
                leadingIcon={<Trash2 size={14} aria-hidden />}
              >
                {t('agents.settings.dangerZone.deleteButton')}
              </Button>
            </div>
          </section>
        </>
      ) : null}

      {toDelete ? (
        <DeleteAgentConfirmDialog
          agent={toDelete}
          isSubmitting={deleteMutation.isPending}
          error={deleteMutation.error}
          onClose={closeDelete}
          onConfirm={handleDeleteConfirm}
        />
      ) : null}
    </div>
  );
}

function resolveSaveServerMessage(error: unknown, t: (key: string) => string): string | null {
  if (!(error instanceof ApiError)) return null;
  if (error.status === 401) return t('agents.form.errors.server.unauthorized');
  if (error.status === 403) return t('agents.settings.errors.forbidden');
  if (error.status === 404) return t('agents.settings.errors.notFound');
  if (error.status === 409) {
    const body = (error.body ?? null) as { code?: string } | null;
    if (body?.code === 'AGENT_SLUG_ALREADY_TAKEN') return t('agents.form.errors.server.slugTaken');
    return t('agents.form.errors.server.conflict');
  }
  if (error.status === 422) return t('agents.form.errors.server.validation');
  return t('agents.form.errors.server.generic');
}
