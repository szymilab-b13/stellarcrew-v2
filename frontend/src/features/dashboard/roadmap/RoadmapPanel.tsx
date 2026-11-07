import { useTranslation } from 'react-i18next';

import { ROADMAP } from './data';
import { MilestoneCard } from './MilestoneCard';

export function RoadmapPanel() {
  const { t } = useTranslation();

  return (
    <section
      aria-label={t('roadmap.title')}
      className="rounded-lg border border-sc-border bg-sc-bg-secondary p-5"
    >
      <header className="mb-4 flex items-baseline justify-between gap-2">
        <h2 className="text-lg font-medium text-sc-text">{t('roadmap.title')}</h2>
        <p className="text-xs text-sc-text-muted">{t('roadmap.subtitle')}</p>
      </header>
      <div className="flex flex-col gap-2">
        {ROADMAP.map((milestone) => (
          <MilestoneCard key={milestone.key} milestone={milestone} />
        ))}
      </div>
    </section>
  );
}
