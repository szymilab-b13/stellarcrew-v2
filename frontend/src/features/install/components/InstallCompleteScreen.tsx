import { CheckCircle2 } from 'lucide-react';

interface InstallCompleteScreenProps {
  displayName: string;
}

export function InstallCompleteScreen({ displayName }: InstallCompleteScreenProps) {
  return (
    <div className="flex min-h-screen items-center justify-center bg-sc-bg px-6 py-12 text-center">
      <section
        aria-labelledby="install-complete-heading"
        className="flex max-w-md flex-col items-center gap-4 rounded-lg border border-sc-border bg-sc-bg-card p-8 shadow-sm"
      >
        <span className="inline-flex h-16 w-16 items-center justify-center rounded-full bg-sc-bg-secondary text-sc-text">
          <CheckCircle2 size={36} strokeWidth={2.2} aria-hidden />
        </span>
        <h2 id="install-complete-heading" className="text-2xl font-semibold tracking-tight">
          You are all set{displayName ? `, ${displayName}` : ''}.
        </h2>
        <p className="text-sm text-sc-text-muted">
          Your StellarCrew instance is installed. Head to the app to sign in and meet your crew.
        </p>
        <a
          href="/"
          className="mt-2 inline-flex items-center gap-2 rounded-md bg-sc-primary px-4 py-2 text-sm font-semibold text-sc-primary-foreground shadow-sm transition-opacity hover:opacity-90"
        >
          Open StellarCrew
        </a>
      </section>
    </div>
  );
}
