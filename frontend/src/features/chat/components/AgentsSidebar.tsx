import { Loader2, Plus, Search } from 'lucide-react';
import { useMemo, useState } from 'react';
import { useTranslation } from 'react-i18next';
import { Link } from 'react-router-dom';

import { useAgentConversations } from '../api/useAgentConversations';
import type { AgentConversation } from '../types';

interface AgentsSidebarProps {
  selectedAgentId: string | null;
  onSelect(agentId: string): void;
  onAddConversation(): void;
}

export function AgentsSidebar({
  selectedAgentId,
  onSelect,
  onAddConversation,
}: AgentsSidebarProps) {
  const { t } = useTranslation();
  const query = useAgentConversations();
  const [search, setSearch] = useState('');

  const hasAnyAgents = (query.data?.conversations.length ?? 0) > 0;

  const directMessages = useMemo(() => {
    const term = search.trim().toLowerCase();
    return (query.data?.conversations ?? [])
      .filter((conversation) => conversation.threadCount > 0)
      .filter((conversation) =>
        term === ''
          ? true
          : conversation.agentName.toLowerCase().includes(term) ||
            conversation.agentRole.toLowerCase().includes(term),
      );
  }, [query.data, search]);

  return (
    <aside
      aria-label={t('chat.sidebar.ariaLabel')}
      className="flex h-full min-h-0 flex-col gap-2 rounded-lg border border-sc-border bg-sc-bg-card/40 p-3"
    >
      {hasAnyAgents ? (
        <div className="flex items-center gap-2 rounded-md border border-sc-border bg-sc-bg px-2 py-1.5">
          <Search size={12} className="text-sc-text-muted" aria-hidden />
          <input
            type="search"
            value={search}
            onChange={(event) => setSearch(event.target.value)}
            placeholder={t('chat.sidebar.searchPlaceholder')}
            aria-label={t('chat.sidebar.searchAria')}
            className="flex-1 bg-transparent text-xs text-sc-text placeholder:text-sc-text-muted focus:outline-none"
          />
        </div>
      ) : null}

      <div className="flex items-center justify-between gap-2 px-1">
        <h2 className="text-xs font-semibold uppercase tracking-wide text-sc-text-muted">
          {t('chat.sidebar.dmHeading')}
        </h2>
        {hasAnyAgents ? (
          <button
            type="button"
            onClick={onAddConversation}
            aria-label={t('chat.sidebar.addConversation')}
            title={t('chat.sidebar.addConversation')}
            className="inline-flex h-6 w-6 items-center justify-center rounded-md border border-sc-border bg-sc-bg text-sc-text transition-colors hover:border-sc-text-muted hover:text-sc-text"
          >
            <Plus size={14} aria-hidden />
          </button>
        ) : null}
      </div>

      {query.isPending ? (
        <p className="flex items-center gap-2 text-sm text-sc-text-muted">
          <Loader2 className="animate-spin" size={14} aria-hidden />
          {t('chat.sidebar.loading')}
        </p>
      ) : null}

      {query.isError ? (
        <p
          role="alert"
          className="rounded-md border border-red-300 bg-red-50 px-3 py-2 text-xs text-red-900"
        >
          {t('chat.sidebar.loadError')}
        </p>
      ) : null}

      {query.isSuccess && !hasAnyAgents ? (
        <div className="flex flex-col items-start gap-2 p-2 text-sm">
          <p className="font-medium text-sc-text">{t('chat.sidebar.emptyTitle')}</p>
          <p className="text-xs text-sc-text-muted">{t('chat.sidebar.emptyBody')}</p>
          <Link
            to="/agents/new"
            className="mt-1 inline-flex items-center rounded-md border border-sc-border bg-sc-bg px-2.5 py-1 text-xs font-medium text-sc-text hover:border-sc-text-muted"
          >
            {t('chat.sidebar.emptyCta')}
          </Link>
        </div>
      ) : null}

      {query.isSuccess && hasAnyAgents && directMessages.length === 0 ? (
        <div className="flex flex-col items-start gap-2 p-2 text-sm">
          <p className="font-medium text-sc-text">
            {search.trim() === ''
              ? t('chat.sidebar.emptyDmTitle')
              : t('chat.sidebar.emptySearchTitle')}
          </p>
          <p className="text-xs text-sc-text-muted">
            {search.trim() === ''
              ? t('chat.sidebar.emptyDmBody')
              : t('chat.sidebar.emptySearchBody')}
          </p>
        </div>
      ) : null}

      {query.isSuccess && directMessages.length > 0 ? (
        <ul className="flex min-h-0 flex-1 flex-col gap-1 overflow-y-auto">
          {directMessages.map((conversation) => (
            <AgentRow
              key={conversation.agentId}
              conversation={conversation}
              isSelected={conversation.agentId === selectedAgentId}
              onSelect={onSelect}
            />
          ))}
        </ul>
      ) : null}
    </aside>
  );
}

interface AgentRowProps {
  conversation: AgentConversation;
  isSelected: boolean;
  onSelect(agentId: string): void;
}

function AgentRow({ conversation, isSelected, onSelect }: AgentRowProps) {
  const { i18n } = useTranslation();

  const dateFormatter = new Intl.DateTimeFormat(i18n.resolvedLanguage ?? 'en', {
    month: 'short',
    day: 'numeric',
    hour: '2-digit',
    minute: '2-digit',
  });
  const lastMessageLabel = conversation.lastMessageAt
    ? dateFormatter.format(new Date(conversation.lastMessageAt))
    : null;

  const initials = buildInitials(conversation.agentName);
  const preview = conversation.lastMessagePreview
    ? truncate(conversation.lastMessagePreview, 60)
    : null;

  return (
    <li>
      <button
        type="button"
        onClick={() => onSelect(conversation.agentId)}
        aria-pressed={isSelected}
        className={`flex w-full items-start gap-3 rounded-md border px-3 py-2 text-left transition-colors ${
          isSelected
            ? 'border-indigo-400/60 bg-gradient-to-r from-indigo-500/20 to-fuchsia-500/10 text-sc-text shadow-sm'
            : 'border-transparent text-sc-text-muted hover:bg-sc-bg-card hover:text-sc-text'
        }`}
      >
        <span
          aria-hidden
          className="mt-0.5 flex h-9 w-9 flex-none items-center justify-center rounded-full text-xs font-semibold"
          style={{
            backgroundColor: `${conversation.agentColor}33`,
            color: conversation.agentColor,
          }}
        >
          {initials}
        </span>
        <span className="flex min-w-0 flex-1 flex-col gap-0.5">
          <span className={`truncate text-sm ${isSelected ? 'font-semibold' : 'font-medium'}`}>
            {conversation.agentName}
          </span>
          <span className="truncate text-[11px] text-sc-text-muted">{conversation.agentRole}</span>
          {preview ? <span className="truncate text-xs text-sc-text-muted">{preview}</span> : null}
          {lastMessageLabel ? (
            <span className="text-[10px] uppercase tracking-wide text-sc-text-muted">
              {lastMessageLabel}
            </span>
          ) : null}
        </span>
      </button>
    </li>
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

function truncate(text: string, max: number): string {
  return text.length > max ? `${text.slice(0, max - 1)}…` : text;
}
