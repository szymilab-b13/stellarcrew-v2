import { AlertTriangle, Loader2, X } from 'lucide-react';
import { useEffect, useId, useState } from 'react';
import { useTranslation } from 'react-i18next';

import { Button } from '@/components/ui/Button';
import { ApiError } from '@/lib/apiClient';

import type { Agent } from '../types';

interface DeleteAgentConfirmDialogProps {
  agent: Agent;
  isSubmitting: boolean;
  error: unknown;
  onClose: () => void;
  onConfirm: () => void;
}

export function DeleteAgentConfirmDialog({
  agent,
  isSubmitting,
  error,
  onClose,
  onConfirm,
}: DeleteAgentConfirmDialogProps) {
  const { t } = useTranslation();
  const [acknowledged, setAcknowledged] = useState(false);
  const checkboxId = useId();
  const serverMessage = resolveServerMessage(error, t);

  useEffect(() => {
    setAcknowledged(false);
  }, [agent.id]);

  const confirmDisabled = isSubmitting || !acknowledged;

  return (
    <div
      role="dialog"
      aria-modal="true"
      aria-labelledby="agent-delete-title"
      className="fixed inset-0 z-50 flex items-center justify-center bg-black/50 p-4"
    >
      <div className="flex w-full max-w-2xl flex-col gap-5 rounded-xl border border-sc-border bg-sc-bg-card p-7 shadow-xl">
        <header className="flex items-start justify-between gap-3">
          <div className="flex items-center gap-3">
            <span
              aria-hidden
              className="inline-flex h-10 w-10 flex-none items-center justify-center rounded-full bg-sc-danger/10 text-sc-danger"
            >
              <AlertTriangle size={20} />
            </span>
            <h2 id="agent-delete-title" className="text-xl font-semibold text-sc-text">
              {t('agents.settings.delete.title')}
            </h2>
          </div>
          <button
            type="button"
            onClick={onClose}
            aria-label={t('common.close')}
            className="inline-flex h-8 w-8 flex-none items-center justify-center rounded-md text-sc-text-muted transition-colors hover:bg-sc-bg hover:text-sc-text"
          >
            <X size={18} aria-hidden />
          </button>
        </header>

        <div className="flex min-h-[6.5rem] flex-col gap-2">
          <p className="text-sm text-sc-text">
            {t('agents.settings.delete.body', { name: agent.name })}
          </p>
          <p className="text-sm font-semibold text-sc-text">
            {t('agents.settings.delete.confirmPrompt')}
          </p>
        </div>

        <label
          htmlFor={checkboxId}
          className="flex cursor-pointer items-center gap-3 rounded-md border border-sc-border bg-sc-bg p-3 text-sm text-sc-text transition-colors hover:border-sc-text-muted"
        >
          <input
            id={checkboxId}
            type="checkbox"
            checked={acknowledged}
            onChange={(event) => setAcknowledged(event.target.checked)}
            className="h-4 w-4 flex-none accent-sc-danger"
          />
          <span>{t('agents.settings.delete.acknowledge', { name: agent.name })}</span>
        </label>

        {serverMessage ? (
          <p
            role="alert"
            className="rounded-md border border-sc-danger/40 bg-sc-danger/10 px-3 py-2 text-sm text-sc-danger"
          >
            {serverMessage}
          </p>
        ) : null}

        <div className="flex flex-col gap-2">
          <Button
            variant="destructive"
            onClick={onConfirm}
            disabled={confirmDisabled}
            className="w-full justify-center px-4 py-3 text-base"
            leadingIcon={
              isSubmitting ? <Loader2 className="animate-spin" size={16} aria-hidden /> : undefined
            }
          >
            {isSubmitting
              ? t('agents.settings.delete.deleting')
              : t('agents.settings.delete.confirm')}
          </Button>
          <Button
            variant="secondary"
            onClick={onClose}
            className="w-full justify-center px-4 py-3 text-base"
          >
            {t('common.cancel')}
          </Button>
        </div>
      </div>
    </div>
  );
}

function resolveServerMessage(error: unknown, t: (key: string) => string): string | null {
  if (!(error instanceof ApiError)) return null;
  if (error.status === 403) return t('agents.settings.delete.errors.forbidden');
  if (error.status === 404) return t('agents.settings.delete.errors.notFound');
  return t('agents.settings.delete.errors.generic');
}
