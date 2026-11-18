import { Settings } from 'lucide-react';
import { useTranslation } from 'react-i18next';

import type { AgentConversation } from '../types';

interface ChatHeaderProps {
  conversation: AgentConversation;
  onOpenSettings(): void;
}

export function ChatHeader({ conversation, onOpenSettings }: ChatHeaderProps) {
  const { t } = useTranslation();
  const initials = buildInitials(conversation.agentName);

  return (
    <header className="flex items-center gap-3 rounded-lg border border-sc-border bg-sc-bg-card/40 p-3">
      <span
        aria-hidden
        className="flex h-11 w-11 flex-none items-center justify-center rounded-xl text-sm font-semibold"
        style={{
          backgroundColor: `${conversation.agentColor}33`,
          color: conversation.agentColor,
        }}
      >
        {initials}
      </span>
      <div className="flex min-w-0 flex-1 flex-col">
        <h2 className="truncate text-base font-semibold text-sc-text">{conversation.agentName}</h2>
        <span className="truncate text-xs text-sc-text-muted">{conversation.agentRole}</span>
      </div>
      <button
        type="button"
        onClick={onOpenSettings}
        aria-label={t('chat.header.openSettings')}
        title={t('chat.header.openSettings')}
        className="inline-flex h-9 w-9 flex-none items-center justify-center rounded-md border border-sc-border text-sc-text-muted transition-colors hover:border-sc-text-muted hover:text-sc-text"
      >
        <Settings size={14} aria-hidden />
      </button>
    </header>
  );
}

function buildInitials(name: string): string {
  const parts = name.trim().split(/\s+/);
  if (parts.length === 0 || parts[0] === '') {
    return '??';
  }
  if (parts.length === 1) {
    return parts[0].slice(0, 2).toUpperCase();
  }
  return (parts[0][0] + parts[parts.length - 1][0]).toUpperCase();
}
