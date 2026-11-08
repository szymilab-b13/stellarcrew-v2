import { AlertTriangle, Loader2, X } from 'lucide-react';
import { useTranslation } from 'react-i18next';

import { Button } from '@/components/ui/Button';
import { ApiError } from '@/lib/apiClient';

import { useDescribeUndo } from '../hooks/useDescribeUndo';
import type { AuditEntry } from '../types';

interface UndoConfirmDialogProps {
  entry: AuditEntry;
  isSubmitting: boolean;
  error: unknown;
  onClose: () => void;
  onConfirm: () => void;
}

export function UndoConfirmDialog({
  entry,
  isSubmitting,
  error,
  onClose,
  onConfirm,
}: UndoConfirmDialogProps) {
  const { t } = useTranslation();
  const describe = useDescribeUndo(entry.id);
  const actionLabel = t(`audit.actions.${entry.action}`, { defaultValue: entry.action });
  const serverMessage = resolveServerMessage(error, t);

  return (
    <div
      role="dialog"
      aria-modal="true"
      aria-labelledby="undo-dialog-title"
      className="fixed inset-0 z-50 flex items-center justify-center bg-black/50 p-4"
    >
      <div className="flex w-full max-w-2xl flex-col gap-5 rounded-xl border border-sc-border bg-sc-bg-card p-7 shadow-xl">
        <header className="flex items-start justify-between gap-3">
          <div className="flex items-center gap-3 text-sc-text">
            <AlertTriangle size={20} aria-hidden />
            <h2 id="undo-dialog-title" className="text-xl font-semibold">
              {t('audit.undoDialog.title')}
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

        <div className="flex min-h-[6.5rem] flex-col gap-2 text-sm text-sc-text">
          <p>{t('audit.undoDialog.summary', { action: actionLabel })}</p>
          {describe.isPending ? (
            <p className="inline-flex items-center gap-2 text-sc-text-muted">
              <Loader2 size={14} className="animate-spin" aria-hidden />
              {t('audit.undoDialog.describing')}
            </p>
          ) : describe.isError ? (
            <p role="alert" className="text-sc-danger">
              {t('audit.undoDialog.describeError')}
            </p>
          ) : (
            <p className="rounded-md border border-sc-border bg-sc-bg px-3 py-2 text-sc-text">
              {describe.data?.description}
            </p>
          )}
        </div>

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
            variant="primary"
            onClick={onConfirm}
            disabled={isSubmitting || describe.isPending}
            className="w-full justify-center px-4 py-3 text-base"
            leadingIcon={
              isSubmitting ? <Loader2 className="animate-spin" size={16} aria-hidden /> : undefined
            }
          >
            {isSubmitting ? t('audit.undoDialog.confirming') : t('audit.undoDialog.confirm')}
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
  if (error.status === 404) return t('audit.undoDialog.errors.notFound');
  if (error.status === 409) return t('audit.undoDialog.errors.conflict');
  if (error.status === 410) return t('audit.undoDialog.errors.alreadyUndone');
  if (error.status === 422) return t('audit.undoDialog.errors.notSupported');
  if (error.status === 403) return t('audit.undoDialog.errors.forbidden');
  return t('audit.undoDialog.errors.generic');
}
