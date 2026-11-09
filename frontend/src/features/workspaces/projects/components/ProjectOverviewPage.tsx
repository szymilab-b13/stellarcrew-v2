import { Loader2 } from 'lucide-react';
import { useMemo } from 'react';
import { useTranslation } from 'react-i18next';
import { Navigate, useParams } from 'react-router-dom';

import { usePageBreadcrumbs } from '@/components/app/useBreadcrumbs';
import { ApiError } from '@/lib/apiClient';

import { useWorkspacesList } from '../../api/useWorkspaces';
import { useProject } from '../api/useProjects';

export function ProjectOverviewPage() {
  const { t } = useTranslation();
  const params = useParams<{ wsId: string; projId: string }>();
  const workspaceId = params.wsId ?? null;
  const projectId = params.projId ?? null;

  const workspacesQuery = useWorkspacesList();
  const workspace = workspacesQuery.data?.find((w) => w.id === workspaceId) ?? null;
  const projectQuery = useProject(projectId);
  const project = projectQuery.data ?? null;

  const breadcrumbs = useMemo(
    () => [
      { label: t('workspaces.breadcrumb'), to: '/workspaces' },
      ...(workspace ? [{ label: workspace.name, to: `/workspace/${workspace.id}/projects` }] : []),
      ...(project ? [{ label: project.name }] : []),
      { label: t('projectOverview.breadcrumb') },
    ],
    [t, workspace, project],
  );
  usePageBreadcrumbs(breadcrumbs);

  if (workspaceId === null || projectId === null) {
    return <Navigate to="/workspaces" replace />;
  }

  if (workspacesQuery.isSuccess && workspace === null) {
    return <Navigate to="/workspaces" replace />;
  }

  if (
    projectQuery.isError &&
    projectQuery.error instanceof ApiError &&
    projectQuery.error.status === 404
  ) {
    return <Navigate to={`/workspace/${workspaceId}/projects`} replace />;
  }

  return (
    <div className="flex w-full flex-col gap-6">
      <header className="flex flex-col gap-1">
        <h1 className="text-2xl font-semibold tracking-tight text-sc-text">
          {project ? project.name : t('projectOverview.title')}
        </h1>
        {project ? <p className="text-sm text-sc-text-muted">{project.slug}</p> : null}
        {project?.description ? (
          <p className="max-w-3xl text-sm text-sc-text-muted">{project.description}</p>
        ) : null}
      </header>

      {projectQuery.isPending ? (
        <p className="flex items-center gap-2 text-sm text-sc-text-muted">
          <Loader2 className="animate-spin" size={14} aria-hidden />
          {t('projectOverview.loading')}
        </p>
      ) : null}

      {projectQuery.isError &&
      !(projectQuery.error instanceof ApiError && projectQuery.error.status === 404) ? (
        <p
          role="alert"
          className="rounded-md border border-red-300 bg-red-50 px-3 py-2 text-sm text-red-900"
        >
          {t('projectOverview.loadError')}
        </p>
      ) : null}

      {project ? (
        <section
          role="region"
          aria-label={t('projectOverview.shellAria')}
          className="grid grid-cols-1 gap-4 md:grid-cols-2 lg:grid-cols-3"
        >
          <ProjectOverviewPlaceholder slotKey="agents" />
          <ProjectOverviewPlaceholder slotKey="tasks" />
          <ProjectOverviewPlaceholder slotKey="milestones" />
          <ProjectOverviewPlaceholder slotKey="knowledge" />
          <ProjectOverviewPlaceholder slotKey="activity" />
          <ProjectOverviewPlaceholder slotKey="settings" />
        </section>
      ) : null}
    </div>
  );
}

interface ProjectOverviewPlaceholderProps {
  slotKey: 'agents' | 'tasks' | 'milestones' | 'knowledge' | 'activity' | 'settings';
}

function ProjectOverviewPlaceholder({ slotKey }: ProjectOverviewPlaceholderProps) {
  const { t } = useTranslation();
  return (
    <article
      aria-label={t(`projectOverview.slots.${slotKey}.title`)}
      className="relative flex min-h-[9rem] flex-col gap-2 rounded-xl border border-dashed border-sc-border bg-sc-bg-card/40 p-4"
    >
      <span className="absolute right-3 top-3 rounded-full bg-sc-bg-muted px-2 py-0.5 text-[10px] font-semibold uppercase tracking-wide text-sc-text-muted">
        {t('projectOverview.prototype')}
      </span>
      <h2 className="text-base font-semibold text-sc-text">
        {t(`projectOverview.slots.${slotKey}.title`)}
      </h2>
      <p className="text-sm text-sc-text-muted">{t(`projectOverview.slots.${slotKey}.body`)}</p>
      <p className="mt-auto text-xs text-sc-text-muted">{t('projectOverview.comingSoon')}</p>
    </article>
  );
}
