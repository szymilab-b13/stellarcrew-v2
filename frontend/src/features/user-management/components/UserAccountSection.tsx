import { useState } from 'react';
import { useTranslation } from 'react-i18next';

import { useDeactivateUser, useReactivateUser } from '../api/useUserDetail';
import { DeactivationConfirmDialog } from './DeactivationConfirmDialog';

interface UserAccountSectionProps {
  userId: string;
  email: string;
  isDeactivated: boolean;
}

export function UserAccountSection({ userId, email, isDeactivated }: UserAccountSectionProps) {
  const { t } = useTranslation();
  const [dialogOpen, setDialogOpen] = useState(false);
  const deactivate = useDeactivateUser(userId);
  const reactivate = useReactivateUser(userId);

  const mutation = isDeactivated ? reactivate : deactivate;

  function openDialog() {
    mutation.reset();
    setDialogOpen(true);
  }

  function closeDialog() {
    setDialogOpen(false);
    mutation.reset();
  }

  function confirm() {
    mutation.mutate(undefined, {
      onSuccess: () => {
        setDialogOpen(false);
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
          onClick={openDialog}
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
          disabled
          className="inline-flex items-center rounded-md border border-sc-border bg-sc-bg px-3 py-1.5 text-xs font-semibold text-sc-text-muted disabled:cursor-not-allowed disabled:opacity-60"
          title={t('adminUserDetail.account.resetPasswordPendingTooltip')}
        >
          {t('adminUserDetail.account.resetPassword')}
        </button>
      </div>
      {dialogOpen ? (
        <DeactivationConfirmDialog
          mode={isDeactivated ? 'reactivate' : 'deactivate'}
          email={email}
          isSubmitting={mutation.isPending}
          error={mutation.error}
          onClose={closeDialog}
          onConfirm={confirm}
        />
      ) : null}
    </section>
  );
}
