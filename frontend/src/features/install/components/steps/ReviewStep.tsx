import { ClipboardList } from 'lucide-react';
import { useTranslation } from 'react-i18next';

import type { InstallFormState } from '../../types';

interface ReviewStepProps {
  value: InstallFormState;
}

export function ReviewStep({ value }: ReviewStepProps) {
  const { t } = useTranslation();
  const dash = t('steps.review.defaults.dash');

  return (
    <section className="rounded-lg border border-sc-border bg-sc-bg-card p-6 shadow-sm">
      <div className="flex items-center gap-3 border-b border-sc-border pb-5">
        <span className="inline-flex h-10 w-10 items-center justify-center rounded-md border border-sc-border bg-sc-bg-secondary text-sc-text">
          <ClipboardList size={18} aria-hidden />
        </span>
        <div>
          <h3 className="text-base font-semibold text-sc-text">
            {t('steps.review.sectionHeading')}
          </h3>
          <p className="text-xs text-sc-text-muted">{t('steps.review.sectionSubheading')}</p>
        </div>
      </div>

      <dl className="mt-6 divide-y divide-sc-border text-sm">
        <ReviewRow label={t('steps.review.fields.adminEmail')} value={value.admin.email || dash} />
        <ReviewRow
          label={t('steps.review.fields.displayName')}
          value={value.admin.displayName || dash}
        />
        <ReviewRow
          label={t('steps.review.fields.password')}
          value={'•'.repeat(Math.max(value.admin.password.length, 6))}
        />
        <ReviewRow
          label={t('steps.review.fields.instanceName')}
          value={value.branding.instanceName || t('steps.review.defaults.instanceName')}
        />
        <ReviewRow label={t('steps.review.fields.locale')} value={value.branding.locale} />
        <ReviewRow
          label={t('steps.review.fields.logo')}
          value={value.logo.file?.name ?? t('steps.review.defaults.noLogo')}
        />
        <ReviewRow
          label={t('steps.review.fields.workspaceName')}
          value={value.workspace.name || dash}
        />
        <ReviewRow
          label={t('steps.review.fields.agents')}
          value={
            value.selectedAgentSlugs.length > 0
              ? value.selectedAgentSlugs.join(', ')
              : t('steps.review.defaults.noAgents')
          }
        />
      </dl>
    </section>
  );
}

function ReviewRow({ label, value }: { label: string; value: string }) {
  return (
    <div className="flex items-center justify-between gap-4 py-3">
      <dt className="text-xs uppercase tracking-wider text-sc-text-muted">{label}</dt>
      <dd className="text-sm font-medium text-sc-text">{value}</dd>
    </div>
  );
}
