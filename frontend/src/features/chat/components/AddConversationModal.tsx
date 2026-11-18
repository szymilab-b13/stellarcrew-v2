import { Loader2, Search, X } from 'lucide-react';
import { useEffect, useMemo, useState } from 'react';
import { useTranslation } from 'react-i18next';

import { useAgentsList } from '@/features/agents/api/useAgents';
import type { Agent } from '@/features/agents/types';

import { useCreateThread } from '../api/useCreateThread';

interface AddConversationModalProps {
  sessionId: string;
  onClose(): void;
  onCreated(agentId: string): void;
}

export function AddConversationModal({ sessionId, onClose, onCreated }: AddConversationModalProps) {
  const { t } = useTranslation();
  const agentsQuery = useAgentsList();
  const createThread = useCreateThread();
  const [search, setSearch] = useState('');

  const filteredAgents = useMemo(() => {
    const agents = agentsQuery.data ?? [];
    const term = search.trim().toLowerCase();
    if (term === '') {
      return agents;
    }
    return agents.filter(
      (agent) =>
        agent.name.toLowerCase().includes(term) ||
        agent.role.toLowerCase().includes(term) ||
        agent.slug.toLowerCase().includes(term),
    );
  }, [agentsQuery.data, search]);

  useEffect(() => {
    function handleKey(event: KeyboardEvent) {
      if (event.key === 'Escape') {
        onClose();
      }
    }
    document.addEventListener('keydown', handleKey);
    return () => document.removeEventListener('keydown', handleKey);
  }, [onClose]);

  function handleSelect(agent: Agent) {
    createThread.mutate(
      {
        sessionId,
        subject: t('chat.addConversation.defaultSubject', { agentName: agent.name }),
      },
      {
        onSuccess: () => {
          onCreated(agent.id);
        },
      },
    );
  }

  return (
    <div
      role="dialog"
      aria-modal="true"
      aria-label={t('chat.addConversation.title')}
      className="fixed inset-0 z-50 flex items-center justify-center bg-black/40 p-4"
      onClick={(event) => {
        if (event.target === event.currentTarget) {
          onClose();
        }
      }}
    >
      <div className="flex max-h-[80vh] w-full max-w-xl flex-col gap-4 rounded-lg border border-sc-border bg-sc-bg p-4 shadow-lg">
        <header className="flex items-center justify-between gap-2">
          <h2 className="text-lg font-semibold text-sc-text">{t('chat.addConversation.title')}</h2>
          <button
            type="button"
            onClick={onClose}
            aria-label={t('chat.addConversation.close')}
            className="inline-flex h-7 w-7 items-center justify-center rounded-md text-sc-text-muted hover:bg-sc-bg-card hover:text-sc-text"
          >
            <X size={16} aria-hidden />
          </button>
        </header>

        <div className="flex items-center gap-2 rounded-md border border-sc-border bg-sc-bg-card px-3 py-2">
          <Search size={14} className="text-sc-text-muted" aria-hidden />
          <input
            type="search"
            placeholder={t('chat.addConversation.searchPlaceholder')}
            value={search}
            onChange={(event) => setSearch(event.target.value)}
            className="flex-1 bg-transparent text-sm text-sc-text placeholder:text-sc-text-muted focus:outline-none"
            autoFocus
          />
        </div>

        {agentsQuery.isPending ? (
          <p className="flex items-center gap-2 text-sm text-sc-text-muted">
            <Loader2 className="animate-spin" size={14} aria-hidden />
            {t('chat.addConversation.loading')}
          </p>
        ) : null}

        {agentsQuery.isError ? (
          <p
            role="alert"
            className="rounded-md border border-red-300 bg-red-50 px-3 py-2 text-xs text-red-900"
          >
            {t('chat.addConversation.loadError')}
          </p>
        ) : null}

        {createThread.isError ? (
          <p
            role="alert"
            className="rounded-md border border-red-300 bg-red-50 px-3 py-2 text-xs text-red-900"
          >
            {t('chat.addConversation.createError')}
          </p>
        ) : null}

        {agentsQuery.isSuccess && filteredAgents.length === 0 ? (
          <p className="rounded-md border border-dashed border-sc-border px-3 py-6 text-center text-sm text-sc-text-muted">
            {search.trim() === ''
              ? t('chat.addConversation.emptyAgents')
              : t('chat.addConversation.emptyFiltered')}
          </p>
        ) : null}

        {filteredAgents.length > 0 ? (
          <ul className="flex flex-col gap-1 overflow-y-auto pr-1">
            {filteredAgents.map((agent) => (
              <li key={agent.id}>
                <button
                  type="button"
                  onClick={() => handleSelect(agent)}
                  disabled={createThread.isPending}
                  className="flex w-full items-center gap-3 rounded-md border border-transparent px-3 py-2 text-left text-sc-text transition-colors hover:border-sc-border hover:bg-sc-bg-card disabled:cursor-not-allowed disabled:opacity-60"
                >
                  <span
                    aria-hidden
                    className="flex h-9 w-9 flex-none items-center justify-center rounded-full text-xs font-semibold"
                    style={{ backgroundColor: `${agent.color}33`, color: agent.color }}
                  >
                    {buildInitials(agent.name)}
                  </span>
                  <span className="flex min-w-0 flex-1 flex-col">
                    <span className="truncate text-sm font-medium text-sc-text">{agent.name}</span>
                    <span className="truncate text-xs text-sc-text-muted">{agent.role}</span>
                  </span>
                  {createThread.isPending &&
                  createThread.variables?.subject.includes(agent.name) ? (
                    <Loader2 className="animate-spin text-sc-text-muted" size={14} aria-hidden />
                  ) : null}
                </button>
              </li>
            ))}
          </ul>
        ) : null}
      </div>
    </div>
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
