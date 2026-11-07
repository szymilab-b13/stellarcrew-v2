import { useState } from 'react';
import { useTranslation } from 'react-i18next';

import { useDeactivateUser, useReactivateUser, useResetUserPassword } from '../api/useUserDetail';
import { DeactivationConfirmDialog } from './DeactivationConfirmDialog';
import { ResetPasswordDialog } from './ResetPasswordDialog';

interface UserAccountSectionProps {
  userId: string;
  email: string;
  isDeactivated: boolean;
}

export function UserAccountSection({ userId, email, isDeactivated }: UserAccountSectionProps) {
  const { t } = useTranslation();
  const [activationDialogOpen, setActivationDialogOpen] = useState(false);
  const [resetPasswordDialogOpen, setResetPasswordDialogOpen] = useState(false);
  const deactivate = useDeactivateUser(userId);
  const reactivate = useReactivateUser(userId);
  const resetPassword = useResetUserPassword(userId);

  const activationMutation = isDeactivated ? reactivate : deactivate;

  function openActivationDialog() {
    activationMutation.reset();
    setActivationDialogOpen(true);
  }

  function closeActivationDialog() {
    setActivationDialogOpen(false);
    activationMutation.reset();
  }

  function confirmActivation() {
    activationMutation.mutate(undefined, {
      onSuccess: () => {
        setActivationDialogOpen(false);
      },
    });
  }

  function openResetPasswordDialog() {
    resetPassword.reset();
    setResetPasswordDialogOpen(true);
  }

  function closeResetPasswordDialog() {
    setResetPasswordDialogOpen(false);
    resetPassword.reset();
  }

  function submitResetPassword(newPassword: string) {
    resetPassword.mutate(newPassword, {
      onSuccess: () => {
        setResetPasswordDialogOpen(false);
      },
    });
  }

  return (
    <section className="flex flex-col gap-3 rounded-lg border border-sc-border bg-sc-bg-card p-4">
      <h2 className="text-sm font-semibold text-sc-text">
        {t('adminUserDetail.sections.account')}
      </h2>
      <div className="flex flex-wrap gap-2">
        <button
          type="button"
          onClick={openActivationDialog}
          className={
            isDeactivated
              ? 'inline-flex items-center rounded-md border border-sc-border bg-emerald-500/10 px-3 py-1.5 text-xs font-semibold text-emerald-700 transition-colors hover:bg-emerald-500/20'
              : 'inline-flex items-center rounded-md border border-sc-border bg-red-500/10 px-3 py-1.5 text-xs font-semibold text-red-700 transition-colors hover:bg-red-500/20'
          }
        >
          {isDeactivated
            ? t('adminUserDetail.account.reactivate')
            : t('adminUserDetail.account.deactivate')}
        </button>
        <button
          type="button"
          onClick={openResetPasswordDialog}
          className="inline-flex items-center rounded-md border border-sc-border bg-sc-primary/10 px-3 py-1.5 text-xs font-semibold text-sc-primary transition-colors hover:bg-sc-primary/20"
        >
          {t('adminUserDetail.account.resetPassword')}
        </button>
      </div>
      {activationDialogOpen ? (
        <DeactivationConfirmDialog
          mode={isDeactivated ? 'reactivate' : 'deactivate'}
          email={email}
          isSubmitting={activationMutation.isPending}
          error={activationMutation.error}
          onClose={closeActivationDialog}
          onConfirm={confirmActivation}
        />
      ) : null}
      {resetPasswordDialogOpen ? (
        <ResetPasswordDialog
          email={email}
          isSubmitting={resetPassword.isPending}
          error={resetPassword.error}
          onClose={closeResetPasswordDialog}
          onSubmit={submitResetPassword}
        />
      ) : null}
    </section>
  );
}
