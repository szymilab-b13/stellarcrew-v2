import { Loader2 } from 'lucide-react';
import { useTranslation } from 'react-i18next';

import { ASSIGNABLE_ROLES, type WorkspaceMember, type WorkspaceRole } from '../types';
import { MemberRowActionsMenu } from './MemberRowActionsMenu';

interface MembersTableProps {
  members: WorkspaceMember[];
  canManage: boolean;
  pendingRoleUserId: string | null;
  onChangeRole: (userId: string, role: Exclude<WorkspaceRole, 'owner'>) => void;
  onRemoveClick: (member: WorkspaceMember) => void;
}

export function MembersTable({
  members,
  canManage,
  pendingRoleUserId,
  onChangeRole,
  onRemoveClick,
}: MembersTableProps) {
  const { t, i18n } = useTranslation();
  const dateFormatter = new Intl.DateTimeFormat(i18n.resolvedLanguage ?? 'en', {
    dateStyle: 'medium',
  });

  return (
    <div className="overflow-x-auto rounded-md border border-sc-border bg-sc-bg-card">
      <table className="min-w-full divide-y divide-sc-border text-left text-sm">
        <thead className="bg-sc-bg-secondary text-xs uppercase tracking-wider text-sc-text-muted">
          <tr>
            <th scope="col" className="px-4 py-3 font-semibold">
              {t('workspaceMembers.columns.email')}
            </th>
            <th scope="col" className="px-4 py-3 font-semibold">
              {t('workspaceMembers.columns.role')}
            </th>
            <th scope="col" className="px-4 py-3 font-semibold">
              {t('workspaceMembers.columns.joinedAt')}
            </th>
            {canManage ? <th scope="col" className="px-4 py-3" /> : null}
          </tr>
        </thead>
        <tbody className="divide-y divide-sc-border">
          {members.length === 0 ? (
            <tr>
              <td
                colSpan={canManage ? 4 : 3}
                className="px-4 py-6 text-center text-sm text-sc-text-muted"
              >
                {t('workspaceMembers.table.empty')}
              </td>
            </tr>
          ) : null}
          {members.map((member) => {
            const isOwner = member.role === 'owner';
            const roleDisabled = !canManage || isOwner || pendingRoleUserId === member.userId;
            return (
              <tr key={member.userId} className="text-sc-text">
                <td className="px-4 py-3 font-medium">{member.email}</td>
                <td className="px-4 py-3">
                  {canManage && !isOwner ? (
                    <select
                      aria-label={t('workspaceMembers.table.roleAria', { email: member.email })}
                      value={member.role}
                      disabled={roleDisabled}
                      onChange={(event) =>
                        onChangeRole(
                          member.userId,
                          event.target.value as Exclude<WorkspaceRole, 'owner'>,
                        )
                      }
                      className="rounded-md border border-sc-border bg-sc-bg px-2 py-1 text-sm text-sc-text focus:outline-none focus:ring-1 focus:ring-sc-primary/30 disabled:cursor-not-allowed disabled:opacity-60"
                    >
                      {ASSIGNABLE_ROLES.map((role) => (
                        <option key={role} value={role}>
                          {t(`workspaceMembers.roles.${role}`)}
                        </option>
                      ))}
                    </select>
                  ) : (
                    <span className="inline-flex items-center rounded-full bg-sc-primary/10 px-2 py-0.5 text-xs font-semibold text-sc-primary">
                      {t(`workspaceMembers.roles.${member.role}`)}
                    </span>
                  )}
                </td>
                <td className="px-4 py-3 text-sc-text-muted">
                  {dateFormatter.format(new Date(member.joinedAt))}
                </td>
                {canManage ? (
                  <td className="px-4 py-3 text-right">
                    {!isOwner ? (
                      pendingRoleUserId === member.userId ? (
                        <Loader2
                          className="inline-block animate-spin text-sc-text-muted"
                          size={14}
                          aria-hidden
                        />
                      ) : (
                        <MemberRowActionsMenu member={member} onRemoveClick={onRemoveClick} />
                      )
                    ) : null}
                  </td>
                ) : null}
              </tr>
            );
          })}
        </tbody>
      </table>
    </div>
  );
}
