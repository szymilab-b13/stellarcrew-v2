import { useMemo } from 'react';
import { useTranslation } from 'react-i18next';
import { Navigate, useParams } from 'react-router-dom';

import { usePageBreadcrumbs } from '@/components/app/useBreadcrumbs';

import { useWorkspacesList } from '../api/useWorkspaces';
import { ActiveAgentsWidget } from './ActiveAgentsWidget';
import { ActiveTasksWidget } from './ActiveTasksWidget';

type WidgetSlot = {
  key: string;
  bl: string;
};

const ROW_1_SLOTS: WidgetSlot[] = [
  { key: 'tasksProgress', bl: 'BL-WSUX-007' },
  { key: 'milestones', bl: 'BL-WSUX-008' },
  { key: 'projects', bl: 'BL-WSUX-009' },
];

const ROW_2_SLOTS: WidgetSlot[] = [
  { key: 'bossActions', bl: 'BL-WSUX-010' },
  { key: 'lastAgentsActivity', bl: 'BL-WSUX-011' },
  { key: 'workspaceInfo', bl: 'BL-WSUX-012' },
];

export function WorkspaceOverviewPage() {
  const { t } = useTranslation();
  const params = useParams<{ wsId: string }>();
  const workspaceId = params.wsId ?? null;

  const workspacesQuery = useWorkspacesList();
  const workspace = workspacesQuery.data?.find((w) => w.id === workspaceId) ?? null;

  const breadcrumbs = useMemo(
    () => [
      { label: t('workspaces.breadcrumb'), to: '/workspaces' },
      ...(workspace ? [{ label: workspace.name }] : []),
      { label: t('workspaceOverview.breadcrumb') },
    ],
    [t, workspace],
  );
  usePageBreadcrumbs(breadcrumbs);

  if (workspaceId === null) {
    return <Navigate to="/workspaces" replace />;
  }

  if (workspacesQuery.isSuccess && workspace === null) {
    return <Navigate to="/workspaces" replace />;
  }

  return (
    <div className="flex w-full flex-col gap-6">
      <header className="flex flex-col gap-1">
        <h1 className="text-2xl font-semibold tracking-tight text-sc-text">
          {t('workspaceOverview.title')}
        </h1>
        {workspace ? <p className="text-sm text-sc-text-muted">{workspace.name}</p> : null}
      </header>

      <section
        role="region"
        aria-label={t('workspaceOverview.rows.row1')}
        className="grid grid-cols-1 gap-4 md:grid-cols-2 lg:grid-cols-3 xl:grid-cols-5"
      >
        <ActiveTasksWidget />
        <ActiveAgentsWidget />
        {ROW_1_SLOTS.map((slot) => (
          <WidgetSlotCard key={slot.key} slot={slot} />
        ))}
      </section>

      <section
        role="region"
        aria-label={t('workspaceOverview.rows.row2')}
        className="grid grid-cols-1 gap-4 md:grid-cols-3"
      >
        {ROW_2_SLOTS.map((slot) => (
          <WidgetSlotCard key={slot.key} slot={slot} />
        ))}
      </section>
    </div>
  );
}

function WidgetSlotCard({ slot }: { slot: WidgetSlot }) {
  const { t } = useTranslation();
  const title = t(`workspaceOverview.slots.${slot.key}.title`);

  return (
    <article className="relative flex min-h-[9rem] flex-col gap-2 rounded-lg border border-dashed border-sc-border bg-sc-bg-card/40 p-4">
      <span
        aria-label={t('workspaceOverview.prototypeBadge')}
        className="absolute right-3 top-3 rounded-full bg-sc-bg-muted px-2 py-0.5 text-[10px] font-semibold uppercase tracking-wider text-sc-text-muted"
      >
        {t('workspaceOverview.prototypeBadge')}
      </span>
      <h2 className="pr-16 text-sm font-semibold text-sc-text">{title}</h2>
      <p className="mt-auto text-xs text-sc-text-muted">
        {t('workspaceOverview.comingIn', { bl: slot.bl })}
      </p>
    </article>
  );
}
