import { useTranslation } from 'react-i18next';

import { SUPPORTED_LANGUAGES, type SupportedLanguage } from '@/i18n/i18n';

const LABELS: Record<SupportedLanguage, string> = {
  en: 'EN',
  pl: 'PL',
};

export function LanguageSwitcher() {
  const { i18n, t } = useTranslation();
  const current = (i18n.resolvedLanguage ?? 'en') as SupportedLanguage;

  function pick(next: SupportedLanguage) {
    if (next === current) return;
    void i18n.changeLanguage(next);
  }

  return (
    <div
      role="group"
      aria-label={t('topbar.switchLang')}
      className="inline-flex items-center gap-0.5 rounded-md border border-sc-border bg-sc-bg-card p-0.5"
    >
      {SUPPORTED_LANGUAGES.map((lng) => {
        const isActive = lng === current;
        return (
          <button
            key={lng}
            type="button"
            onClick={() => pick(lng)}
            aria-pressed={isActive}
            className={`rounded-sm px-2 py-1 text-[11px] font-semibold uppercase tracking-wider transition-colors ${
              isActive
                ? 'bg-sc-primary text-sc-primary-foreground'
                : 'text-sc-text-muted hover:text-sc-text'
            }`}
          >
            {LABELS[lng]}
          </button>
        );
      })}
    </div>
  );
}
