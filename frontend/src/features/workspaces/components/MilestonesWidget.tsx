import { useTranslation } from 'react-i18next';
import type { LucideIcon } from 'lucide-react';
import { AlertCircle, CheckCircle2, Circle, Loader2 } from 'lucide-react';

type MilestoneStatus = 'done' | 'inProgress' | 'planned' | 'blocked';

type SampleMilestone = {
  id: string;
  status: MilestoneStatus;
};

const SAMPLE_MILESTONES: SampleMilestone[] = [
  { id: 'sample-0', status: 'done' },
  { id: 'sample-1', status: 'inProgress' },
  { id: 'sample-2', status: 'planned' },
  { id: 'sample-3', status: 'blocked' },
];

const STATUS_ICONS: Record<MilestoneStatus, LucideIcon> = {
  done: CheckCircle2,
  inProgress: Loader2,
  planned: Circle,
  blocked: AlertCircle,
};

export function MilestonesWidget() {
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
        {t('workspaceOverview.slots.milestones.title')}
      </h2>
      <ul
        className="flex flex-col gap-1.5 opacity-60"
        aria-label={t('workspaceOverview.widgets.milestones.previewLabel')}
      >
        {SAMPLE_MILESTONES.map((milestone, index) => {
          const Icon = STATUS_ICONS[milestone.status];
          return (
            <li key={milestone.id} className="flex items-center gap-2 text-xs text-sc-text-muted">
              <Icon className="h-3.5 w-3.5 shrink-0 text-sc-text-muted" aria-hidden="true" />
              <span className="truncate">
                {t(`workspaceOverview.widgets.milestones.samples.${index}`)}
              </span>
              <span className="ml-auto rounded-full bg-sc-bg-muted px-1.5 py-0.5 text-[10px] font-semibold uppercase tracking-wider text-sc-text-muted">
                {t(`workspaceOverview.widgets.milestones.statuses.${milestone.status}`)}
              </span>
            </li>
          );
        })}
      </ul>
      <p className="mt-auto text-xs text-sc-text-muted">
        {t('workspaceOverview.widgets.milestones.comingAfter')}
      </p>
    </article>
  );
}
