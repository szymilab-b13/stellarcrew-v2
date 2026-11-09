import { useTranslation } from 'react-i18next';
import type { LucideIcon } from 'lucide-react';
import { Archive, FolderKanban, FolderOpen, PauseCircle } from 'lucide-react';

type ProjectStatus = 'active' | 'planned' | 'paused' | 'archived';

type SampleProject = {
  id: string;
  status: ProjectStatus;
};

const SAMPLE_PROJECTS: SampleProject[] = [
  { id: 'sample-0', status: 'active' },
  { id: 'sample-1', status: 'planned' },
  { id: 'sample-2', status: 'paused' },
  { id: 'sample-3', status: 'archived' },
];

const STATUS_ICONS: Record<ProjectStatus, LucideIcon> = {
  active: FolderOpen,
  planned: FolderKanban,
  paused: PauseCircle,
  archived: Archive,
};

export function ProjectsWidget() {
  const { t } = useTranslation();

  return (
    <article className="relative flex min-h-[9rem] flex-col gap-3 rounded-lg border border-dashed border-sc-border bg-sc-bg-card/40 p-4">
      <span
        aria-label={t('workspaceOverview.prototypeBadge')}
        className="absolute right-3 top-3 rounded-full bg-sc-bg-muted px-2 py-0.5 text-[10px] font-semibold uppercase tracking-wider text-sc-text-muted"
      >
        {t('workspaceOverview.prototypeBadge')}
      </span>
      <h2 className="pr-16 text-sm font-semibold text-sc-text">
        {t('workspaceOverview.slots.projects.title')}
      </h2>
      <ul
        className="flex flex-col gap-1.5 opacity-60"
        aria-label={t('workspaceOverview.widgets.projects.previewLabel')}
      >
        {SAMPLE_PROJECTS.map((project, index) => {
          const Icon = STATUS_ICONS[project.status];
          return (
            <li key={project.id} className="flex items-center gap-2 text-xs text-sc-text-muted">
              <Icon className="h-3.5 w-3.5 shrink-0 text-sc-text-muted" aria-hidden="true" />
              <span className="truncate">
                {t(`workspaceOverview.widgets.projects.samples.${index}`)}
              </span>
              <span className="ml-auto rounded-full bg-sc-bg-muted px-1.5 py-0.5 text-[10px] font-semibold uppercase tracking-wider text-sc-text-muted">
                {t(`workspaceOverview.widgets.projects.statuses.${project.status}`)}
              </span>
            </li>
          );
        })}
      </ul>
      <p className="mt-auto text-xs text-sc-text-muted">
        {t('workspaceOverview.widgets.projects.comingAfter')}
      </p>
    </article>
  );
}
