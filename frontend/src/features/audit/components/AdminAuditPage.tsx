import { Loader2 } from 'lucide-react';
import { useMemo, useState } from 'react';
import { useTranslation } from 'react-i18next';
import { useSearchParams } from 'react-router-dom';

import { usePageBreadcrumbs } from '@/components/app/useBreadcrumbs';

import { useAuditEntries } from '../hooks/useAuditEntries';
import { useUndoEntry } from '../hooks/useUndoEntry';
import type { AuditEntry } from '../types';
import { AuditFilters } from './AuditFilters';
import { AuditTable } from './AuditTable';
import { UndoConfirmDialog } from './UndoConfirmDialog';
import {
  filtersFromSearchParams,
  isReversibleAction,
  toApiFilters,
  type AuditFiltersValue,
} from './auditFilters.state';

export function AdminAuditPage() {
  const { t } = useTranslation();
  const [searchParams] = useSearchParams();
  const initialFilters = useMemo(() => filtersFromSearchParams(searchParams), [searchParams]);
  const [filters, setFilters] = useState<AuditFiltersValue>(initialFilters);
  const [entryToUndo, setEntryToUndo] = useState<AuditEntry | null>(null);

  const breadcrumbs = useMemo(() => [{ label: t('audit.breadcrumb') }], [t]);
  usePageBreadcrumbs(breadcrumbs);

  const apiFilters = useMemo(() => toApiFilters(filters), [filters]);
  const entriesQuery = useAuditEntries(apiFilters);
  const undoMutation = useUndoEntry();

  const visibleEntries = useMemo(() => {
    const items = entriesQuery.data?.items ?? [];
    return filters.onlyReversible
      ? items.filter((entry) => isReversibleAction(entry.action))
      : items;
  }, [entriesQuery.data, filters.onlyReversible]);

  function openUndoDialog(entry: AuditEntry) {
    undoMutation.reset();
    setEntryToUndo(entry);
  }

  function closeUndoDialog() {
    setEntryToUndo(null);
  }

  function confirmUndo() {
    if (entryToUndo === null) return;
    undoMutation.mutate(entryToUndo.id, { onSuccess: closeUndoDialog });
  }

  return (
    <div className="mx-auto flex w-full max-w-6xl flex-col gap-6 px-6 py-8">
      <header className="flex flex-col gap-1">
        <h1 className="text-2xl font-semibold tracking-tight text-sc-text">{t('audit.title')}</h1>
        <p className="text-sm text-sc-text-muted">{t('audit.subtitle')}</p>
      </header>

      <AuditFilters value={filters} onChange={setFilters} />

      {entriesQuery.isPending ? (
        <p className="inline-flex items-center gap-2 text-sm text-sc-text-muted">
          <Loader2 size={14} className="animate-spin" aria-hidden />
          {t('audit.loading')}
        </p>
      ) : entriesQuery.isError ? (
        <p role="alert" className="text-sm text-sc-danger">
          {t('audit.loadError')}
        </p>
      ) : (
        <>
          <p className="text-xs text-sc-text-muted">
            {t('audit.total', { count: entriesQuery.data.total })}
          </p>
          <AuditTable entries={visibleEntries} onUndoClick={openUndoDialog} />
        </>
      )}

      {entryToUndo ? (
        <UndoConfirmDialog
          entry={entryToUndo}
          isSubmitting={undoMutation.isPending}
          error={undoMutation.error}
          onClose={closeUndoDialog}
          onConfirm={confirmUndo}
        />
      ) : null}
    </div>
  );
}
