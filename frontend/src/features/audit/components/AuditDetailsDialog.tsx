import { FileText, X } from 'lucide-react';
import { useTranslation } from 'react-i18next';

import { Button } from '@/components/ui/Button';

import type { AuditEntry } from '../types';

interface AuditDetailsDialogProps {
  entry: AuditEntry;
  onClose: () => void;
}

export function AuditDetailsDialog({ entry, onClose }: AuditDetailsDialogProps) {
  const { t, i18n } = useTranslation();
  const actionLabel = t(`audit.actions.${entry.action}`, { defaultValue: entry.action });
  const dateFormatter = new Intl.DateTimeFormat(i18n.resolvedLanguage ?? 'en', {
    dateStyle: 'medium',
    timeStyle: 'long',
  });

  return (
    <div
      role="dialog"
      aria-modal="true"
      aria-labelledby="audit-details-dialog-title"
      className="fixed inset-0 z-50 flex items-center justify-center bg-black/50 p-4"
    >
      <div className="flex w-full max-w-3xl flex-col gap-5 rounded-xl border border-sc-border bg-sc-bg-card p-7 shadow-xl">
        <header className="flex items-start justify-between gap-3">
          <div className="flex items-center gap-3">
            <span
              aria-hidden
              className="inline-flex h-10 w-10 flex-none items-center justify-center rounded-full bg-sky-500/10 text-sky-600"
            >
              <FileText size={20} />
            </span>
            <h2 id="audit-details-dialog-title" className="text-xl font-semibold text-sc-text">
              {t('audit.detailsDialog.title')}
            </h2>
          </div>
          <button
            type="button"
            onClick={onClose}
            aria-label={t('common.close')}
            className="inline-flex h-8 w-8 flex-none items-center justify-center rounded-md text-sc-text-muted transition-colors hover:bg-sc-bg hover:text-sc-text"
          >
            <X size={18} aria-hidden />
          </button>
        </header>

        <dl className="grid grid-cols-1 gap-3 text-sm sm:grid-cols-2">
          <DetailRow label={t('audit.detailsDialog.action')} value={actionLabel} />
          <DetailRow
            label={t('audit.detailsDialog.occurredAt')}
            value={dateFormatter.format(new Date(entry.occurredAt))}
          />
          <DetailRow
            label={t('audit.detailsDialog.actor')}
            value={entry.actorEmail ?? entry.actorUserId}
          />
          <DetailRow
            label={t('audit.detailsDialog.resource')}
            value={formatResource(entry.resourceType, entry.resourceId)}
          />
          <DetailRow label={t('audit.detailsDialog.entryId')} value={entry.id} mono />
          {entry.undoneAt ? (
            <DetailRow
              label={t('audit.detailsDialog.undoneAt')}
              value={`${dateFormatter.format(new Date(entry.undoneAt))}${
                entry.undoneByEmail ? ` · ${entry.undoneByEmail}` : ''
              }`}
            />
          ) : null}
        </dl>

        <JsonSection
          label={t('audit.detailsDialog.beforeState')}
          data={entry.beforeState}
          empty={t('audit.detailsDialog.empty')}
        />
        <JsonSection
          label={t('audit.detailsDialog.afterState')}
          data={entry.afterState}
          empty={t('audit.detailsDialog.empty')}
        />
        <JsonSection
          label={t('audit.detailsDialog.metadata')}
          data={Object.keys(entry.metadata).length === 0 ? null : entry.metadata}
          empty={t('audit.detailsDialog.empty')}
        />

        <div className="flex justify-end">
          <Button
            variant="secondary"
            onClick={onClose}
            className="justify-center px-4 py-3 text-base"
          >
            {t('common.close')}
          </Button>
        </div>
      </div>
    </div>
  );
}

interface DetailRowProps {
  label: string;
  value: string;
  mono?: boolean;
}

function DetailRow({ label, value, mono }: DetailRowProps) {
  return (
    <div className="flex flex-col gap-0.5">
      <dt className="text-xs uppercase tracking-wider text-sc-text-muted">{label}</dt>
      <dd className={`text-sc-text ${mono === true ? 'font-mono text-xs break-all' : ''}`}>
        {value}
      </dd>
    </div>
  );
}

interface JsonSectionProps {
  label: string;
  data: Record<string, unknown> | null;
  empty: string;
}

function JsonSection({ label, data, empty }: JsonSectionProps) {
  return (
    <section className="flex flex-col gap-1">
      <h3 className="text-xs uppercase tracking-wider text-sc-text-muted">{label}</h3>
      {data === null ? (
        <p className="rounded-md border border-sc-border bg-sc-bg px-3 py-2 text-xs italic text-sc-text-muted">
          {empty}
        </p>
      ) : (
        <pre className="max-h-64 overflow-auto rounded-md border border-sc-border bg-sc-bg px-3 py-2 font-mono text-xs leading-relaxed text-sc-text">
          {JSON.stringify(data, null, 2)}
        </pre>
      )}
    </section>
  );
}

function formatResource(type: string | null, id: string | null): string {
  if (type === null && id === null) return '—';
  if (type === null) return id ?? '—';
  if (id === null) return type;
  return `${type} · ${id}`;
}
