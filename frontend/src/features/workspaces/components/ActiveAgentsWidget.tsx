import { useTranslation } from 'react-i18next';
import type { LucideIcon } from 'lucide-react';
import { Compass, Hammer, Headphones, Moon } from 'lucide-react';

type AgentMode = 'builder' | 'listener' | 'discovery' | 'idle';

type SampleAgent = {
  id: string;
  mode: AgentMode;
};

const SAMPLE_AGENTS: SampleAgent[] = [
  { id: 'sample-0', mode: 'builder' },
  { id: 'sample-1', mode: 'listener' },
  { id: 'sample-2', mode: 'discovery' },
  { id: 'sample-3', mode: 'idle' },
];

const MODE_ICONS: Record<AgentMode, LucideIcon> = {
  builder: Hammer,
  listener: Headphones,
  discovery: Compass,
  idle: Moon,
};

export function ActiveAgentsWidget() {
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
        {t('workspaceOverview.slots.activeAgents.title')}
      </h2>
      <ul
        className="flex flex-col gap-1.5 opacity-60"
        aria-label={t('workspaceOverview.widgets.activeAgents.previewLabel')}
      >
        {SAMPLE_AGENTS.map((agent, index) => {
          const Icon = MODE_ICONS[agent.mode];
          return (
            <li key={agent.id} className="flex items-center gap-2 text-xs text-sc-text-muted">
              <Icon className="h-3.5 w-3.5 shrink-0 text-sc-text-muted" aria-hidden="true" />
              <span className="truncate">
                {t(`workspaceOverview.widgets.activeAgents.samples.${index}`)}
              </span>
              <span className="ml-auto rounded-full bg-sc-bg-muted px-1.5 py-0.5 text-[10px] font-semibold uppercase tracking-wider text-sc-text-muted">
                {t(`workspaceOverview.widgets.activeAgents.modes.${agent.mode}`)}
              </span>
            </li>
          );
        })}
      </ul>
      <p className="mt-auto text-xs text-sc-text-muted">
        {t('workspaceOverview.widgets.activeAgents.comingAfter')}
      </p>
    </article>
  );
}
