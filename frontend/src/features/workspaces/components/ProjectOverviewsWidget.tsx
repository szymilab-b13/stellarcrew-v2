import { ArrowUpRight, Plus } from 'lucide-react';
import { useTranslation } from 'react-i18next';
import { Link } from 'react-router-dom';

type ProjectStatus = 'active' | 'frozen' | 'archived';

type SampleProject = {
  id: string;
  status: ProjectStatus;
  percent: number;
  agents: number;
  tasksDone: number;
  tasksTotal: number;
  sparkline: readonly number[];
};

const SAMPLE_PROJECTS: readonly SampleProject[] = [
  {
    id: 'stellarcrew',
    status: 'active',
    percent: 48,
    agents: 5,
    tasksDone: 13,
    tasksTotal: 41,
    sparkline: [10, 12, 11, 14, 16, 15, 18],
  },
  {
    id: 'jobSearch',
    status: 'active',
    percent: 72,
    agents: 2,
    tasksDone: 5,
    tasksTotal: 8,
    sparkline: [8, 10, 12, 14, 15, 17, 18],
  },
  {
    id: 'gymPlan',
    status: 'active',
    percent: 67,
    agents: 0,
    tasksDone: 8,
    tasksTotal: 12,
    sparkline: [6, 8, 9, 11, 13, 14, 16],
  },
  {
    id: 'questilo',
    status: 'frozen',
    percent: 78,
    agents: 3,
    tasksDone: 14,
    tasksTotal: 18,
    sparkline: [18, 17, 16, 15, 14, 13, 12],
  },
  {
    id: 'englishLearning',
    status: 'active',
    percent: 33,
    agents: 1,
    tasksDone: 2,
    tasksTotal: 6,
    sparkline: [4, 6, 5, 7, 8, 7, 9],
  },
  {
    id: 'readingChallenge',
    status: 'active',
    percent: 17,
    agents: 0,
    tasksDone: 4,
    tasksTotal: 24,
    sparkline: [5, 6, 4, 7, 5, 8, 6],
  },
  {
    id: 'businessPlan',
    status: 'frozen',
    percent: 20,
    agents: 0,
    tasksDone: 2,
    tasksTotal: 10,
    sparkline: [14, 12, 11, 10, 9, 8, 7],
  },
];

const STATUS_CLASSES: Record<ProjectStatus, string> = {
  active: 'bg-sc-primary/15 text-sc-primary',
  frozen: 'bg-amber-500/15 text-amber-500',
  archived: 'bg-sc-bg-muted text-sc-text-muted',
};

function Sparkline({ points, ariaLabel }: { points: readonly number[]; ariaLabel: string }) {
  const width = 72;
  const height = 24;
  const min = Math.min(...points);
  const max = Math.max(...points);
  const range = max - min || 1;
  const step = width / (points.length - 1);
  const coords = points
    .map((value, index) => {
      const x = index * step;
      const y = height - ((value - min) / range) * height;
      return `${x.toFixed(2)},${y.toFixed(2)}`;
    })
    .join(' ');
  const last = points[points.length - 1];
  const lastX = (points.length - 1) * step;
  const lastY = height - ((last - min) / range) * height;

  return (
    <svg
      width={width}
      height={height}
      viewBox={`0 0 ${width} ${height}`}
      role="img"
      aria-label={ariaLabel}
      className="text-sc-primary"
    >
      <polyline points={coords} fill="none" stroke="currentColor" strokeWidth="1.5" />
      <circle cx={lastX} cy={lastY} r="2" fill="currentColor" />
    </svg>
  );
}

interface ProjectOverviewsWidgetProps {
  workspaceId: string;
}

export function ProjectOverviewsWidget({ workspaceId }: ProjectOverviewsWidgetProps) {
  const { t } = useTranslation();
  const projectsPath = `/workspace/${workspaceId}/projects`;
  const openAllAria = t('workspaceOverview.widgets.projectOverviews.openAllAria');

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

      <ul
        className="grid grid-cols-1 gap-3 md:grid-cols-2 xl:grid-cols-4"
        aria-label={t('workspaceOverview.widgets.projectOverviews.listAria')}
      >
        {SAMPLE_PROJECTS.map((project) => {
          const barColor = project.status === 'archived' ? 'bg-sc-text-muted' : 'bg-sc-primary';
          return (
            <li
              key={project.id}
              className="flex flex-col gap-2 rounded-md border border-sc-border/60 bg-sc-bg-card/60 p-3"
            >
              <div className="flex items-start justify-between gap-2">
                <div className="flex min-w-0 flex-col">
                  <span className="truncate text-sm font-semibold text-sc-text">
                    {t(`workspaceOverview.widgets.projectOverviews.samples.${project.id}.name`)}
                  </span>
                  <span className="truncate text-xs text-sc-text-muted">
                    {t(`workspaceOverview.widgets.projectOverviews.samples.${project.id}.subtitle`)}
                  </span>
                </div>
                <span
                  className={`shrink-0 rounded-full px-2 py-0.5 text-[10px] font-semibold uppercase tracking-wider ${STATUS_CLASSES[project.status]}`}
                >
                  {t(`workspaceOverview.widgets.projectOverviews.statuses.${project.status}`)}
                </span>
              </div>
              <div className="flex items-end justify-between gap-2">
                <span className="text-xl font-semibold text-sc-text">
                  {t('workspaceOverview.widgets.projectOverviews.percent', {
                    value: project.percent,
                  })}
                </span>
                <Sparkline
                  points={project.sparkline}
                  ariaLabel={t('workspaceOverview.widgets.projectOverviews.sparklineAria', {
                    name: t(
                      `workspaceOverview.widgets.projectOverviews.samples.${project.id}.name`,
                    ),
                  })}
                />
              </div>
              <div
                className="h-1.5 w-full overflow-hidden rounded-full bg-sc-bg-muted"
                role="progressbar"
                aria-valuemin={0}
                aria-valuemax={100}
                aria-valuenow={project.percent}
                aria-label={t('workspaceOverview.widgets.projectOverviews.progressAria', {
                  name: t(`workspaceOverview.widgets.projectOverviews.samples.${project.id}.name`),
                })}
              >
                <div className={`h-full ${barColor}`} style={{ width: `${project.percent}%` }} />
              </div>
              <div className="flex items-center justify-between gap-2 text-[10px] text-sc-text-muted">
                <span>
                  {t('workspaceOverview.widgets.projectOverviews.footer.agents', {
                    count: project.agents,
                  })}
                </span>
                <span>
                  {t('workspaceOverview.widgets.projectOverviews.footer.tasks', {
                    done: project.tasksDone,
                    total: project.tasksTotal,
                  })}
                </span>
                <span>
                  {t(`workspaceOverview.widgets.projectOverviews.footer.ago.${project.id}`)}
                </span>
              </div>
            </li>
          );
        })}
        <li>
          <Link
            to={projectsPath}
            aria-label={t('workspaceOverview.widgets.projectOverviews.showMoreAria')}
            className="flex h-full min-h-[8rem] flex-col items-center justify-center gap-2 rounded-md border border-dashed border-sc-border bg-sc-bg-card/40 p-3 text-sc-text-muted transition-colors hover:border-sc-text hover:text-sc-text focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-sc-text"
          >
            <Plus className="h-5 w-5" aria-hidden="true" />
            <span className="text-xs font-semibold uppercase tracking-wider">
              {t('workspaceOverview.widgets.projectOverviews.showMore')}
            </span>
          </Link>
        </li>
      </ul>
    </article>
  );
}
