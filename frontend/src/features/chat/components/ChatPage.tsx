import { Loader2 } from 'lucide-react';
import { useMemo, useState } from 'react';
import { useTranslation } from 'react-i18next';

import { usePageBreadcrumbs } from '@/components/app/useBreadcrumbs';
import { ApiError } from '@/lib/apiClient';

import { useAgentConversations } from '../api/useAgentConversations';
import { useCurrentSession } from '../api/useCurrentSession';
import { AddConversationModal } from './AddConversationModal';
import { AgentsSidebar } from './AgentsSidebar';
import { ConversationFeed } from './ConversationFeed';

export function ChatPage() {
  const { t } = useTranslation();
  const breadcrumbs = useMemo(() => [{ label: t('chat.breadcrumb') }], [t]);
  usePageBreadcrumbs(breadcrumbs);

  const sessionQuery = useCurrentSession();
  const conversationsQuery = useAgentConversations();
  const [selectedAgentId, setSelectedAgentId] = useState<string | null>(null);
  const [isAddModalOpen, setIsAddModalOpen] = useState(false);

  const selectedConversation =
    conversationsQuery.data?.conversations.find(
      (conversation) => conversation.agentId === selectedAgentId,
    ) ?? null;

  return (
    <div className="flex h-full w-full flex-col gap-6">
      <header className="flex flex-col gap-1">
        <h1 className="text-2xl font-semibold tracking-tight text-sc-text">{t('chat.title')}</h1>
        <p className="text-sm text-sc-text-muted">{t('chat.subtitle')}</p>
      </header>

      {sessionQuery.isPending ? (
        <p className="flex items-center gap-2 text-sm text-sc-text-muted">
          <Loader2 className="animate-spin" size={14} aria-hidden />
          {t('chat.sessionLoading')}
        </p>
      ) : null}

      {sessionQuery.isError ? (
        <p
          role="alert"
          className="rounded-md border border-red-300 bg-red-50 px-3 py-2 text-sm text-red-900"
        >
          {sessionQuery.error instanceof ApiError && sessionQuery.error.status === 409
            ? t('chat.sessionMissingOrg')
            : t('chat.sessionError')}
        </p>
      ) : null}

      {sessionQuery.isSuccess ? (
        <div className="grid min-h-0 flex-1 grid-cols-1 gap-4 lg:grid-cols-[18rem_1fr]">
          <AgentsSidebar
            selectedAgentId={selectedAgentId}
            onSelect={setSelectedAgentId}
            onAddConversation={() => setIsAddModalOpen(true)}
          />

          {selectedConversation && sessionQuery.data ? (
            <ConversationFeed
              conversation={selectedConversation}
              sessionId={sessionQuery.data.id}
              senderId={sessionQuery.data.ownerUserId}
            />
          ) : (
            <main className="flex min-h-0 flex-col items-center justify-center gap-2 overflow-y-auto rounded-lg border border-dashed border-sc-border bg-sc-bg-card/20 p-8 text-center">
              <p className="text-base font-medium text-sc-text">
                {t('chat.panel.selectAgentTitle')}
              </p>
              <p className="max-w-md text-sm text-sc-text-muted">
                {t('chat.panel.selectAgentBody')}
              </p>
            </main>
          )}
        </div>
      ) : null}

      {isAddModalOpen && sessionQuery.data ? (
        <AddConversationModal
          sessionId={sessionQuery.data.id}
          onClose={() => setIsAddModalOpen(false)}
          onCreated={(agentId) => {
            setSelectedAgentId(agentId);
            setIsAddModalOpen(false);
          }}
        />
      ) : null}
    </div>
  );
}
