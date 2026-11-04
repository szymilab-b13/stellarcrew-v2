import { ThemeToggle } from './features/theme';

export function App() {
  return (
    <main className="min-h-screen bg-sc-bg px-6 py-12 text-sc-text">
      <div className="mx-auto flex max-w-3xl flex-col gap-6">
        <header className="flex items-start justify-between gap-6">
          <div>
            <h1 className="text-3xl font-semibold tracking-tight">StellarCrew</h1>
            <p className="mt-2 text-sm text-sc-text-muted">
              Frontend baseline wired up. Tokens, Inter font and light-first theme toggle are live.
            </p>
          </div>
          <ThemeToggle />
        </header>

        <section className="rounded-lg border border-sc-border bg-sc-bg-card p-5 shadow-sm">
          <h2 className="text-lg font-medium">Design tokens</h2>
          <p className="mt-2 text-sm text-sc-text-muted">
            Backgrounds, borders and text colours are driven by <code className="font-mono">--color-sc-*</code> CSS
            variables, remapped per <code className="font-mono">data-theme</code> on <code className="font-mono">&lt;html&gt;</code>.
          </p>
        </section>

        <section className="rounded-lg border border-sc-border bg-sc-bg-secondary p-5">
          <h2 className="text-lg font-medium">Reference</h2>
          <p className="mt-2 text-sm text-sc-text-muted">
            See <code className="font-mono">knowledge/adr/ADR-FE-001-frontend-baseline.md</code>.
          </p>
        </section>
      </div>
    </main>
  );
}
