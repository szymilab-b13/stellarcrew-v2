import { useMemo } from 'react';
import { useTranslation } from 'react-i18next';

import type { ChatMessage } from '../types';

interface MessageBubbleProps {
  message: ChatMessage;
  agentName?: string;
  agentColor?: string;
}

export function MessageBubble({ message, agentName, agentColor }: MessageBubbleProps) {
  const { t, i18n } = useTranslation();

  const timestampFormatter = useMemo(
    () =>
      new Intl.DateTimeFormat(i18n.resolvedLanguage ?? 'en', {
        hour: '2-digit',
        minute: '2-digit',
      }),
    [i18n.resolvedLanguage],
  );
  const timestampLabel = timestampFormatter.format(new Date(message.createdAt));

  const isUser = message.senderType === 'user';
  const senderLabel = t(`chat.sender.${message.senderType}`);

  const bubbleStyle =
    !isUser && agentColor
      ? {
          backgroundColor: `${agentColor}33`,
          borderColor: `${agentColor}66`,
        }
      : undefined;

  return (
    <div className={`flex ${isUser ? 'justify-end' : 'justify-start'}`}>
      <div
        role="article"
        aria-label={t('chat.bubble.aria', { sender: senderLabel, subject: message.subject })}
        data-sender-type={message.senderType}
        style={bubbleStyle}
        className={`max-w-[75%] rounded-xl border px-4 py-2 text-sm text-sc-text ${
          isUser ? 'border-emerald-500/40 bg-emerald-500/15' : 'border-sc-border bg-sc-bg-card'
        }`}
      >
        {!isUser && agentName ? (
          <p
            className="mb-1 text-[10px] font-semibold"
            style={agentColor ? { color: agentColor } : undefined}
          >
            {agentName}
          </p>
        ) : null}
        <p className="whitespace-pre-wrap leading-relaxed">{message.content}</p>
        <p className="mt-1 text-right text-[10px] text-sc-text-muted">{timestampLabel}</p>
      </div>
    </div>
  );
}
