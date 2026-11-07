import { Search } from 'lucide-react';
import { useTranslation } from 'react-i18next';

import { Input } from '@/components/ui/Input';
import type { Workspace } from '@/features/workspaces/types';

import type { UserFiltersValue } from './userFilters.state';

interface UserFiltersProps {
  value: UserFiltersValue;
  workspaces: Workspace[] | undefined;
  onChange: (next: UserFiltersValue) => void;
}

export function UserFilters({ value, workspaces, onChange }: UserFiltersProps) {
  const { t } = useTranslation();

  return (
    <section
      aria-label={t('userManagement.filters.ariaLabel')}
      className="flex flex-col gap-3 rounded-md border border-sc-border bg-sc-bg-card p-4"
    >
      <div className="grid grid-cols-1 gap-3 md:grid-cols-4">
        <label className="flex flex-col gap-1.5 md:col-span-2">
          <span className="text-xs font-medium text-sc-text">
            {t('userManagement.filters.search')}
          </span>
          <span className="relative">
            <Search
              size={14}
              className="pointer-events-none absolute left-2.5 top-1/2 -translate-y-1/2 text-sc-text-muted"
              aria-hidden
            />
            <Input
              type="search"
              value={value.q}
              placeholder={t('userManagement.filters.searchPlaceholder')}
              className="pl-8"
              onChange={(event) => onChange({ ...value, q: event.target.value })}
            />
          </span>
        </label>

        <label className="flex flex-col gap-1.5">
          <span className="text-xs font-medium text-sc-text">
            {t('userManagement.filters.workspace')}
          </span>
          <select
            value={value.workspaceId}
            onChange={(event) => onChange({ ...value, workspaceId: event.target.value })}
            className="w-full rounded-md border border-sc-border bg-sc-bg px-3 py-2 text-sm text-sc-text focus:outline-none focus:ring-1 focus:ring-sc-primary/30"
          >
            <option value="">{t('userManagement.filters.workspaceAll')}</option>
            {workspaces?.map((workspace) => (
              <option key={workspace.id} value={workspace.id}>
                {workspace.name}
              </option>
            ))}
          </select>
        </label>

        <label className="flex flex-col gap-1.5">
          <span className="text-xs font-medium text-sc-text">
            {t('userManagement.filters.hasSuperAdmin')}
          </span>
          <select
            value={value.hasSuperAdmin}
            onChange={(event) =>
              onChange({
                ...value,
                hasSuperAdmin: event.target.value as UserFiltersValue['hasSuperAdmin'],
              })
            }
            className="w-full rounded-md border border-sc-border bg-sc-bg px-3 py-2 text-sm text-sc-text focus:outline-none focus:ring-1 focus:ring-sc-primary/30"
          >
            <option value="all">{t('userManagement.filters.hasSuperAdminAll')}</option>
            <option value="yes">{t('userManagement.filters.hasSuperAdminYes')}</option>
            <option value="no">{t('userManagement.filters.hasSuperAdminNo')}</option>
          </select>
        </label>
      </div>

      <label className="inline-flex items-center gap-2 text-sm text-sc-text">
        <input
          type="checkbox"
          className="h-4 w-4 rounded border-sc-border text-sc-primary focus:ring-sc-primary/30"
          checked={value.onlyDeactivated}
          onChange={(event) => onChange({ ...value, onlyDeactivated: event.target.checked })}
        />
        {t('userManagement.filters.onlyDeactivated')}
      </label>
    </section>
  );
}
