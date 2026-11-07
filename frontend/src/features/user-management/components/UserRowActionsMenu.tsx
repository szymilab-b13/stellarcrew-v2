import { Eye, KeyRound, MoreHorizontal, UserCheck, UserX } from 'lucide-react';
import { useEffect, useRef, useState } from 'react';
import { useTranslation } from 'react-i18next';
import { useNavigate } from 'react-router-dom';

import { useDeactivateUser, useReactivateUser, useResetUserPassword } from '../api/useUserDetail';

import { DeactivationConfirmDialog } from './DeactivationConfirmDialog';
import { ResetPasswordDialog } from './ResetPasswordDialog';

interface UserRowActionsMenuProps {
  userId: string;
  email: string;
  isDeactivated: boolean;
}

export function UserRowActionsMenu({ userId, email, isDeactivated }: UserRowActionsMenuProps) {
  const { t } = useTranslation();
  const navigate = useNavigate();
  const containerRef = useRef<HTMLDivElement>(null);
  const [open, setOpen] = useState(false);
  const [activationDialogOpen, setActivationDialogOpen] = useState(false);
  const [resetPasswordDialogOpen, setResetPasswordDialogOpen] = useState(false);

  const deactivate = useDeactivateUser(userId);
  const reactivate = useReactivateUser(userId);
  const resetPassword = useResetUserPassword(userId);

  const activationMutation = isDeactivated ? reactivate : deactivate;

  useEffect(() => {
    if (!open) return;
    function handleClickOutside(event: MouseEvent) {
      if (!containerRef.current?.contains(event.target as Node)) {
        setOpen(false);
      }
    }
    document.addEventListener('mousedown', handleClickOutside);
    return () => document.removeEventListener('mousedown', handleClickOutside);
  }, [open]);

  function openDetails() {
    setOpen(false);
    navigate(`/admin/users/${userId}`);
  }

  function openActivationDialog() {
    setOpen(false);
    activationMutation.reset();
    setActivationDialogOpen(true);
  }

  function closeActivationDialog() {
    setActivationDialogOpen(false);
    activationMutation.reset();
  }

  function confirmActivation() {
    activationMutation.mutate(undefined, {
      onSuccess: () => setActivationDialogOpen(false),
    });
  }

  function openResetPasswordDialog() {
    setOpen(false);
    resetPassword.reset();
    setResetPasswordDialogOpen(true);
  }

  function closeResetPasswordDialog() {
    setResetPasswordDialogOpen(false);
    resetPassword.reset();
  }

  function submitResetPassword(newPassword: string) {
    resetPassword.mutate(newPassword, {
      onSuccess: () => setResetPasswordDialogOpen(false),
    });
  }

  return (
    <div ref={containerRef} className="relative inline-flex">
      <button
        type="button"
        onClick={() => setOpen((previous) => !previous)}
        className="inline-flex items-center gap-2 rounded-lg px-2 py-1.5 text-sm transition-colors hover:bg-sc-bg-card"
        aria-haspopup="menu"
        aria-expanded={open}
        aria-label={t('userManagement.table.rowActionsAria', { email })}
      >
        <MoreHorizontal size={16} className="text-sc-text-muted" aria-hidden />
      </button>

      {open ? (
        <div
          role="menu"
          className="absolute right-0 top-full z-20 mt-2 w-56 overflow-hidden rounded-md border border-sc-border bg-sc-bg-card shadow-lg"
        >
          <button
            type="button"
            role="menuitem"
            onClick={openDetails}
            className="flex w-full items-center gap-2 px-3 py-2 text-left text-sm text-sc-text transition-colors hover:bg-sc-bg-secondary"
          >
            <Eye size={14} aria-hidden />
            {t('userManagement.row.actionDetails')}
          </button>
          <button
            type="button"
            role="menuitem"
            onClick={openActivationDialog}
            className="flex w-full items-center gap-2 px-3 py-2 text-left text-sm text-sc-text transition-colors hover:bg-sc-bg-secondary"
          >
            {isDeactivated ? <UserCheck size={14} aria-hidden /> : <UserX size={14} aria-hidden />}
            {isDeactivated
              ? t('userManagement.row.actionReactivate')
              : t('userManagement.row.actionDeactivate')}
          </button>
          <button
            type="button"
            role="menuitem"
            onClick={openResetPasswordDialog}
            className="flex w-full items-center gap-2 px-3 py-2 text-left text-sm text-sc-text transition-colors hover:bg-sc-bg-secondary"
          >
            <KeyRound size={14} aria-hidden />
            {t('userManagement.row.actionResetPassword')}
          </button>
        </div>
      ) : null}

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
    </div>
  );
}
