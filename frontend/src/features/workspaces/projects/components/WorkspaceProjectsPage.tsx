import { useMemo } from 'react';
import { useTranslation } from 'react-i18next';
import { Navigate, useParams } from 'react-router-dom';

import { usePageBreadcrumbs } from '@/components/app/useBreadcrumbs';

import { useWorkspacesList } from '../../api/useWorkspaces';

export function WorkspaceProjectsPage() {
  const { t } = useTranslation();
  const params = useParams<{ wsId: string }>();
  const workspaceId = params.wsId ?? null;

  const workspacesQuery = useWorkspacesList();
  const workspace = workspacesQuery.data?.find((w) => w.id === workspaceId) ?? null;

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

      <section
        aria-label={t('workspaceProjects.placeholderAria')}
        className="flex min-h-[18rem] flex-col items-center justify-center gap-3 rounded-lg border border-dashed border-sc-border bg-sc-bg-card/40 p-8 text-center"
      >
        <p className="text-base font-medium text-sc-text">
          {t('workspaceProjects.placeholderTitle')}
        </p>
        <p className="max-w-md text-sm text-sc-text-muted">
          {t('workspaceProjects.placeholderBody')}
        </p>
      </section>
    </div>
  );
}
