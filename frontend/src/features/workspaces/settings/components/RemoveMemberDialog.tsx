import { AlertTriangle, Loader2, X } from 'lucide-react';
import { useTranslation } from 'react-i18next';

import { Button } from '@/components/ui/Button';
import { ApiError } from '@/lib/apiClient';

import type { WorkspaceMember } from '../types';

interface RemoveMemberDialogProps {
  member: WorkspaceMember;
  isSubmitting: boolean;
  error: unknown;
  onClose: () => void;
  onConfirm: () => void;
}

export function RemoveMemberDialog({
  member,
  isSubmitting,
  error,
  onClose,
  onConfirm,
}: RemoveMemberDialogProps) {
  const { t } = useTranslation();
  const serverMessage = resolveServerMessage(error, t);

  return (
    <div
      role="dialog"
      aria-modal="true"
      aria-labelledby="remove-member-title"
      className="fixed inset-0 z-50 flex items-center justify-center bg-black/50 p-4"
    >
      <div className="flex w-full max-w-md flex-col gap-5 rounded-xl border border-sc-border bg-sc-bg-card p-7 shadow-xl">
        <header className="flex items-start justify-between gap-3">
          <div className="flex items-center gap-3">
            <span
              aria-hidden
              className="inline-flex h-10 w-10 flex-none items-center justify-center rounded-full bg-sc-danger/10 text-sc-danger"
            >
              <AlertTriangle size={20} />
            </span>
            <h2 id="remove-member-title" className="text-xl font-semibold text-sc-text">
              {t('workspaceMembers.remove.title')}
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

        <p className="text-sm text-sc-text">
          {t('workspaceMembers.remove.body', { email: member.email })}
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
            className="w-full justify-center px-4 py-3 text-base"
            leadingIcon={
              isSubmitting ? <Loader2 className="animate-spin" size={16} aria-hidden /> : undefined
            }
          >
            {isSubmitting
              ? t('workspaceMembers.remove.confirming')
              : t('workspaceMembers.remove.confirm')}
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
  if (error.status === 422) return t('workspaceMembers.remove.errors.adminMustBeDowngraded');
  if (error.status === 404) return t('workspaceMembers.remove.errors.notFound');
  if (error.status === 403) return t('workspaceMembers.remove.errors.forbidden');
  return t('workspaceMembers.remove.errors.generic');
}
