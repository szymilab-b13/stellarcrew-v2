import { Loader2 } from 'lucide-react';
import { useMemo, useState } from 'react';
import { useTranslation } from 'react-i18next';
import { Navigate, useParams } from 'react-router-dom';

import { usePageBreadcrumbs } from '@/components/app/useBreadcrumbs';
import { useMe } from '@/features/auth/api/useMe';
import { useWorkspacesList } from '@/features/workspaces/api/useWorkspaces';

import {
  useAssignMember,
  useChangeMemberRole,
  useRemoveMember,
  useWorkspaceMembers,
} from '../api/useWorkspaceMembers';
import { canManageMembers, type WorkspaceMember, type WorkspaceRole } from '../types';
import { AddMemberForm, type AddMemberValues } from './AddMemberForm';
import { MembersTable } from './MembersTable';
import { RemoveMemberDialog } from './RemoveMemberDialog';

export function MembersSettingsPage() {
  const { t } = useTranslation();
  const params = useParams<{ wsId: string }>();
  const workspaceId = params.wsId ?? null;

  const me = useMe();
  const workspacesQuery = useWorkspacesList();
  const membersQuery = useWorkspaceMembers(workspaceId);
  const assignMutation = useAssignMember(workspaceId ?? '');
  const changeRoleMutation = useChangeMemberRole(workspaceId ?? '');
  const removeMutation = useRemoveMember(workspaceId ?? '');

  const [pendingRoleUserId, setPendingRoleUserId] = useState<string | null>(null);
  const [memberToRemove, setMemberToRemove] = useState<WorkspaceMember | null>(null);

  const workspace = workspacesQuery.data?.find((w) => w.id === workspaceId) ?? null;

  const breadcrumbs = useMemo(
    () => [
      { label: t('workspaces.breadcrumb'), to: '/workspaces' },
      ...(workspace ? [{ label: workspace.name }] : []),
      { label: t('workspaceMembers.breadcrumb') },
    ],
    [t, workspace],
  );
  usePageBreadcrumbs(breadcrumbs);

  if (workspaceId === null) {
    return <Navigate to="/workspaces" replace />;
  }
  const activeWorkspaceId = workspaceId;

  const items = membersQuery.data?.items ?? [];
  const myEmail = me.data?.email ?? null;
  const myMembership = items.find((member) => member.email === myEmail) ?? null;
  const isSuperAdmin = me.data?.role === 'admin';
  const canManage = isSuperAdmin || canManageMembers(myMembership?.role ?? null);
  const excludedUserIds = new Set(items.map((member) => member.userId));

  function changeRole(userId: string, role: Exclude<WorkspaceRole, 'owner'>) {
    setPendingRoleUserId(userId);
    changeRoleMutation.mutate(
      { workspaceId: activeWorkspaceId, targetUserId: userId, role },
      { onSettled: () => setPendingRoleUserId(null) },
    );
  }

  function submitAdd(values: AddMemberValues) {
    assignMutation.mutate({
      workspaceId: activeWorkspaceId,
      targetUserId: values.targetUserId,
      role: values.role,
    });
  }

  function confirmRemove() {
    if (memberToRemove === null) return;
    removeMutation.mutate(
      { workspaceId: activeWorkspaceId, targetUserId: memberToRemove.userId },
      {
        onSuccess: () => {
          setMemberToRemove(null);
          removeMutation.reset();
        },
      },
    );
  }

  function closeRemoveDialog() {
    setMemberToRemove(null);
    removeMutation.reset();
  }

  return (
    <div className="mx-auto flex w-full max-w-5xl flex-col gap-6 px-6 py-8">
      <header className="flex flex-col gap-1">
        <h1 className="text-2xl font-semibold tracking-tight text-sc-text">
          {t('workspaceMembers.title')}
        </h1>
        <p className="text-sm text-sc-text-muted">{t('workspaceMembers.subtitle')}</p>
      </header>

      {membersQuery.isPending ? (
        <p className="flex items-center gap-2 text-sm text-sc-text-muted">
          <Loader2 className="animate-spin" size={14} aria-hidden />
          {t('workspaceMembers.loading')}
        </p>
      ) : null}

      {membersQuery.isError ? (
        <p
          role="alert"
          className="rounded-md border border-sc-danger/40 bg-sc-danger/10 px-3 py-2 text-sm text-sc-danger"
        >
          {t('workspaceMembers.loadError')}
        </p>
      ) : null}

      {membersQuery.data ? (
        <section aria-label={t('workspaceMembers.listAria')} className="flex flex-col gap-3">
          <MembersTable
            members={items}
            canManage={canManage}
            pendingRoleUserId={pendingRoleUserId}
            onChangeRole={changeRole}
            onRemoveClick={setMemberToRemove}
          />
          <p className="text-xs text-sc-text-muted">
            {t('workspaceMembers.total', { count: items.length })}
          </p>
        </section>
      ) : null}

      {canManage ? (
        <AddMemberForm
          excludedUserIds={excludedUserIds}
          isSubmitting={assignMutation.isPending}
          error={assignMutation.error}
          onSubmit={submitAdd}
        />
      ) : null}

      {memberToRemove ? (
        <RemoveMemberDialog
          member={memberToRemove}
          isSubmitting={removeMutation.isPending}
          error={removeMutation.error}
          onClose={closeRemoveDialog}
          onConfirm={confirmRemove}
        />
      ) : null}
    </div>
  );
}
