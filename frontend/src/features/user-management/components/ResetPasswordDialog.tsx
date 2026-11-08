import { KeyRound, Loader2, X } from 'lucide-react';
import { useState, type FormEvent } from 'react';
import { createPortal } from 'react-dom';
import { useTranslation } from 'react-i18next';

import { Button } from '@/components/ui/Button';
import { ApiError } from '@/lib/apiClient';

const MIN_PASSWORD_LENGTH = 12;

interface ResetPasswordDialogProps {
  email: string;
  isSubmitting: boolean;
  error: unknown;
  onClose: () => void;
  onSubmit: (newPassword: string) => void;
}

export function ResetPasswordDialog({
  email,
  isSubmitting,
  error,
  onClose,
  onSubmit,
}: ResetPasswordDialogProps) {
  const { t } = useTranslation();
  const [newPassword, setNewPassword] = useState('');
  const [confirmPassword, setConfirmPassword] = useState('');
  const [clientError, setClientError] = useState<string | null>(null);

  const serverMessage = resolveServerMessage(error, t);

  function submit(event: FormEvent) {
    event.preventDefault();
    if (newPassword.length < MIN_PASSWORD_LENGTH) {
      setClientError(t('adminUserDetail.account.resetPasswordDialog.errors.tooShort'));
      return;
    }
    if (newPassword !== confirmPassword) {
      setClientError(t('adminUserDetail.account.resetPasswordDialog.errors.mismatch'));
      return;
    }
    setClientError(null);
    onSubmit(newPassword);
  }

  return createPortal(
    <div
      role="dialog"
      aria-modal="true"
      aria-labelledby="reset-password-dialog-title"
      className="fixed inset-0 z-50 flex items-center justify-center bg-black/50 p-4"
    >
      <form
        onSubmit={submit}
        className="flex w-full max-w-2xl flex-col gap-5 rounded-xl border border-sc-border bg-sc-bg-card p-7 shadow-xl"
      >
        <header className="flex items-start justify-between gap-3">
          <div className="flex items-center gap-3">
            <span
              aria-hidden
              className="inline-flex h-10 w-10 flex-none items-center justify-center rounded-full bg-sc-primary/10 text-sc-primary"
            >
              <KeyRound size={20} />
            </span>
            <h2 id="reset-password-dialog-title" className="text-xl font-semibold text-sc-text">
              {t('adminUserDetail.account.resetPasswordDialog.title')}
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
          {t('adminUserDetail.account.resetPasswordDialog.body', { email })}
        </p>

        <div className="flex flex-col gap-3">
          <label className="flex flex-col gap-1 text-sm text-sc-text">
            <span>{t('adminUserDetail.account.resetPasswordDialog.newPassword')}</span>
            <input
              type="password"
              autoComplete="new-password"
              value={newPassword}
              onChange={(event) => setNewPassword(event.target.value)}
              className="rounded-md border border-sc-border bg-sc-bg px-3 py-2 text-sm text-sc-text focus:outline-none focus:ring-2 focus:ring-sc-primary"
            />
          </label>
          <label className="flex flex-col gap-1 text-sm text-sc-text">
            <span>{t('adminUserDetail.account.resetPasswordDialog.confirmPassword')}</span>
            <input
              type="password"
              autoComplete="new-password"
              value={confirmPassword}
              onChange={(event) => setConfirmPassword(event.target.value)}
              className="rounded-md border border-sc-border bg-sc-bg px-3 py-2 text-sm text-sc-text focus:outline-none focus:ring-2 focus:ring-sc-primary"
            />
          </label>
        </div>

        {clientError ? (
          <p
            role="alert"
            className="rounded-md border border-sc-danger/40 bg-sc-danger/10 px-3 py-2 text-sm text-sc-danger"
          >
            {clientError}
          </p>
        ) : null}

        {!clientError && serverMessage ? (
          <p
            role="alert"
            className="rounded-md border border-sc-danger/40 bg-sc-danger/10 px-3 py-2 text-sm text-sc-danger"
          >
            {serverMessage}
          </p>
        ) : null}

        <div className="flex flex-col gap-2">
          <Button
            type="submit"
            variant="primary"
            disabled={isSubmitting}
            className="w-full justify-center px-4 py-3 text-base"
            leadingIcon={
              isSubmitting ? <Loader2 className="animate-spin" size={16} aria-hidden /> : undefined
            }
          >
            {isSubmitting
              ? t('adminUserDetail.account.resetPasswordDialog.submitting')
              : t('adminUserDetail.account.resetPasswordDialog.submit')}
          </Button>
          <Button
            type="button"
            variant="secondary"
            onClick={onClose}
            className="w-full justify-center px-4 py-3 text-base"
          >
            {t('common.cancel')}
          </Button>
        </div>
      </form>
    </div>,
    document.body,
  );
}

function resolveServerMessage(error: unknown, t: (key: string) => string): string | null {
  if (!(error instanceof ApiError)) return null;
  if (error.status === 404) return t('adminUserDetail.account.errors.notFound');
  if (error.status === 403) return t('adminUserDetail.account.errors.forbidden');
  if (error.status === 422) return t('adminUserDetail.account.resetPasswordDialog.errors.tooShort');
  return t('adminUserDetail.account.errors.generic');
}
