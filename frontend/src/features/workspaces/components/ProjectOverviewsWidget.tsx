import { ArrowUpRight, Loader2, Plus } from 'lucide-react';
import { useTranslation } from 'react-i18next';
import { Link } from 'react-router-dom';

import { useProjectsList } from '../projects/api/useProjects';
import { ProjectTile } from '../projects/components/ProjectTile';

const PREVIEW_LIMIT = 7;

interface ProjectOverviewsWidgetProps {
  workspaceId: string;
}

export function ProjectOverviewsWidget({ workspaceId }: ProjectOverviewsWidgetProps) {
  const { t } = useTranslation();
  const query = useProjectsList(workspaceId);
  const projectsPath = `/workspace/${workspaceId}/projects`;
  const openAllAria = t('workspaceOverview.widgets.projectOverviews.openAllAria');

  const preview = query.data?.slice(0, PREVIEW_LIMIT) ?? [];

  return (
    <article
      className="relative flex flex-col gap-4 rounded-lg border border-sc-border bg-sc-bg-card/40 p-4"
      aria-label={t('workspaceOverview.widgets.projectOverviews.sectionAria')}
    >
      <header className="flex items-center justify-between">
        <h2 className="text-sm font-semibold text-sc-text">
          {t('workspaceOverview.slots.projectOverviews.title')}
        </h2>
        <Link
          to={projectsPath}
          aria-label={openAllAria}
          title={openAllAria}
          className="inline-flex h-7 w-7 items-center justify-center rounded-md text-sc-text-muted transition-colors hover:bg-sc-bg-muted hover:text-sc-text focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-sc-text"
        >
          <ArrowUpRight className="h-4 w-4" aria-hidden="true" />
        </Link>
      </header>

      {query.isPending ? (
        <p className="flex items-center gap-2 text-xs text-sc-text-muted">
          <Loader2 className="animate-spin" size={12} aria-hidden="true" />
          {t('workspaceOverview.widgets.projectOverviews.loading')}
        </p>
      ) : null}

      {query.isError ? (
        <p
          role="alert"
          className="rounded-md border border-red-300 bg-red-50 px-3 py-2 text-xs text-red-900"
        >
          {t('workspaceOverview.widgets.projectOverviews.loadError')}
        </p>
      ) : null}

      {query.isSuccess && preview.length === 0 ? (
        <p className="text-xs text-sc-text-muted">
          {t('workspaceOverview.widgets.projectOverviews.empty')}
        </p>
      ) : null}

      {query.isSuccess && preview.length > 0 ? (
        <ul
          className="grid grid-cols-1 gap-3 md:grid-cols-2 xl:grid-cols-4"
          aria-label={t('workspaceOverview.widgets.projectOverviews.listAria')}
        >
          {preview.map((project) => (
            <li key={project.id}>
              <ProjectTile project={project} />
            </li>
          ))}
          <li>
            <Link
              to={projectsPath}
              aria-label={t('workspaceOverview.widgets.projectOverviews.showMoreAria')}
              className="flex h-full min-h-[9rem] flex-col items-center justify-center gap-2 rounded-xl border border-dashed border-sc-border bg-sc-bg-card/40 p-3 text-sc-text-muted transition-colors hover:border-sc-text hover:text-sc-text focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-sc-text"
            >
              <Plus className="h-5 w-5" aria-hidden="true" />
              <span className="text-xs font-semibold uppercase tracking-wider">
                {t('workspaceOverview.widgets.projectOverviews.showMore')}
              </span>
            </Link>
          </li>
        </ul>
      ) : null}
    </article>
  );
}
