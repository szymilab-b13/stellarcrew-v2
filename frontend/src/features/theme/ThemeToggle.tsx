import { Moon, Sun } from 'lucide-react';
import { useTranslation } from 'react-i18next';

import { useTheme } from './useTheme';

export function ThemeToggle() {
  const { theme, toggle } = useTheme();
  const { t } = useTranslation();
  const isDark = theme === 'dark';
  const Icon = isDark ? Sun : Moon;
  const label = isDark ? t('topbar.themeLight') : t('topbar.themeDark');

  return (
    <button
      type="button"
      onClick={toggle}
      aria-label={label}
      title={label}
      className="inline-flex h-8 w-8 items-center justify-center rounded-lg text-sc-text-muted transition-colors hover:bg-sc-bg-card hover:text-sc-text"
    >
      <Icon size={16} strokeWidth={1.75} aria-hidden />
    </button>
  );
}
