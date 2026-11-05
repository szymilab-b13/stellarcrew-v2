import { InstallWizard } from '@/features/install';
import { ThemeToggle } from '@/features/theme';

export function InstallPage() {
  return (
    <>
      <header className="flex items-start justify-between gap-6">
        <div>
          <h1 className="text-3xl font-semibold tracking-tight">Install StellarCrew</h1>
          <p className="mt-2 text-sm text-sc-text-muted">
            One-time setup for the first administrator account.
          </p>
        </div>
        <ThemeToggle />
      </header>

      <InstallWizard />
    </>
  );
}
