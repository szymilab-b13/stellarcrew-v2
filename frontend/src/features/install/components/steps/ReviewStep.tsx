import { ClipboardList } from 'lucide-react';

import type { InstallFormState } from '../../types';

interface ReviewStepProps {
  value: InstallFormState;
}

export function ReviewStep({ value }: ReviewStepProps) {
  return (
    <section className="rounded-lg border border-sc-border bg-sc-bg-card p-6 shadow-sm">
      <div className="flex items-center gap-3 border-b border-sc-border pb-5">
        <span className="inline-flex h-10 w-10 items-center justify-center rounded-md border border-sc-border bg-sc-bg-secondary text-sc-text">
          <ClipboardList size={18} aria-hidden />
        </span>
        <div>
          <h3 className="text-base font-semibold text-sc-text">Review</h3>
          <p className="text-xs text-sc-text-muted">
            Quick look before we finalise. You can jump back to any completed step on the left.
          </p>
        </div>
      </div>

      <dl className="mt-6 divide-y divide-sc-border text-sm">
        <ReviewRow label="Admin email" value={value.admin.email || '—'} />
        <ReviewRow label="Display name" value={value.admin.displayName || '—'} />
        <ReviewRow label="Password" value={'•'.repeat(Math.max(value.admin.password.length, 6))} />
        <ReviewRow
          label="Instance name"
          value={value.branding.instanceName || 'StellarCrew (default)'}
        />
        <ReviewRow label="Default locale" value={value.branding.locale} />
        <ReviewRow label="Logo" value={value.logo.file?.name ?? 'Using generic mark'} />
        <ReviewRow label="Workspace name" value={value.workspace.name || '—'} />
        <ReviewRow
          label="Agents"
          value={value.selectedAgentSlugs.length > 0 ? value.selectedAgentSlugs.join(', ') : 'None'}
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
