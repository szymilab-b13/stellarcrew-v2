import { Loader2 } from 'lucide-react';
import { useTranslation } from 'react-i18next';

import { useMessages } from '../api/useMessages';
import { MessageBubble } from './MessageBubble';

interface MessageListProps {
  threadId: string;
  limit?: number;
  offset?: number;
}

export function MessageList({ threadId, limit, offset }: MessageListProps) {
  const { t } = useTranslation();
  const messagesQuery = useMessages({ threadId, limit, offset });

  if (messagesQuery.isPending) {
    return (
      <p className="flex items-center gap-2 text-sm text-sc-text-muted">
        <Loader2 className="animate-spin" size={14} aria-hidden />
        {t('chat.messages.loading')}
      </p>
    );
  }

  if (messagesQuery.isError) {
    return (
      <p
        role="alert"
        className="rounded-md border border-red-300 bg-red-50 px-3 py-2 text-sm text-red-900"
      >
        {t('chat.messages.loadError')}
      </p>
    );
  }

  const messages = messagesQuery.data?.messages ?? [];

  if (messages.length === 0) {
    return (
      <div className="flex flex-col items-start gap-1 text-sm">
        <p className="font-medium text-sc-text">{t('chat.messages.emptyTitle')}</p>
        <p className="text-xs text-sc-text-muted">{t('chat.messages.emptyBody')}</p>
      </div>
    );
  }

  return (
    <ul aria-label={t('chat.messages.listAria')} className="flex flex-col gap-3">
      {messages.map((message) => (
        <li key={message.id}>
          <MessageBubble message={message} />
        </li>
      ))}
    </ul>
  );
}
