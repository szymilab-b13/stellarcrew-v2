import { useTranslation } from 'react-i18next';
import type { LucideIcon } from 'lucide-react';
import { FolderPlus, Megaphone, Rocket, UserPlus } from 'lucide-react';

type ActionType = 'create' | 'invite' | 'launch' | 'broadcast';

type SampleAction = {
  id: string;
  type: ActionType;
};

const SAMPLE_ACTIONS: SampleAction[] = [
  { id: 'sample-0', type: 'create' },
  { id: 'sample-1', type: 'invite' },
  { id: 'sample-2', type: 'launch' },
  { id: 'sample-3', type: 'broadcast' },
];

const TYPE_ICONS: Record<ActionType, LucideIcon> = {
  create: FolderPlus,
  invite: UserPlus,
  launch: Rocket,
  broadcast: Megaphone,
};

export function BossActionsWidget() {
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
        {t('workspaceOverview.slots.bossActions.title')}
      </h2>
      <ul
        className="flex flex-col gap-1.5 opacity-60"
        aria-label={t('workspaceOverview.widgets.bossActions.previewLabel')}
      >
        {SAMPLE_ACTIONS.map((action, index) => {
          const Icon = TYPE_ICONS[action.type];
          return (
            <li key={action.id} className="flex items-center gap-2 text-xs text-sc-text-muted">
              <Icon className="h-3.5 w-3.5 shrink-0 text-sc-text-muted" aria-hidden="true" />
              <span className="truncate">
                {t(`workspaceOverview.widgets.bossActions.samples.${index}`)}
              </span>
              <span className="ml-auto rounded-full bg-sc-bg-muted px-1.5 py-0.5 text-[10px] font-semibold uppercase tracking-wider text-sc-text-muted">
                {t(`workspaceOverview.widgets.bossActions.types.${action.type}`)}
              </span>
            </li>
          );
        })}
      </ul>
      <p className="mt-auto text-xs text-sc-text-muted">
        {t('workspaceOverview.widgets.bossActions.comingAfter')}
      </p>
    </article>
  );
}
