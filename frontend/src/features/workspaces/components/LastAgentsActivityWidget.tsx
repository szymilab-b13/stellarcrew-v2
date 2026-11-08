import { useTranslation } from 'react-i18next';
import type { LucideIcon } from 'lucide-react';
import { Circle, GitMerge, Loader2 } from 'lucide-react';

type ActivityStatus = 'merged' | 'inProgress' | 'pending';

type SampleActivity = {
  id: string;
  status: ActivityStatus;
};

const SAMPLE_ACTIVITIES: SampleActivity[] = [
  { id: 'sample-0', status: 'merged' },
  { id: 'sample-1', status: 'merged' },
  { id: 'sample-2', status: 'inProgress' },
  { id: 'sample-3', status: 'pending' },
];

const STATUS_ICONS: Record<ActivityStatus, LucideIcon> = {
  merged: GitMerge,
  inProgress: Loader2,
  pending: Circle,
};

export function LastAgentsActivityWidget() {
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
        {t('workspaceOverview.slots.lastAgentsActivity.title')}
      </h2>
      <ul
        className="flex flex-col gap-1.5 opacity-60"
        aria-label={t('workspaceOverview.widgets.lastAgentsActivity.previewLabel')}
      >
        {SAMPLE_ACTIVITIES.map((activity, index) => {
          const Icon = STATUS_ICONS[activity.status];
          return (
            <li key={activity.id} className="flex items-center gap-2 text-xs text-sc-text-muted">
              <Icon className="h-3.5 w-3.5 shrink-0 text-sc-text-muted" aria-hidden="true" />
              <span className="truncate font-mono">
                {t(`workspaceOverview.widgets.lastAgentsActivity.samples.${index}.bl`)}
              </span>
              <span className="truncate font-mono text-sc-text-muted/80">
                {t(`workspaceOverview.widgets.lastAgentsActivity.samples.${index}.commit`)}
              </span>
              <span className="ml-auto rounded-full bg-sc-bg-muted px-1.5 py-0.5 text-[10px] font-semibold uppercase tracking-wider text-sc-text-muted">
                {t(`workspaceOverview.widgets.lastAgentsActivity.statuses.${activity.status}`)}
              </span>
            </li>
          );
        })}
      </ul>
      <p className="mt-auto text-xs text-sc-text-muted">
        {t('workspaceOverview.widgets.lastAgentsActivity.comingAfter')}
      </p>
    </article>
  );
}
