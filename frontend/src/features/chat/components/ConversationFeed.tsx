import { useQueries } from '@tanstack/react-query';
import { Loader2 } from 'lucide-react';
import { useState } from 'react';
import { useTranslation } from 'react-i18next';

import { fetchMessages } from '../api/chatApi';
import { useAgentThreads } from '../api/useAgentThreads';
import type { AgentConversation, ChatMessage } from '../types';
import { ChatHeader } from './ChatHeader';
import { ChatSettingsModal } from './ChatSettingsModal';
import { MessageBubble } from './MessageBubble';
import { ThreadComposer } from './ThreadComposer';

interface ConversationFeedProps {
  conversation: AgentConversation;
  sessionId: string;
  senderId: string;
}

export function ConversationFeed({ conversation, sessionId, senderId }: ConversationFeedProps) {
  const { t } = useTranslation();
  const threadsQuery = useAgentThreads(conversation.agentId);
  const [isSettingsOpen, setIsSettingsOpen] = useState(false);

  const threads = threadsQuery.data?.threads ?? [];

  const messageQueries = useQueries({
    queries: threads.map((thread) => ({
      queryKey: [
        'chat',
        'thread',
        thread.threadId,
        'messages',
        { limit: undefined, offset: undefined },
      ],
      queryFn: () => fetchMessages({ threadId: thread.threadId }),
      enabled: threads.length > 0,
      staleTime: 2_000,
      refetchInterval: 3_000,
      refetchIntervalInBackground: false,
    })),
  });

  const isLoadingMessages = messageQueries.some((query) => query.isPending);
  const hasMessageError = messageQueries.some((query) => query.isError);
  const mergedMessages: ChatMessage[] = messageQueries
    .flatMap((query) => query.data?.messages ?? [])
    .slice()
    .sort((left, right) => left.createdAt.localeCompare(right.createdAt));

  const latestThreadId = threads.length > 0 ? threads[0].threadId : null;
  const defaultSubject = t('chat.composer.defaultSubject', { agentName: conversation.agentName });

  return (
    <section className="flex min-h-0 flex-1 flex-col gap-3">
      <ChatHeader conversation={conversation} onOpenSettings={() => setIsSettingsOpen(true)} />

      <div className="flex min-h-0 flex-1 flex-col gap-3 overflow-y-auto rounded-xl border border-sc-border bg-sc-bg-card/40 p-4">
        {threadsQuery.isPending || isLoadingMessages ? (
          <p className="flex items-center gap-2 text-sm text-sc-text-muted">
            <Loader2 className="animate-spin" size={14} aria-hidden />
            {t('chat.feed.loadingMessages')}
          </p>
        ) : null}

        {threadsQuery.isError || hasMessageError ? (
          <p
            role="alert"
            className="rounded-md border border-red-300 bg-red-50 px-3 py-2 text-sm text-red-900"
          >
            {t('chat.feed.loadError')}
          </p>
        ) : null}

        {threadsQuery.isSuccess &&
        !isLoadingMessages &&
        !hasMessageError &&
        mergedMessages.length === 0 ? (
          <div className="flex flex-col items-start gap-1 text-sm">
            <p className="font-medium text-sc-text">{t('chat.feed.emptyTitle')}</p>
            <p className="text-xs text-sc-text-muted">{t('chat.feed.emptyBody')}</p>
          </div>
        ) : null}

        {mergedMessages.map((message) => (
          <MessageBubble
            key={message.id}
            message={message}
            agentName={conversation.agentName}
            agentColor={conversation.agentColor}
          />
        ))}
      </div>

      <ThreadComposer
        sessionId={sessionId}
        agentId={conversation.agentId}
        senderId={senderId}
        threadId={latestThreadId}
        defaultSubject={defaultSubject}
        onThreadCreated={() => {
          // Latest thread id is derived from the threads query, which the mutation already invalidates.
        }}
      />

      {isSettingsOpen ? (
        <ChatSettingsModal conversation={conversation} onClose={() => setIsSettingsOpen(false)} />
      ) : null}
    </section>
  );
}
