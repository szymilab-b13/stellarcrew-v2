import { useTranslation } from 'react-i18next';
import { Link, useParams } from 'react-router-dom';

import { useUserAudit, useUserDetail } from '../api/useUserDetail';

import { RolePill } from './RolePill';
import { UserAccountSection } from './UserAccountSection';

export function AdminUserDetailPage() {
  const { t } = useTranslation();
  const params = useParams<{ id: string }>();
  const userId = params.id ?? '';
  const detail = useUserDetail(userId);
  const audit = useUserAudit(userId);

  if (detail.isPending) {
    return <p className="p-6 text-sm text-sc-text-muted">{t('adminUserDetail.loading')}</p>;
  }

  if (detail.isError || !detail.data) {
    return (
      <p className="p-6 text-sm text-red-600" role="alert">
        {t('adminUserDetail.loadError')}
      </p>
    );
  }

  const user = detail.data;

  return (
    <div className="flex flex-col gap-6 p-6">
      <nav className="text-xs text-sc-text-muted" aria-label="breadcrumb">
        <Link to="/admin/users" className="hover:underline">
          {t('adminUserDetail.breadcrumb.users')}
        </Link>
        <span className="mx-2">/</span>
        <span>{user.email}</span>
      </nav>

      <header className="flex items-center gap-4 rounded-lg border border-sc-border bg-sc-bg-card p-4">
        <span
          className="inline-flex h-12 w-12 flex-none items-center justify-center rounded-lg text-base font-semibold text-white"
          style={{ backgroundColor: user.avatarColor }}
          aria-hidden
        >
          {user.email.slice(0, 2).toUpperCase()}
        </span>
        <div className="flex min-w-0 flex-1 flex-col">
          <h1 className="truncate text-xl font-semibold text-sc-text">{user.email}</h1>
          {user.displayName !== null ? (
            <p className="truncate text-sm text-sc-text-muted">{user.displayName}</p>
          ) : null}
        </div>
        <div className="flex flex-col items-end gap-1">
          {user.isSuperAdmin ? (
            <span className="inline-flex items-center rounded-full bg-purple-100 px-2 py-0.5 text-xs font-medium text-purple-800 ring-1 ring-inset ring-purple-200">
              {t('adminUserDetail.pills.superAdmin')}
            </span>
          ) : null}
          {user.deactivatedAt !== null ? (
            <span className="inline-flex items-center rounded-full bg-red-100 px-2 py-0.5 text-xs font-medium text-red-800 ring-1 ring-inset ring-red-200">
              {t('adminUserDetail.pills.deactivated')}
            </span>
          ) : null}
        </div>
      </header>

      <section className="flex flex-col gap-3 rounded-lg border border-sc-border bg-sc-bg-card p-4">
        <h2 className="text-sm font-semibold text-sc-text">
          {t('adminUserDetail.sections.workspaces')}
        </h2>
        {user.workspaceAssignments.length === 0 ? (
          <p className="text-xs text-sc-text-muted">{t('adminUserDetail.workspaces.empty')}</p>
        ) : (
          <ul className="flex flex-col gap-2">
            {user.workspaceAssignments.map((assignment) => (
              <li
                key={assignment.workspaceId}
                className="flex items-center gap-2 rounded-md border border-sc-border bg-sc-bg px-3 py-2"
              >
                <span className="min-w-0 flex-1 truncate text-sm font-medium text-sc-text">
                  {assignment.workspaceName}
                </span>
                <RolePill role={assignment.role as never} size="sm" />
              </li>
            ))}
          </ul>
        )}
      </section>

      <UserAccountSection
        userId={user.id}
        email={user.email}
        isDeactivated={user.deactivatedAt !== null}
      />

      <section className="flex flex-col gap-3 rounded-lg border border-sc-border bg-sc-bg-card p-4">
        <div className="flex items-center justify-between">
          <h2 className="text-sm font-semibold text-sc-text">
            {t('adminUserDetail.sections.audit')}
          </h2>
          <Link
            to={`/admin/audit?resource_id=${user.id}`}
            className="text-xs text-sc-text-muted hover:underline"
          >
            {t('adminUserDetail.audit.seeAll')}
          </Link>
        </div>
        {audit.isPending ? (
          <p className="text-xs text-sc-text-muted">{t('adminUserDetail.audit.loading')}</p>
        ) : audit.isError || !audit.data || audit.data.length === 0 ? (
          <p className="text-xs text-sc-text-muted">{t('adminUserDetail.audit.empty')}</p>
        ) : (
          <ul className="flex flex-col gap-1">
            {audit.data.map((entry) => (
              <li
                key={entry.id}
                className="flex flex-col rounded-md border border-sc-border bg-sc-bg px-3 py-2"
              >
                <span className="text-xs font-mono text-sc-text">{entry.action}</span>
                <span className="text-[11px] text-sc-text-muted">
                  {new Date(entry.occurredAt).toLocaleString()}
                  {entry.actorEmail !== null ? ` · ${entry.actorEmail}` : ''}
                  {entry.undoneAt !== null ? ` · ${t('adminUserDetail.audit.undone')}` : ''}
                </span>
              </li>
            ))}
          </ul>
        )}
      </section>
    </div>
  );
}
