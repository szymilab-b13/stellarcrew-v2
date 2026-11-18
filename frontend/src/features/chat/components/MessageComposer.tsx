import { zodResolver } from '@hookform/resolvers/zod';
import { Loader2, Send } from 'lucide-react';
import { useForm } from 'react-hook-form';
import { useTranslation } from 'react-i18next';
import { z } from 'zod';

import { Button } from '@/components/ui/Button';
import { Input } from '@/components/ui/Input';

import { useSendMessage } from '../api/useSendMessage';
import type { EngineType, Priority } from '../types';

const UUID_PATTERN = /^[0-9a-f]{8}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{12}$/i;

const PRIORITIES: readonly Priority[] = ['P0', 'P1', 'P2', 'P3'] as const;
const ENGINE_TYPES: readonly EngineType[] = ['cli_terminal', 'webhook', 'batch'] as const;

const composerSchema = z.object({
  subject: z.string().trim().min(1, 'subject.required').max(120, 'subject.tooLong'),
  content: z.string().trim().min(1, 'content.required'),
  priority: z.enum(['P0', 'P1', 'P2', 'P3']),
  engineId: z.string().trim().regex(UUID_PATTERN, 'engineId.invalid'),
  engineType: z.enum(['cli_terminal', 'webhook', 'batch']),
});

type ComposerFormValues = z.infer<typeof composerSchema>;

interface MessageComposerProps {
  sessionId: string;
  threadId: string;
  senderId: string;
}

export function MessageComposer({ sessionId, threadId, senderId }: MessageComposerProps) {
  const { t } = useTranslation();
  const mutation = useSendMessage();

  const form = useForm<ComposerFormValues>({
    resolver: zodResolver(composerSchema),
    defaultValues: {
      subject: '',
      content: '',
      priority: 'P2',
      engineId: '',
      engineType: 'cli_terminal',
    },
    mode: 'onSubmit',
  });

  const subjectError = form.formState.errors.subject?.message;
  const contentError = form.formState.errors.content?.message;
  const engineIdError = form.formState.errors.engineId?.message;

  function submit(values: ComposerFormValues) {
    mutation.mutate(
      {
        sessionId,
        threadId,
        senderType: 'user',
        senderId,
        recipientType: 'agent',
        recipientId: values.engineId,
        subject: values.subject,
        priority: values.priority,
        content: values.content,
        contentType: 'text',
        metadata: {},
        replyToMessageId: null,
        engineId: values.engineId,
        engineType: values.engineType,
        classifiedBy: 'sender',
        classificationConfidence: null,
      },
      {
        onSuccess: () => form.reset(),
      },
    );
  }

  return (
    <form
      noValidate
      onSubmit={form.handleSubmit(submit)}
      className="flex flex-col gap-3 rounded-lg border border-sc-border bg-sc-bg-card/40 p-3"
    >
      <div className="flex flex-col gap-1.5">
        <label htmlFor="chat-composer-subject" className="text-xs font-medium text-sc-text">
          {t('chat.composer.subject')}
        </label>
        <Input
          id="chat-composer-subject"
          autoComplete="off"
          invalid={Boolean(subjectError)}
          {...form.register('subject')}
        />
        {subjectError ? (
          <p role="alert" className="text-xs text-sc-danger">
            {t(`chat.composer.errors.${subjectError}`)}
          </p>
        ) : null}
      </div>

      <div className="flex flex-col gap-1.5">
        <label htmlFor="chat-composer-content" className="text-xs font-medium text-sc-text">
          {t('chat.composer.content')}
        </label>
        <textarea
          id="chat-composer-content"
          rows={4}
          aria-invalid={contentError ? true : undefined}
          className={`w-full rounded-md border bg-sc-bg px-3 py-2 text-sm text-sc-text outline-none transition-colors focus:ring-1 ${
            contentError
              ? 'border-sc-danger focus:border-sc-danger focus:ring-sc-danger/30'
              : 'border-sc-border focus:border-sc-primary focus:ring-sc-primary/30'
          }`}
          {...form.register('content')}
        />
        {contentError ? (
          <p role="alert" className="text-xs text-sc-danger">
            {t(`chat.composer.errors.${contentError}`)}
          </p>
        ) : null}
      </div>

      <div className="grid grid-cols-1 gap-3 sm:grid-cols-2">
        <div className="flex flex-col gap-1.5">
          <label htmlFor="chat-composer-priority" className="text-xs font-medium text-sc-text">
            {t('chat.composer.priority')}
          </label>
          <select
            id="chat-composer-priority"
            className="w-full rounded-md border border-sc-border bg-sc-bg px-3 py-2 text-sm text-sc-text outline-none transition-colors focus:border-sc-primary focus:ring-1 focus:ring-sc-primary/30"
            {...form.register('priority')}
          >
            {PRIORITIES.map((priority) => (
              <option key={priority} value={priority}>
                {t(`chat.priority.${priority}`)}
              </option>
            ))}
          </select>
        </div>

        <div className="flex flex-col gap-1.5">
          <label htmlFor="chat-composer-engine-type" className="text-xs font-medium text-sc-text">
            {t('chat.composer.engineType')}
          </label>
          <select
            id="chat-composer-engine-type"
            className="w-full rounded-md border border-sc-border bg-sc-bg px-3 py-2 text-sm text-sc-text outline-none transition-colors focus:border-sc-primary focus:ring-1 focus:ring-sc-primary/30"
            {...form.register('engineType')}
          >
            {ENGINE_TYPES.map((engineType) => (
              <option key={engineType} value={engineType}>
                {t(`chat.composer.engineTypes.${engineType}`)}
              </option>
            ))}
          </select>
        </div>
      </div>

      <div className="flex flex-col gap-1.5">
        <label htmlFor="chat-composer-engine-id" className="text-xs font-medium text-sc-text">
          {t('chat.composer.engineId')}
        </label>
        <Input
          id="chat-composer-engine-id"
          autoComplete="off"
          placeholder="00000000-0000-0000-0000-000000000000"
          invalid={Boolean(engineIdError)}
          {...form.register('engineId')}
        />
        {engineIdError ? (
          <p role="alert" className="text-xs text-sc-danger">
            {t(`chat.composer.errors.${engineIdError}`)}
          </p>
        ) : null}
      </div>

      {mutation.isError ? (
        <p
          role="alert"
          className="rounded-md border border-sc-danger/40 bg-sc-danger/10 px-3 py-2 text-sm text-sc-danger"
        >
          {t('chat.composer.errors.server.generic')}
        </p>
      ) : null}

      <div className="flex items-center justify-end">
        <Button
          type="submit"
          variant="primary"
          disabled={mutation.isPending}
          trailingIcon={
            mutation.isPending ? (
              <Loader2 className="animate-spin" size={14} aria-hidden />
            ) : (
              <Send size={14} aria-hidden />
            )
          }
        >
          {mutation.isPending ? t('chat.composer.sending') : t('chat.composer.send')}
        </Button>
      </div>
    </form>
  );
}
