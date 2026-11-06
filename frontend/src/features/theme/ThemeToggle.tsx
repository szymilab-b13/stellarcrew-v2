import { Moon, Sun } from 'lucide-react';
import { useTranslation } from 'react-i18next';

import { useTheme } from './useTheme';

export function ThemeToggle() {
  const { theme, toggle } = useTheme();
  const { t } = useTranslation();
  const isDark = theme === 'dark';
  const Icon = isDark ? Sun : Moon;
  const label = t('topbar.switchTheme');

  return (
    <button
      type="button"
      onClick={toggle}
      aria-label={label}
      title={label}
      className="inline-flex items-center gap-2 rounded-md border border-sc-border bg-sc-bg-card px-3 py-1.5 text-xs font-semibold text-sc-text transition-colors hover:bg-sc-bg-secondary"
    >
      <Icon size={14} aria-hidden />
      <span className="hidden sm:inline">
        {isDark ? t('topbar.themeLight') : t('topbar.themeDark')}
      </span>
    </button>
  );
}
