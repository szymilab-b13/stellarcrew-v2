import { useTranslation } from 'react-i18next';

import type { AuditEntry } from '../types';
import { AuditRowActionsMenu } from './AuditRowActionsMenu';
import { isReversibleAction } from './auditFilters.state';

interface AuditTableProps {
  entries: AuditEntry[];
  onDetailsClick: (entry: AuditEntry) => void;
  onUndoClick: (entry: AuditEntry) => void;
}

export function AuditTable({ entries, onDetailsClick, onUndoClick }: AuditTableProps) {
  const { t, i18n } = useTranslation();

  const dateFormatter = new Intl.DateTimeFormat(i18n.resolvedLanguage ?? 'en', {
    dateStyle: 'medium',
    timeStyle: 'short',
  });

  return (
    <div className="overflow-x-auto rounded-md border border-sc-border bg-sc-bg-card">
      <table
        className="min-w-full divide-y divide-sc-border text-left text-sm"
        aria-label={t('audit.listAria')}
      >
        <thead className="bg-sc-bg-secondary text-xs uppercase tracking-wider text-sc-text-muted">
          <tr>
            <th scope="col" className="px-4 py-3 font-semibold">
              {t('audit.columns.actor')}
            </th>
            <th scope="col" className="px-4 py-3 font-semibold">
              {t('audit.columns.occurredAt')}
            </th>
            <th scope="col" className="px-4 py-3 font-semibold">
              {t('audit.columns.action')}
            </th>
            <th scope="col" className="px-4 py-3 font-semibold">
              {t('audit.columns.resource')}
            </th>
            <th scope="col" className="px-4 py-3 font-semibold">
              {t('audit.columns.status')}
            </th>
            <th scope="col" className="px-4 py-3 text-right font-semibold">
              {t('audit.columns.actions')}
            </th>
          </tr>
        </thead>
        <tbody className="divide-y divide-sc-border">
          {entries.length === 0 ? (
            <tr>
              <td colSpan={6} className="px-4 py-6 text-center text-sm text-sc-text-muted">
                {t('audit.table.empty')}
              </td>
            </tr>
          ) : null}
          {entries.map((entry) => {
            const actionLabel = t(`audit.actions.${entry.action}`, { defaultValue: entry.action });
            const canUndo = isReversibleAction(entry.action) && entry.undoneAt === null;
            return (
              <tr key={entry.id} className="text-sc-text">
                <td className="px-4 py-3 font-medium">{entry.actorEmail ?? entry.actorUserId}</td>
                <td className="px-4 py-3 text-sc-text-muted tabular-nums">
                  {dateFormatter.format(new Date(entry.occurredAt))}
                </td>
                <td className="px-4 py-3">{actionLabel}</td>
                <td className="px-4 py-3 text-sc-text-muted">
                  {formatResource(entry.resourceType, entry.resourceId)}
                </td>
                <td className="px-4 py-3">
                  {entry.undoneAt ? (
                    <span className="inline-flex items-center rounded-full bg-sc-text-muted/10 px-2 py-0.5 text-xs font-semibold text-sc-text-muted">
                      {t('audit.table.badgeUndone')}
                    </span>
                  ) : (
                    <span className="inline-flex items-center rounded-full bg-emerald-500/10 px-2 py-0.5 text-xs font-semibold text-emerald-600">
                      {t('audit.table.badgeActive')}
                    </span>
                  )}
                </td>
                <td className="px-4 py-3 text-right">
                  <AuditRowActionsMenu
                    entry={entry}
                    canUndo={canUndo}
                    onDetailsClick={onDetailsClick}
                    onUndoClick={onUndoClick}
                  />
                </td>
              </tr>
            );
          })}
        </tbody>
      </table>
    </div>
  );
}

function formatResource(type: string | null, id: string | null): string {
  if (type === null && id === null) return '—';
  if (type === null) return id ?? '—';
  if (id === null) return type;
  return `${type} · ${shortId(id)}`;
}

function shortId(id: string): string {
  return id.length > 10 ? `${id.slice(0, 8)}…` : id;
}
