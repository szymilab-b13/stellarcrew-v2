import { Loader2, UserPlus } from 'lucide-react';
import { useMemo, useState } from 'react';
import { useTranslation } from 'react-i18next';

import { Button } from '@/components/ui/Button';
import { usePageBreadcrumbs } from '@/components/app/useBreadcrumbs';
import { useWorkspacesList } from '@/features/workspaces/api/useWorkspaces';

import { useCreateUser, useUsersList } from '../api/useUsers';
import { AddUserDialog, type AddUserValues } from './AddUserDialog';
import { UserFilters } from './UserFilters';
import { UserTable } from './UserTable';
import { DEFAULT_USER_FILTERS, toApiFilters, type UserFiltersValue } from './userFilters.state';

export function AdminUsersPage() {
  const { t } = useTranslation();
  const [filters, setFilters] = useState<UserFiltersValue>(DEFAULT_USER_FILTERS);
  const [dialogOpen, setDialogOpen] = useState(false);

  const breadcrumbs = useMemo(() => [{ label: t('userManagement.breadcrumb') }], [t]);
  usePageBreadcrumbs(breadcrumbs);

  const apiFilters = toApiFilters(filters);
  const usersQuery = useUsersList(apiFilters);
  const workspacesQuery = useWorkspacesList();
  const createMutation = useCreateUser();

  function openDialog() {
    createMutation.reset();
    setDialogOpen(true);
  }

  function closeDialog() {
    setDialogOpen(false);
  }

  function handleCreate(values: AddUserValues) {
    createMutation.mutate(values, { onSuccess: closeDialog });
  }

  return (
    <div className="flex w-full flex-col gap-6">
      <header className="flex items-start justify-between gap-4">
        <div className="flex flex-col gap-1">
          <h1 className="text-2xl font-semibold tracking-tight text-sc-text">
            {t('userManagement.title')}
          </h1>
          <p className="text-sm text-sc-text-muted">{t('userManagement.subtitle')}</p>
        </div>
        <Button
          variant="primary"
          leadingIcon={<UserPlus size={14} aria-hidden />}
          onClick={openDialog}
        >
          {t('userManagement.addUser')}
        </Button>
      </header>

      <UserFilters value={filters} workspaces={workspacesQuery.data} onChange={setFilters} />

      {usersQuery.isPending ? (
        <p className="flex items-center gap-2 text-sm text-sc-text-muted">
          <Loader2 className="animate-spin" size={14} aria-hidden />
          {t('userManagement.loading')}
        </p>
      ) : null}

      {usersQuery.isError ? (
        <p
          role="alert"
          className="rounded-md border border-sc-danger/40 bg-sc-danger/10 px-3 py-2 text-sm text-sc-danger"
        >
          {t('userManagement.loadError')}
        </p>
      ) : null}

      {usersQuery.data ? (
        <section aria-label={t('userManagement.listAria')} className="flex flex-col gap-3">
          <UserTable users={usersQuery.data.items} />
          <p className="text-xs text-sc-text-muted">
            {t('userManagement.total', { count: usersQuery.data.total })}
          </p>
        </section>
      ) : null}

      {dialogOpen ? (
        <AddUserDialog
          isSubmitting={createMutation.isPending}
          error={createMutation.error}
          onClose={closeDialog}
          onSubmit={handleCreate}
        />
      ) : null}
    </div>
  );
}
