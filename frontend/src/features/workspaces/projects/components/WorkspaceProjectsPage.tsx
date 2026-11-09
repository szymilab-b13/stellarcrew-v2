import { Loader2, X } from 'lucide-react';
import { useEffect, useMemo, useState } from 'react';
import { useTranslation } from 'react-i18next';
import { Link, Navigate, useLocation, useNavigate, useParams } from 'react-router-dom';

import { usePageBreadcrumbs } from '@/components/app/useBreadcrumbs';
import { Button } from '@/components/ui/Button';

import { useWorkspacesList } from '../../api/useWorkspaces';
import { useCreateProject, useProjectsList } from '../api/useProjects';
import { AddProjectTile } from './AddProjectTile';
import { ProjectDialog, type ProjectFormValues } from './ProjectDialog';
import { ProjectTile } from './ProjectTile';

interface LocationState {
  deletedProjectName?: string;
}

export function WorkspaceProjectsPage() {
  const { t } = useTranslation();
  const navigate = useNavigate();
  const location = useLocation();
  const params = useParams<{ wsId: string }>();
  const workspaceId = params.wsId ?? null;

  const workspacesQuery = useWorkspacesList();
  const workspace = workspacesQuery.data?.find((w) => w.id === workspaceId) ?? null;
  const projectsQuery = useProjectsList(workspaceId);
  const createMutation = useCreateProject();

  const navState = (location.state ?? null) as LocationState | null;
  const [deletedBanner, setDeletedBanner] = useState<string | null>(
    navState?.deletedProjectName ?? null,
  );
  useEffect(() => {
    if (navState?.deletedProjectName) {
      navigate(location.pathname, { replace: true, state: null });
    }
  }, [navState, navigate, location.pathname]);

  function dismissBanner() {
    setDeletedBanner(null);
  }

  const breadcrumbs = useMemo(
    () => [
      { label: t('workspaces.breadcrumb'), to: '/workspaces' },
      ...(workspace ? [{ label: workspace.name }] : []),
      { label: t('workspaceProjects.breadcrumb') },
    ],
    [t, workspace],
  );
  usePageBreadcrumbs(breadcrumbs);

  const [isDialogOpen, setIsDialogOpen] = useState(false);

  function openDialog() {
    createMutation.reset();
    setIsDialogOpen(true);
  }

  function closeDialog() {
    setIsDialogOpen(false);
  }

  function handleSubmit(values: ProjectFormValues) {
    if (!workspaceId) return;
    createMutation.mutate(
      {
        workspaceId,
        input: {
          name: values.name,
          slug: values.slug,
          description: values.description.length > 0 ? values.description : null,
        },
      },
      {
        onSuccess: (project) => {
          setIsDialogOpen(false);
          navigate(`/workspace/${workspaceId}/project/${project.id}/overview`);
        },
      },
    );
  }

  if (workspaceId === null) {
    return <Navigate to="/workspaces" replace />;
  }

  if (workspacesQuery.isSuccess && workspace === null) {
    return <Navigate to="/workspaces" replace />;
  }

  return (
    <div className="flex w-full flex-col gap-6">
      <header className="flex items-start justify-between gap-3">
        <div className="flex flex-col gap-1">
          <h1 className="text-2xl font-semibold tracking-tight text-sc-text">
            {t('workspaceProjects.title')}
          </h1>
          {workspace ? <p className="text-sm text-sc-text-muted">{workspace.name}</p> : null}
        </div>
        <Button variant="primary" onClick={openDialog}>
          {t('projects.addTile.label')}
        </Button>
      </header>

      {deletedBanner ? (
        <div
          role="status"
          className="flex items-start justify-between gap-3 rounded-md border border-emerald-400/40 bg-emerald-500/10 px-3 py-2 text-sm text-emerald-700 dark:text-emerald-300"
        >
          <p>
            {t('projects.deletedBanner.body', { name: deletedBanner })}{' '}
            <Link to="/admin/audit" className="font-semibold underline">
              {t('projects.deletedBanner.undoLink')}
            </Link>
          </p>
          <button
            type="button"
            onClick={dismissBanner}
            aria-label={t('projects.deletedBanner.dismiss')}
            className="inline-flex h-6 w-6 flex-none items-center justify-center rounded-md text-emerald-700 hover:bg-emerald-500/20 dark:text-emerald-300"
          >
            <X size={14} aria-hidden />
          </button>
        </div>
      ) : null}

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
          className="flex min-h-[18rem] flex-col items-center justify-center gap-3 rounded-lg border border-dashed border-sc-border bg-sc-bg-card/40 p-8 text-center"
        >
          <p className="text-base font-medium text-sc-text">{t('projects.emptyTitle')}</p>
          <p className="max-w-md text-sm text-sc-text-muted">{t('projects.emptyBody')}</p>
          <Button variant="primary" onClick={openDialog}>
            {t('projects.addTile.label')}
          </Button>
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
          <AddProjectTile onClick={openDialog} />
        </section>
      ) : null}

      {isDialogOpen ? (
        <ProjectDialog
          isSubmitting={createMutation.isPending}
          error={createMutation.error}
          onClose={closeDialog}
          onSubmit={handleSubmit}
        />
      ) : null}
    </div>
  );
}
