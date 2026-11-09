import { Loader2 } from 'lucide-react';
import { useMemo } from 'react';
import { useTranslation } from 'react-i18next';
import { Navigate, useParams } from 'react-router-dom';

import { usePageBreadcrumbs } from '@/components/app/useBreadcrumbs';

import { useWorkspacesList } from '../../api/useWorkspaces';
import { useProjectsList } from '../api/useProjects';
import { ProjectTile } from './ProjectTile';

export function WorkspaceProjectsPage() {
  const { t } = useTranslation();
  const params = useParams<{ wsId: string }>();
  const workspaceId = params.wsId ?? null;

  const workspacesQuery = useWorkspacesList();
  const workspace = workspacesQuery.data?.find((w) => w.id === workspaceId) ?? null;
  const projectsQuery = useProjectsList(workspaceId);

  const breadcrumbs = useMemo(
    () => [
      { label: t('workspaces.breadcrumb'), to: '/workspaces' },
      ...(workspace ? [{ label: workspace.name }] : []),
      { label: t('workspaceProjects.breadcrumb') },
    ],
    [t, workspace],
  );
  usePageBreadcrumbs(breadcrumbs);

  if (workspaceId === null) {
    return <Navigate to="/workspaces" replace />;
  }

  if (workspacesQuery.isSuccess && workspace === null) {
    return <Navigate to="/workspaces" replace />;
  }

  return (
    <div className="flex w-full flex-col gap-6">
      <header className="flex flex-col gap-1">
        <h1 className="text-2xl font-semibold tracking-tight text-sc-text">
          {t('workspaceProjects.title')}
        </h1>
        {workspace ? <p className="text-sm text-sc-text-muted">{workspace.name}</p> : null}
      </header>

      {projectsQuery.isPending ? (
        <p className="flex items-center gap-2 text-sm text-sc-text-muted">
          <Loader2 className="animate-spin" size={14} aria-hidden />
          {t('projects.loading')}
        </p>
      ) : null}

      {projectsQuery.isError ? (
        <p
          role="alert"
          className="rounded-md border border-red-300 bg-red-50 px-3 py-2 text-sm text-red-900"
        >
          {t('projects.loadError')}
        </p>
      ) : null}

      {projectsQuery.isSuccess && projectsQuery.data.length === 0 ? (
        <section
          aria-label={t('projects.emptyAria')}
          className="flex min-h-[18rem] flex-col items-center justify-center gap-2 rounded-lg border border-dashed border-sc-border bg-sc-bg-card/40 p-8 text-center"
        >
          <p className="text-base font-medium text-sc-text">{t('projects.emptyTitle')}</p>
          <p className="max-w-md text-sm text-sc-text-muted">{t('projects.emptyBody')}</p>
        </section>
      ) : null}

      {projectsQuery.isSuccess && projectsQuery.data.length > 0 ? (
        <section
          aria-label={t('projects.listAria')}
          className="grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4"
        >
          {projectsQuery.data.map((project) => (
            <ProjectTile key={project.id} project={project} />
          ))}
        </section>
      ) : null}
    </div>
  );
}
