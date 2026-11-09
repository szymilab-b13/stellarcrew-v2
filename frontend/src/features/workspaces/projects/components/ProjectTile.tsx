import { useTranslation } from 'react-i18next';

import type { Project } from '../types';

interface ProjectTileProps {
  project: Project;
}

export function ProjectTile({ project }: ProjectTileProps) {
  const { t, i18n } = useTranslation();

  const dateFormatter = new Intl.DateTimeFormat(i18n.resolvedLanguage ?? 'en', {
    year: 'numeric',
    month: 'short',
    day: 'numeric',
  });
  const createdAtLabel = dateFormatter.format(new Date(project.createdAt));

  return (
    <div
      role="group"
      aria-label={t('projects.tile.aria', { name: project.name })}
      className="flex min-h-[9rem] flex-col gap-2 rounded-xl border border-sc-border bg-sc-bg-card p-4 transition-colors hover:border-sc-text-muted"
    >
      <div className="flex min-w-0 flex-col gap-1">
        <h2 className="truncate text-base font-semibold text-sc-text">{project.name}</h2>
        <p className="truncate text-xs text-sc-text-muted">{project.slug}</p>
      </div>
      {project.description ? (
        <p className="line-clamp-2 text-sm text-sc-text-muted">{project.description}</p>
      ) : null}
      <p className="mt-auto text-xs text-sc-text-muted">
        {t('projects.tile.createdAt', { date: createdAtLabel })}
      </p>
    </div>
  );
}
