import { Palette } from 'lucide-react';

import { PlaceholderStep } from './PlaceholderStep';

export function BrandingStep() {
  return (
    <PlaceholderStep
      icon={<Palette size={18} aria-hidden />}
      heading="Branding"
      description="Instance name, default locale and colour accents."
    >
      <p>
        Fields for <strong>instance name</strong> and <strong>default locale</strong> will land
        here. For now you can skip ahead — defaults will be applied.
      </p>
    </PlaceholderStep>
  );
}
