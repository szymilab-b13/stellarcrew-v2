import { X } from 'lucide-react';
import { useEffect, useState } from 'react';
import { useTranslation } from 'react-i18next';

import { useWorkers } from '../../workers/api/useWorkers';
import { useAgentDefaults } from '../hooks/useAgentDefaults';
import type { AgentConversation, EngineType } from '../types';

interface ChatSettingsModalProps {
  conversation: AgentConversation;
  onClose(): void;
}

const ENGINE_TYPES: EngineType[] = ['stellarcrew_worker', 'cli_terminal', 'webhook', 'batch'];

export function ChatSettingsModal({ conversation, onClose }: ChatSettingsModalProps) {
  const { t } = useTranslation();
  const { defaults, update } = useAgentDefaults(conversation.agentId);
  const workersQuery = useWorkers();

  const [engineType, setEngineType] = useState<EngineType>(defaults.engineType);
  const [engineId, setEngineId] = useState(defaults.engineId);

  useEffect(() => {
    function handleKey(event: KeyboardEvent) {
      if (event.key === 'Escape') {
        onClose();
      }
    }
    document.addEventListener('keydown', handleKey);
    return () => document.removeEventListener('keydown', handleKey);
  }, [onClose]);

  function handleSave(event: React.FormEvent) {
    event.preventDefault();
    update({ engineType, engineId, priority: defaults.priority });
    onClose();
  }

  const workers = workersQuery.data?.workers ?? [];
  const isWorkerMode = engineType === 'stellarcrew_worker';

  return (
    <div
      role="dialog"
      aria-modal="true"
      aria-label={t('chat.settings.title')}
      className="fixed inset-0 z-50 flex items-center justify-center bg-black/40 p-4"
      onClick={(event) => {
        if (event.target === event.currentTarget) {
          onClose();
        }
      }}
    >
      <form
        onSubmit={handleSave}
        className="flex w-full max-w-md flex-col gap-4 rounded-lg border border-sc-border bg-sc-bg p-4 shadow-lg"
      >
        <header className="flex items-center justify-between gap-2">
          <h2 className="text-lg font-semibold text-sc-text">
            {t('chat.settings.titleWithAgent', { agentName: conversation.agentName })}
          </h2>
          <button
            type="button"
            onClick={onClose}
            aria-label={t('chat.settings.close')}
            className="inline-flex h-7 w-7 items-center justify-center rounded-md text-sc-text-muted hover:bg-sc-bg-card hover:text-sc-text"
          >
            <X size={16} aria-hidden />
          </button>
        </header>

        <label className="flex flex-col gap-1 text-sm">
          <span className="text-sc-text-muted">{t('chat.settings.engineType')}</span>
          <select
            value={engineType}
            onChange={(event) => setEngineType(event.target.value as EngineType)}
            className="rounded-md border border-sc-border bg-sc-bg px-2 py-1 text-sc-text focus:border-indigo-400 focus:outline-none"
          >
            {ENGINE_TYPES.map((et) => (
              <option key={et} value={et}>
                {t(`chat.composer.engineTypes.${et}` as const)}
              </option>
            ))}
          </select>
        </label>

        {isWorkerMode ? (
          <label className="flex flex-col gap-1 text-sm">
            <span className="text-sc-text-muted">{t('chat.settings.worker')}</span>
            <select
              value={engineId}
              onChange={(event) => setEngineId(event.target.value)}
              disabled={workersQuery.isLoading || workers.length === 0}
              className="rounded-md border border-sc-border bg-sc-bg px-2 py-1 text-sc-text focus:border-indigo-400 focus:outline-none disabled:opacity-60"
            >
              <option value="">{t('chat.settings.workerPlaceholder')}</option>
              {workers.map((worker) => (
                <option key={worker.id} value={worker.id}>
                  {worker.name}
                  {worker.hostLabel ? ` (${worker.hostLabel})` : ''}
                  {worker.isOnline ? ' · online' : ''}
                </option>
              ))}
            </select>
            {workers.length === 0 && !workersQuery.isLoading ? (
              <span className="text-xs text-sc-text-muted">
                {t('chat.settings.noWorkers')}
              </span>
            ) : null}
          </label>
        ) : (
          <label className="flex flex-col gap-1 text-sm">
            <span className="text-sc-text-muted">{t('chat.settings.engineId')}</span>
            <input
              type="text"
              value={engineId}
              onChange={(event) => setEngineId(event.target.value)}
              placeholder="00000000-0000-0000-0000-000000000000"
              className="rounded-md border border-sc-border bg-sc-bg px-2 py-1 font-mono text-xs text-sc-text placeholder:text-sc-text-muted focus:border-indigo-400 focus:outline-none"
            />
          </label>
        )}

        <div className="flex justify-end gap-2">
          <button
            type="button"
            onClick={onClose}
            className="inline-flex items-center rounded-md border border-sc-border bg-sc-bg px-3 py-1.5 text-xs font-medium text-sc-text-muted transition-colors hover:border-sc-text-muted hover:text-sc-text"
          >
            {t('chat.settings.cancel')}
          </button>
          <button
            type="submit"
            className="inline-flex items-center rounded-md bg-indigo-500 px-3 py-1.5 text-xs font-medium text-white hover:bg-indigo-600"
          >
            {t('chat.settings.save')}
          </button>
        </div>
      </form>
    </div>
  );
}
