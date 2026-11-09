import { useTranslation } from 'react-i18next';
import { Circle, Loader2 } from 'lucide-react';

type SampleTask = {
  id: string;
  status: 'inProgress' | 'pending';
};

const SAMPLE_TASKS: SampleTask[] = [
  { id: 'sample-0', status: 'inProgress' },
  { id: 'sample-1', status: 'pending' },
  { id: 'sample-2', status: 'pending' },
];

export function ActiveTasksWidget() {
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
        {t('workspaceOverview.slots.activeTasks.title')}
      </h2>
      <ul
        className="flex flex-col gap-1.5 opacity-60"
        aria-label={t('workspaceOverview.widgets.activeTasks.previewLabel')}
      >
        {SAMPLE_TASKS.map((task, index) => (
          <li key={task.id} className="flex items-center gap-2 text-xs text-sc-text-muted">
            {task.status === 'inProgress' ? (
              <Loader2 className="h-3.5 w-3.5 shrink-0 text-sc-accent" aria-hidden="true" />
            ) : (
              <Circle className="h-3.5 w-3.5 shrink-0" aria-hidden="true" />
            )}
            <span className="truncate">
              {t(`workspaceOverview.widgets.activeTasks.samples.${index}`)}
            </span>
          </li>
        ))}
      </ul>
      <p className="mt-auto text-xs text-sc-text-muted">
        {t('workspaceOverview.widgets.activeTasks.comingAfter')}
      </p>
    </article>
  );
}
