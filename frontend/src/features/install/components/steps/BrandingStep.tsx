import { Palette } from 'lucide-react';
import { Trans, useTranslation } from 'react-i18next';

import { PlaceholderStep } from './PlaceholderStep';

export function BrandingStep() {
  const { t } = useTranslation();
  return (
    <PlaceholderStep
      icon={<Palette size={18} aria-hidden />}
      heading={t('steps.branding.sectionHeading')}
      description={t('steps.branding.sectionSubheading')}
    >
      <p>
        <Trans i18nKey="steps.branding.placeholderBody" components={{ strong: <strong /> }} />
      </p>
    </PlaceholderStep>
  );
}
