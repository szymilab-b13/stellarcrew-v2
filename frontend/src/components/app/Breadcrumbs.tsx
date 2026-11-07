import { ChevronRight, Home } from 'lucide-react';
import { useTranslation } from 'react-i18next';
import { NavLink } from 'react-router-dom';

import { useBreadcrumbs } from './useBreadcrumbs';

export function Breadcrumbs() {
  const { t } = useTranslation();
  const segments = useBreadcrumbs();

  return (
    <nav
      aria-label={t('breadcrumbs.aria')}
      className="flex min-w-0 items-center gap-1.5 text-sm text-sc-text-muted"
    >
      <NavLink
        to="/dashboard"
        aria-label={t('breadcrumbs.home')}
        className="inline-flex h-7 w-7 flex-none items-center justify-center rounded-md transition-colors hover:bg-sc-bg-secondary hover:text-sc-text"
      >
        <Home size={14} aria-hidden />
      </NavLink>
      {segments.map((segment, index) => {
        const isLast = index === segments.length - 1;
        return (
          <span key={`${segment.label}-${index}`} className="flex min-w-0 items-center gap-1.5">
            <ChevronRight size={12} aria-hidden className="flex-none text-sc-text-muted/60" />
            {isLast || !segment.to ? (
              <span
                aria-current={isLast ? 'page' : undefined}
                className={`truncate ${isLast ? 'font-medium text-sc-text' : ''}`}
              >
                {segment.label}
              </span>
            ) : (
              <NavLink to={segment.to} className="truncate transition-colors hover:text-sc-text">
                {segment.label}
              </NavLink>
            )}
          </span>
        );
      })}
    </nav>
  );
}
