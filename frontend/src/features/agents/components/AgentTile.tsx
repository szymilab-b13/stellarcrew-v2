import { Sparkles } from 'lucide-react';
import { useTranslation } from 'react-i18next';
import { Link } from 'react-router-dom';

import type { Agent } from '../types';

interface AgentTileProps {
  agent: Agent;
}

export function AgentTile({ agent }: AgentTileProps) {
  const { t, i18n } = useTranslation();

  const dateFormatter = new Intl.DateTimeFormat(i18n.resolvedLanguage ?? 'en', {
    year: 'numeric',
    month: 'short',
    day: 'numeric',
  });
  const createdAtLabel = dateFormatter.format(new Date(agent.createdAt));

  return (
    <div
      role="group"
      aria-label={t('agents.tile.aria', { name: agent.name })}
      className="rounded-xl border border-sc-border bg-sc-bg-card transition-colors hover:border-sc-text-muted"
    >
      <Link
        to={`/agents/${agent.id}/overview`}
        aria-label={t('agents.tile.openAria', { name: agent.name })}
        className="flex min-h-[9rem] flex-col gap-2 rounded-xl p-4 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-sc-text"
      >
        <div className="flex min-w-0 flex-col gap-1">
          <h2 className="truncate text-base font-semibold text-sc-text">{agent.name}</h2>
          <p className="truncate text-xs text-sc-text-muted">{agent.role}</p>
        </div>
        {agent.sourceTemplateId ? (
          <span className="inline-flex w-fit items-center gap-1 rounded-full border border-sc-border bg-sc-bg px-2 py-0.5 text-[11px] font-medium text-sc-text-muted">
            <Sparkles size={11} aria-hidden />
            {t('agents.tile.fromTemplate')}
          </span>
        ) : null}
        <p className="mt-auto text-xs text-sc-text-muted">
          {t('agents.tile.createdAt', { date: createdAtLabel })}
        </p>
      </Link>
    </div>
  );
}
