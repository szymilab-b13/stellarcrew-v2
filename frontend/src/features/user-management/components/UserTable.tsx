import { useTranslation } from 'react-i18next';

import type { UserListItem } from '../types';

import { UserRowActionsMenu } from './UserRowActionsMenu';

interface UserTableProps {
  users: UserListItem[];
}

export function UserTable({ users }: UserTableProps) {
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
              {t('userManagement.columns.email')}
            </th>
            <th scope="col" className="px-4 py-3 font-semibold">
              {t('userManagement.columns.superAdmin')}
            </th>
            <th scope="col" className="px-4 py-3 font-semibold">
              {t('userManagement.columns.workspaces')}
            </th>
            <th scope="col" className="px-4 py-3 font-semibold">
              {t('userManagement.columns.status')}
            </th>
            <th scope="col" className="px-4 py-3 font-semibold">
              {t('userManagement.columns.createdAt')}
            </th>
            <th scope="col" className="px-4 py-3 text-right font-semibold">
              <span className="sr-only">{t('userManagement.columns.actions')}</span>
            </th>
          </tr>
        </thead>
        <tbody className="divide-y divide-sc-border">
          {users.length === 0 ? (
            <tr>
              <td colSpan={6} className="px-4 py-6 text-center text-sm text-sc-text-muted">
                {t('userManagement.table.empty')}
              </td>
            </tr>
          ) : null}
          {users.map((user) => (
            <tr key={user.id} className="text-sc-text">
              <td className="px-4 py-3 font-medium">{user.email}</td>
              <td className="px-4 py-3">
                {user.isSuperAdmin ? (
                  <span className="inline-flex items-center rounded-full bg-sc-primary/10 px-2 py-0.5 text-xs font-semibold text-sc-primary">
                    {t('userManagement.table.badgeSuperAdmin')}
                  </span>
                ) : (
                  <span className="text-xs text-sc-text-muted">
                    {t('userManagement.table.badgeRegular')}
                  </span>
                )}
              </td>
              <td className="px-4 py-3 tabular-nums">{user.workspaceCount}</td>
              <td className="px-4 py-3">
                {user.deactivatedAt ? (
                  <span className="inline-flex items-center rounded-full bg-sc-danger/10 px-2 py-0.5 text-xs font-semibold text-sc-danger">
                    {t('userManagement.table.statusDeactivated')}
                  </span>
                ) : (
                  <span className="inline-flex items-center rounded-full bg-emerald-500/10 px-2 py-0.5 text-xs font-semibold text-emerald-600">
                    {t('userManagement.table.statusActive')}
                  </span>
                )}
              </td>
              <td className="px-4 py-3 text-sc-text-muted">
                {dateFormatter.format(new Date(user.createdAt))}
              </td>
              <td className="px-4 py-3 text-right">
                <UserRowActionsMenu
                  userId={user.id}
                  email={user.email}
                  isDeactivated={user.deactivatedAt !== null}
                />
              </td>
            </tr>
          ))}
        </tbody>
      </table>
    </div>
  );
}
