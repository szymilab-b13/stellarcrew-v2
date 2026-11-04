import { Moon, Sun } from 'lucide-react';

import { useTheme } from './useTheme';

export function ThemeToggle() {
  const { theme, toggle } = useTheme();
  const isDark = theme === 'dark';
  const Icon = isDark ? Sun : Moon;
  const label = isDark ? 'Switch to light theme' : 'Switch to dark theme';

  return (
    <button
      type="button"
      onClick={toggle}
      aria-label={label}
      title={label}
      className="inline-flex items-center gap-2 rounded-md border border-sc-border bg-sc-bg-card px-3 py-2 text-sm text-sc-text transition-colors hover:bg-sc-bg-secondary"
    >
      <Icon size={16} aria-hidden />
      <span>{isDark ? 'Light' : 'Dark'}</span>
    </button>
  );
}
