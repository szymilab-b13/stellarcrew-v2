import { useTranslation } from 'react-i18next';

import { Input } from '@/components/ui/Input';
import { useVisibleUsers } from '@/features/user-management/api/useVisibleUsers';

import { AUDIT_ACTIONS, type AuditFiltersValue } from './auditFilters.state';

interface AuditFiltersProps {
  value: AuditFiltersValue;
  onChange: (next: AuditFiltersValue) => void;
}

export function AuditFilters({ value, onChange }: AuditFiltersProps) {
  const { t } = useTranslation();
  const usersQuery = useVisibleUsers(null);
  const users = usersQuery.data ?? [];

  return (
    <section
      aria-label={t('audit.filters.ariaLabel')}
      className="flex flex-col gap-3 rounded-md border border-sc-border bg-sc-bg-card p-4"
    >
      <div className="grid grid-cols-1 gap-3 md:grid-cols-4">
        <label className="flex flex-col gap-1.5">
          <span className="text-xs font-medium text-sc-text">{t('audit.filters.actor')}</span>
          <select
            value={value.actorUserId}
            onChange={(event) => onChange({ ...value, actorUserId: event.target.value })}
            className="w-full rounded-md border border-sc-border bg-sc-bg px-3 py-2 text-sm text-sc-text focus:outline-none focus:ring-1 focus:ring-sc-primary/30"
          >
            <option value="">{t('audit.filters.actorAll')}</option>
            {users.map((user) => (
              <option key={user.id} value={user.id}>
                {user.email}
              </option>
            ))}
          </select>
        </label>

        <label className="flex flex-col gap-1.5">
          <span className="text-xs font-medium text-sc-text">{t('audit.filters.action')}</span>
          <select
            value={value.action}
            onChange={(event) => onChange({ ...value, action: event.target.value })}
            className="w-full rounded-md border border-sc-border bg-sc-bg px-3 py-2 text-sm text-sc-text focus:outline-none focus:ring-1 focus:ring-sc-primary/30"
          >
            <option value="">{t('audit.filters.actionAll')}</option>
            {AUDIT_ACTIONS.map((group) => (
              <optgroup key={group.group} label={t(`audit.filters.actionGroups.${group.group}`)}>
                {group.actions.map((action) => (
                  <option key={action} value={action}>
                    {t(`audit.actions.${action}`, { defaultValue: action })}
                  </option>
                ))}
              </optgroup>
            ))}
          </select>
        </label>

        <label className="flex flex-col gap-1.5">
          <span className="text-xs font-medium text-sc-text">
            {t('audit.filters.resourceType')}
          </span>
          <Input
            type="text"
            value={value.resourceType}
            placeholder={t('audit.filters.resourceTypePlaceholder')}
            onChange={(event) => onChange({ ...value, resourceType: event.target.value })}
          />
        </label>

        <label className="flex flex-col gap-1.5">
          <span className="text-xs font-medium text-sc-text">{t('audit.filters.resourceId')}</span>
          <Input
            type="text"
            value={value.resourceId}
            placeholder={t('audit.filters.resourceIdPlaceholder')}
            onChange={(event) => onChange({ ...value, resourceId: event.target.value })}
          />
        </label>

        <label className="flex flex-col gap-1.5">
          <span className="text-xs font-medium text-sc-text">{t('audit.filters.from')}</span>
          <Input
            type="datetime-local"
            value={value.from}
            onChange={(event) => onChange({ ...value, from: event.target.value })}
          />
        </label>

        <label className="flex flex-col gap-1.5">
          <span className="text-xs font-medium text-sc-text">{t('audit.filters.to')}</span>
          <Input
            type="datetime-local"
            value={value.to}
            onChange={(event) => onChange({ ...value, to: event.target.value })}
          />
        </label>
      </div>

      <div className="flex flex-wrap gap-4">
        <label className="inline-flex items-center gap-2 text-sm text-sc-text">
          <input
            type="checkbox"
            className="h-4 w-4 rounded border-sc-border text-sc-primary focus:ring-sc-primary/30"
            checked={value.onlyReversible}
            onChange={(event) => onChange({ ...value, onlyReversible: event.target.checked })}
          />
          {t('audit.filters.onlyReversible')}
        </label>

        <label className="inline-flex items-center gap-2 text-sm text-sc-text">
          <input
            type="checkbox"
            className="h-4 w-4 rounded border-sc-border text-sc-primary focus:ring-sc-primary/30"
            checked={value.showUndone}
            onChange={(event) => onChange({ ...value, showUndone: event.target.checked })}
          />
          {t('audit.filters.showUndone')}
        </label>
      </div>
    </section>
  );
}
