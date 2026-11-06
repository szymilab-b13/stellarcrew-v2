import { ChevronsUpDown } from 'lucide-react';
import { useTranslation } from 'react-i18next';

interface WorkspaceSwitcherProps {
  collapsed: boolean;
}

export function WorkspaceSwitcher({ collapsed }: WorkspaceSwitcherProps) {
  const { t } = useTranslation();

  if (collapsed) {
    return (
      <div
        className="mx-auto mt-2 flex h-8 w-8 items-center justify-center rounded-md border border-sc-border bg-sc-bg-card text-xs font-semibold text-sc-text"
        title={t('workspaceSwitcher.default')}
      >
        D
      </div>
    );
  }

  return (
    <button
      type="button"
      disabled
      className="mt-2 flex w-full items-center justify-between gap-2 rounded-md border border-sc-border bg-sc-bg-card px-3 py-2 text-left text-sm font-medium text-sc-text transition-colors disabled:cursor-default"
      title={t('workspaceSwitcher.placeholderHint')}
    >
      <span className="flex min-w-0 items-center gap-2">
        <span className="inline-flex h-6 w-6 flex-none items-center justify-center rounded-md bg-sc-bg-secondary text-xs font-semibold text-sc-text-muted">
          D
        </span>
        <span className="flex min-w-0 flex-col">
          <span className="truncate text-xs text-sc-text-muted">
            {t('workspaceSwitcher.label')}
          </span>
          <span className="truncate text-sm font-semibold text-sc-text">
            {t('workspaceSwitcher.default')}
          </span>
        </span>
      </span>
      <ChevronsUpDown size={14} className="flex-none text-sc-text-muted" aria-hidden />
    </button>
  );
}
