import { Circle, CircleCheck, CircleDashed, CircleDot } from 'lucide-react';
import { useTranslation } from 'react-i18next';

import type { MilestoneStatus, RoadmapMilestone, RoadmapTask } from './types';

interface MilestoneCardProps {
  milestone: RoadmapMilestone;
}

const COMMIT_BASE = 'https://github.com/Nessandro/stellarcrew-v2-private/commit/';

const STATUS_BADGE_CLASSES: Record<MilestoneStatus, string> = {
  done: 'bg-sc-primary/15 text-sc-primary',
  inProgress: 'bg-amber-400/15 text-amber-500',
  planned: 'bg-sc-text-muted/15 text-sc-text-muted',
  tbd: 'bg-slate-500/15 text-slate-400',
};

export function MilestoneCard({ milestone }: MilestoneCardProps) {
  const { t } = useTranslation();
  const title = t(`roadmap.milestones.${milestone.key}.title`);
  const statusLabel = t(`roadmap.statuses.${milestone.status}`);
  const doneCount = milestone.tasks.filter((task) => task.status === 'done').length;
  const total = milestone.tasks.length;

  return (
    <details className="group rounded-lg border border-sc-border bg-sc-bg-card">
      <summary className="flex cursor-pointer list-none items-center gap-3 px-4 py-3 text-sm font-medium text-sc-text transition-colors hover:bg-sc-bg-secondary">
        <span className="flex-1 truncate">{title}</span>
        <span
          className={`inline-flex items-center gap-1 rounded-full px-2 py-0.5 text-xs font-semibold uppercase tracking-wide ${STATUS_BADGE_CLASSES[milestone.status]}`}
        >
          {statusLabel}
        </span>
        {total > 0 ? (
          <span className="text-xs tabular-nums text-sc-text-muted">
            {doneCount}/{total}
          </span>
        ) : null}
      </summary>

      <div className="border-t border-sc-border px-4 py-3">
        {milestone.note ? (
          <p className="mb-3 rounded-md border border-sc-border bg-sc-bg-secondary px-3 py-2 text-xs text-sc-text-muted">
            {milestone.note}
          </p>
        ) : null}
        {milestone.tasks.length === 0 ? (
          <p className="text-xs text-sc-text-muted">{t('roadmap.emptyTasks')}</p>
        ) : (
          <ul className="flex flex-col gap-2">
            {milestone.tasks.map((task) => (
              <TaskRow key={task.id} task={task} />
            ))}
          </ul>
        )}
      </div>
    </details>
  );
}

function TaskRow({ task }: { task: RoadmapTask }) {
  const isDone = task.status === 'done';
  return (
    <li className="flex items-start gap-2 text-sm">
      <StatusIcon status={task.status} />
      <div className="min-w-0 flex-1">
        <span className="font-mono text-xs text-sc-text-muted">{task.id}</span>{' '}
        <span className={isDone ? 'line-through text-sc-text-muted' : 'text-sc-text'}>
          {task.description}
        </span>
        {task.commit ? (
          <>
            {' '}
            <a
              href={`${COMMIT_BASE}${task.commit}`}
              target="_blank"
              rel="noreferrer"
              className="font-mono text-xs text-sc-primary hover:underline"
            >
              {task.commit}
            </a>
          </>
        ) : null}
      </div>
    </li>
  );
}

function StatusIcon({ status }: { status: MilestoneStatus }) {
  const common = 'mt-0.5 h-4 w-4 flex-none';
  if (status === 'done') return <CircleCheck className={`${common} text-sc-primary`} aria-hidden />;
  if (status === 'inProgress')
    return <CircleDot className={`${common} text-amber-500`} aria-hidden />;
  if (status === 'tbd') return <CircleDashed className={`${common} text-slate-400`} aria-hidden />;
  return <Circle className={`${common} text-sc-text-muted`} aria-hidden />;
}
