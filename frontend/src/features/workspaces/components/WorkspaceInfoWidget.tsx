import { useTranslation } from 'react-i18next';
import type { LucideIcon } from 'lucide-react';
import { Calendar, Copy, Hash, Lock, Users } from 'lucide-react';

type MetadataField = 'id' | 'created' | 'members' | 'visibility';

const METADATA_FIELDS: MetadataField[] = ['id', 'created', 'members', 'visibility'];

const FIELD_ICONS: Record<MetadataField, LucideIcon> = {
  id: Hash,
  created: Calendar,
  members: Users,
  visibility: Lock,
};

export function WorkspaceInfoWidget() {
  const { t } = useTranslation();

  return (
    <article className="relative flex min-h-[9rem] flex-col gap-3 rounded-lg border border-dashed border-sc-border bg-sc-bg-card/40 p-4">
      <span
        aria-label={t('workspaceOverview.prototypeBadge')}
        className="absolute right-3 top-3 rounded-full bg-sc-bg-muted px-2 py-0.5 text-[10px] font-semibold uppercase tracking-wider text-sc-text-muted"
      >
        {t('workspaceOverview.prototypeBadge')}
      </span>
      <h2 className="pr-16 text-sm font-semibold text-sc-text">
        {t('workspaceOverview.slots.workspaceInfo.title')}
      </h2>
      <div
        className="flex items-center gap-3 opacity-60"
        aria-label={t('workspaceOverview.widgets.workspaceInfo.previewLabel')}
      >
        <div
          aria-hidden="true"
          className="flex h-10 w-10 shrink-0 items-center justify-center rounded-full bg-sc-bg-muted text-sm font-semibold text-sc-text-muted"
        >
          {t('workspaceOverview.widgets.workspaceInfo.avatarInitials')}
        </div>
        <div className="flex min-w-0 flex-col">
          <span className="truncate text-sm font-medium text-sc-text">
            {t('workspaceOverview.widgets.workspaceInfo.sampleName')}
          </span>
          <button
            type="button"
            disabled
            aria-label={t('workspaceOverview.widgets.workspaceInfo.copyIdAria')}
            className="inline-flex w-fit items-center gap-1 text-[10px] font-semibold uppercase tracking-wider text-sc-text-muted hover:text-sc-text disabled:cursor-not-allowed disabled:hover:text-sc-text-muted"
          >
            <Copy className="h-3 w-3" aria-hidden="true" />
            {t('workspaceOverview.widgets.workspaceInfo.copyId')}
          </button>
        </div>
      </div>
      <ul
        className="flex flex-col gap-1.5 opacity-60"
        aria-label={t('workspaceOverview.widgets.workspaceInfo.metadataLabel')}
      >
        {METADATA_FIELDS.map((field) => {
          const Icon = FIELD_ICONS[field];
          return (
            <li key={field} className="flex items-center gap-2 text-xs text-sc-text-muted">
              <Icon className="h-3.5 w-3.5 shrink-0 text-sc-text-muted" aria-hidden="true" />
              <span className="text-sc-text-muted/80">
                {t(`workspaceOverview.widgets.workspaceInfo.fields.${field}.label`)}
              </span>
              <span className="ml-auto truncate font-mono text-sc-text-muted">
                {t(`workspaceOverview.widgets.workspaceInfo.fields.${field}.value`)}
              </span>
            </li>
          );
        })}
      </ul>
      <p className="mt-auto text-xs text-sc-text-muted">
        {t('workspaceOverview.widgets.workspaceInfo.comingAfter')}
      </p>
    </article>
  );
}
