import { useTranslation } from 'react-i18next';
import type { LucideIcon } from 'lucide-react';
import { AlertCircle, CheckCircle2, Loader2 } from 'lucide-react';

type SegmentStatus = 'inProgress' | 'done' | 'blocked';

type SampleSegment = {
  status: SegmentStatus;
  percent: number;
};

const SAMPLE_SEGMENTS: SampleSegment[] = [
  { status: 'inProgress', percent: 45 },
  { status: 'done', percent: 72 },
  { status: 'blocked', percent: 8 },
];

const STATUS_ICONS: Record<SegmentStatus, LucideIcon> = {
  inProgress: Loader2,
  done: CheckCircle2,
  blocked: AlertCircle,
};

export function TasksProgressWidget() {
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
        {t('workspaceOverview.slots.tasksProgress.title')}
      </h2>
      <ul
        className="flex flex-col gap-2 opacity-60"
        aria-label={t('workspaceOverview.widgets.tasksProgress.previewLabel')}
      >
        {SAMPLE_SEGMENTS.map((segment) => {
          const Icon = STATUS_ICONS[segment.status];
          return (
            <li key={segment.status} className="flex flex-col gap-1">
              <div className="flex items-center gap-2 text-xs text-sc-text-muted">
                <Icon className="h-3.5 w-3.5 shrink-0 text-sc-text-muted" aria-hidden="true" />
                <span className="truncate">
                  {t(`workspaceOverview.widgets.tasksProgress.segments.${segment.status}`)}
                </span>
                <span className="ml-auto text-[10px] font-semibold uppercase tracking-wider text-sc-text-muted">
                  {t('workspaceOverview.widgets.tasksProgress.percent', { value: segment.percent })}
                </span>
              </div>
              <div className="h-1.5 rounded-full bg-sc-bg-muted" aria-hidden="true">
                <div
                  className="h-full rounded-full bg-sc-text-muted"
                  style={{ width: `${segment.percent}%` }}
                />
              </div>
            </li>
          );
        })}
      </ul>
      <p className="mt-auto text-xs text-sc-text-muted">
        {t('workspaceOverview.widgets.tasksProgress.comingAfter')}
      </p>
    </article>
  );
}
