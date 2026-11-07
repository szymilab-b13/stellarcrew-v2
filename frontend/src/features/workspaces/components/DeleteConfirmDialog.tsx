import { Loader2, X } from 'lucide-react';
import { useTranslation } from 'react-i18next';

import { Button } from '@/components/ui/Button';
import { ApiError } from '@/lib/apiClient';

import type { Workspace } from '../types';

interface DeleteConfirmDialogProps {
  workspace: Workspace;
  isSubmitting: boolean;
  error: unknown;
  onClose: () => void;
  onConfirm: () => void;
}

export function DeleteConfirmDialog({
  workspace,
  isSubmitting,
  error,
  onClose,
  onConfirm,
}: DeleteConfirmDialogProps) {
  const { t } = useTranslation();
  const serverMessage = resolveServerMessage(error, t);

  return (
    <div
      role="dialog"
      aria-modal="true"
      aria-labelledby="workspace-delete-title"
      className="fixed inset-0 z-50 flex items-center justify-center bg-black/50 p-4"
    >
      <div className="flex w-full max-w-sm flex-col gap-4 rounded-lg border border-sc-border bg-sc-bg-card p-6 shadow-xl">
        <header className="flex items-start justify-between gap-3">
          <h2 id="workspace-delete-title" className="text-lg font-semibold text-sc-text">
            {t('workspaces.delete.title')}
          </h2>
          <button
            type="button"
            onClick={onClose}
            aria-label={t('common.close')}
            className="inline-flex h-7 w-7 items-center justify-center rounded-md text-sc-text-muted transition-colors hover:bg-sc-bg hover:text-sc-text"
          >
            <X size={16} aria-hidden />
          </button>
        </header>

        <p className="text-sm text-sc-text">
          {t('workspaces.delete.body', { name: workspace.name })}
        </p>

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
            disabled={isSubmitting}
            leadingIcon={
              isSubmitting ? <Loader2 className="animate-spin" size={14} aria-hidden /> : undefined
            }
          >
            {isSubmitting ? t('workspaces.delete.deleting') : t('workspaces.delete.confirm')}
          </Button>
          <Button variant="secondary" onClick={onClose}>
            {t('common.cancel')}
          </Button>
        </div>
      </div>
    </div>
  );
}

function resolveServerMessage(error: unknown, t: (key: string) => string): string | null {
  if (!(error instanceof ApiError)) return null;
  if (error.status === 403) return t('workspaces.delete.errors.forbidden');
  if (error.status === 404) return t('workspaces.delete.errors.notFound');
  return t('workspaces.delete.errors.generic');
}
