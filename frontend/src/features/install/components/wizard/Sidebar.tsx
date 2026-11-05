import { Shield } from 'lucide-react';

import { Logo } from '@/features/branding/Logo';

interface SidebarProps {
  currentStepIndex: number;
  totalSteps: number;
}

export function Sidebar({ currentStepIndex, totalSteps }: SidebarProps) {
  const progress = Math.round(((currentStepIndex + 1) / totalSteps) * 100);

  return (
    <aside className="hidden flex-col justify-between border-r border-sc-border bg-sc-bg-secondary p-8 lg:flex lg:w-80">
      <div>
        <div className="flex items-center gap-2 text-lg font-semibold tracking-tight">
          <Logo size={36} />
          <span>StellarCrew</span>
        </div>

        <div className="mt-12 flex flex-col gap-3">
          <span className="text-xs font-semibold uppercase tracking-widest text-sc-text-muted">
            Setup guide
          </span>
          <h1 className="text-2xl font-semibold leading-tight tracking-tight text-sc-text">
            Your installation. <br />
            <span className="text-sc-text-muted">Simpler than you think.</span>
          </h1>
          <p className="mt-2 text-sm leading-relaxed text-sc-text-muted">
            Walk through a few short steps to finish setting up your StellarCrew instance.
          </p>
        </div>
      </div>

      <div className="mt-8">
        <div className="flex items-center justify-between text-xs text-sc-text-muted">
          <span>Setup progress</span>
          <strong className="text-sc-text">{progress}%</strong>
        </div>
        <div
          role="progressbar"
          aria-valuemin={0}
          aria-valuemax={100}
          aria-valuenow={progress}
          className="mt-3 h-1.5 overflow-hidden rounded-full bg-sc-border"
        >
          <span
            className="block h-full rounded-full bg-sc-primary transition-[width] duration-300"
            style={{ width: `${progress}%` }}
          />
        </div>
        <p className="mt-4 flex items-center gap-2 text-xs text-sc-text-muted">
          <Shield size={14} aria-hidden />
          <span>Local preview — nothing is sent outside your instance.</span>
        </p>
      </div>
    </aside>
  );
}
