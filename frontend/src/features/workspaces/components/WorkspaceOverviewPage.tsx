import { useMemo } from 'react';
import { useTranslation } from 'react-i18next';
import { Navigate, useParams } from 'react-router-dom';

import { usePageBreadcrumbs } from '@/components/app/useBreadcrumbs';

import { useWorkspacesList } from '../api/useWorkspaces';
import { ActiveAgentsWidget } from './ActiveAgentsWidget';
import { ActiveTasksWidget } from './ActiveTasksWidget';
import { BossActionsWidget } from './BossActionsWidget';
import { LastAgentsActivityWidget } from './LastAgentsActivityWidget';
import { MilestonesWidget } from './MilestonesWidget';
import { ProjectOverviewsWidget } from './ProjectOverviewsWidget';
import { ProjectsWidget } from './ProjectsWidget';
import { TasksProgressWidget } from './TasksProgressWidget';
import { WorkspaceInfoWidget } from './WorkspaceInfoWidget';

export function WorkspaceOverviewPage() {
  const { t } = useTranslation();
  const params = useParams<{ wsId: string }>();
  const workspaceId = params.wsId ?? null;

  const workspacesQuery = useWorkspacesList();
  const workspace = workspacesQuery.data?.find((w) => w.id === workspaceId) ?? null;

  const breadcrumbs = useMemo(
    () => [
      { label: t('workspaces.breadcrumb'), to: '/workspaces' },
      ...(workspace ? [{ label: workspace.name }] : []),
      { label: t('workspaceOverview.breadcrumb') },
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
          {t('workspaceOverview.title')}
        </h1>
        {workspace ? <p className="text-sm text-sc-text-muted">{workspace.name}</p> : null}
      </header>

      <section
        role="region"
        aria-label={t('workspaceOverview.rows.row1')}
        className="grid grid-cols-1 gap-4 md:grid-cols-2 lg:grid-cols-3 xl:grid-cols-5"
      >
        <ActiveTasksWidget />
        <ActiveAgentsWidget />
        <TasksProgressWidget />
        <MilestonesWidget />
        <ProjectsWidget />
      </section>

      <section
        role="region"
        aria-label={t('workspaceOverview.rows.row2')}
        className="grid grid-cols-1 gap-4 md:grid-cols-3"
      >
        <BossActionsWidget />
        <LastAgentsActivityWidget />
        <WorkspaceInfoWidget />
      </section>

      <section
        role="region"
        aria-label={t('workspaceOverview.rows.row3')}
        className="grid grid-cols-1 gap-4"
      >
        <ProjectOverviewsWidget workspaceId={workspaceId} />
      </section>
    </div>
  );
}
