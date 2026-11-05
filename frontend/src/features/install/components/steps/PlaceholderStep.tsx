import type { ReactNode } from 'react';

interface PlaceholderStepProps {
  icon: ReactNode;
  heading: string;
  description: string;
  children?: ReactNode;
}

export function PlaceholderStep({ icon, heading, description, children }: PlaceholderStepProps) {
  return (
    <section className="rounded-lg border border-dashed border-sc-border bg-sc-bg-card p-6 shadow-sm">
      <div className="flex items-start gap-3 border-b border-sc-border pb-5">
        <span className="inline-flex h-10 w-10 items-center justify-center rounded-md border border-sc-border bg-sc-bg-secondary text-sc-text-muted">
          {icon}
        </span>
        <div>
          <h3 className="text-base font-semibold text-sc-text">{heading}</h3>
          <p className="text-xs text-sc-text-muted">{description}</p>
        </div>
      </div>
      <div className="mt-6 text-sm text-sc-text-muted">
        {children ?? (
          <p>This step is a placeholder — fields will light up in an upcoming release.</p>
        )}
      </div>
    </section>
  );
}
