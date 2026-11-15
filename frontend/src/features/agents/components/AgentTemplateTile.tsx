import { ArrowRight } from 'lucide-react';
import { useTranslation } from 'react-i18next';
import { Link } from 'react-router-dom';

import { getAgentAvatar } from '../lib/agentAvatar';
import type { AgentTemplate } from '../types';

interface AgentTemplateTileProps {
  template: AgentTemplate;
}

export function AgentTemplateTile({ template }: AgentTemplateTileProps) {
  const { t } = useTranslation();
  const avatar = getAgentAvatar(template.slug, template.name);

  return (
    <article
      role="group"
      aria-label={t('agents.templateTile.aria', { name: template.name })}
      className="flex flex-col rounded-xl border border-sc-border bg-sc-bg-card transition-colors hover:border-sc-text-muted"
    >
      <Link
        to={`/agents/new/template/${template.slug}`}
        aria-label={t('agents.templateTile.openAria', { name: template.name })}
        className="flex min-h-[12rem] flex-col gap-3 rounded-xl p-4 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-sc-text"
      >
        <div className="flex items-center gap-3">
          <span
            aria-hidden
            className={`inline-flex h-12 w-12 flex-none items-center justify-center rounded-lg text-base font-semibold ${avatar.bgClass} ${avatar.textClass}`}
          >
            {avatar.initials}
          </span>
          <div className="flex min-w-0 flex-col gap-1">
            <h2 className="truncate text-base font-semibold text-sc-text">{template.name}</h2>
            <p className="truncate text-xs text-sc-text-muted">{template.role}</p>
          </div>
        </div>
        {template.roleDescription ? (
          <p className="line-clamp-2 text-sm text-sc-text-muted">{template.roleDescription}</p>
        ) : null}
        <span className="mt-auto inline-flex items-center gap-1 text-xs font-medium text-sc-primary">
          {t('agents.templateTile.useThis')}
          <ArrowRight size={12} aria-hidden />
        </span>
      </Link>
    </article>
  );
}
