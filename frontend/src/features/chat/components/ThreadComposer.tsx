import { Loader2, Paperclip, Plus, Send } from 'lucide-react';
import { useState } from 'react';
import { useTranslation } from 'react-i18next';

import { useCreateThread } from '../api/useCreateThread';
import { useSendMessage } from '../api/useSendMessage';
import { useAgentDefaults } from '../hooks/useAgentDefaults';

interface ThreadComposerProps {
  sessionId: string;
  agentId: string;
  senderId: string;
  threadId: string | null;
  defaultSubject: string;
  onThreadCreated(threadId: string): void;
}

export function ThreadComposer({
  sessionId,
  agentId,
  senderId,
  threadId,
  defaultSubject,
  onThreadCreated,
}: ThreadComposerProps) {
  const { t } = useTranslation();
  const createThread = useCreateThread();
  const sendMessage = useSendMessage();
  const { defaults } = useAgentDefaults(agentId);

  const [content, setContent] = useState('');

  const isSending = createThread.isPending || sendMessage.isPending;
  const canSend = content.trim() !== '' && !isSending;
  const hasError = createThread.isError || sendMessage.isError;

  async function handleSubmit(event: React.FormEvent) {
    event.preventDefault();
    if (!canSend) {
      return;
    }

    try {
      let targetThreadId = threadId;
      if (targetThreadId === null) {
        const created = await createThread.mutateAsync({
          sessionId,
          subject: defaultSubject,
        });
        targetThreadId = created.id;
        onThreadCreated(created.id);
      }

      await sendMessage.mutateAsync({
        sessionId,
        threadId: targetThreadId,
        senderType: 'user',
        senderId,
        recipientType: 'agent',
        recipientId: agentId,
        subject: defaultSubject,
        priority: defaults.priority,
        content: content.trim(),
        contentType: 'text',
        metadata: {},
        replyToMessageId: null,
        engineId: defaults.engineId,
        engineType: defaults.engineType,
        classifiedBy: 'sender',
        classificationConfidence: null,
      });

      setContent('');
    } catch {
      // mutation.isError already drives the inline error alert.
    }
  }

  function handleKeyDown(event: React.KeyboardEvent<HTMLTextAreaElement>) {
    if (event.key === 'Enter' && !event.shiftKey) {
      event.preventDefault();
      void handleSubmit(event);
    }
  }

  return (
    <form
      onSubmit={handleSubmit}
      aria-label={t('chat.composer.ariaLabel')}
      className="flex flex-col gap-2 rounded-xl border border-sc-border bg-sc-bg-card/80 p-3 transition-colors"
    >
      <div className="flex items-center gap-2">
        <button
          type="button"
          disabled
          aria-label={t('chat.composer.toolbar')}
          title={t('chat.composer.toolbar')}
          className="inline-flex h-9 w-9 flex-none items-center justify-center rounded-md text-sc-text-muted transition-colors hover:bg-sc-bg-card hover:text-sc-text disabled:opacity-60"
        >
          <Plus size={18} aria-hidden />
        </button>
        <button
          type="button"
          disabled
          aria-label={t('chat.composer.attach')}
          title={t('chat.composer.attach')}
          className="inline-flex h-9 w-9 flex-none items-center justify-center rounded-md text-sc-text-muted transition-colors hover:bg-sc-bg-card hover:text-sc-text disabled:opacity-60"
        >
          <Paperclip size={18} aria-hidden />
        </button>
        <textarea
          value={content}
          onChange={(event) => setContent(event.target.value)}
          onKeyDown={handleKeyDown}
          placeholder={t('chat.composer.placeholder')}
          rows={1}
          className="flex-1 resize-none bg-transparent px-1 py-2 text-sm text-sc-text placeholder:text-sc-text-muted focus:outline-none"
        />
        <button
          type="submit"
          disabled={!canSend}
          aria-label={t('chat.composer.send')}
          className={`inline-flex h-9 w-9 flex-none items-center justify-center rounded-md transition-colors ${
            canSend
              ? 'bg-emerald-500 text-white hover:bg-emerald-600'
              : 'bg-sc-bg-card text-sc-text-muted cursor-not-allowed'
          }`}
        >
          {isSending ? (
            <Loader2 size={16} className="animate-spin" aria-hidden />
          ) : (
            <Send size={16} aria-hidden />
          )}
        </button>
      </div>

      {hasError ? (
        <p
          role="alert"
          className="rounded-md border border-red-300 bg-red-50 px-2 py-1 text-xs text-red-900"
        >
          {t('chat.composer.sendError')}
        </p>
      ) : null}
    </form>
  );
}
