import { Check } from 'lucide-react';

import type { InstallStepDescriptor } from '../../types';

interface StepRailProps {
  steps: readonly InstallStepDescriptor[];
  currentIndex: number;
  onJumpBack(targetIndex: number): void;
}

export function StepRail({ steps, currentIndex, onJumpBack }: StepRailProps) {
  const progressPercent =
    steps.length <= 1 ? 100 : Math.min(100, Math.max(0, (currentIndex / (steps.length - 1)) * 100));

  return (
    <nav
      aria-label="Installation steps"
      className="relative flex flex-row items-center gap-0 lg:flex-col lg:items-stretch lg:gap-0"
    >
      {/* vertical rail (lg): background track */}
      <span
        aria-hidden
        className="pointer-events-none absolute top-6 bottom-6 left-[23px] hidden w-0.5 rounded-full bg-sc-border lg:block"
      />
      {/* vertical rail (lg): progress fill, primary opacity 0.8 */}
      <span
        aria-hidden
        className="pointer-events-none absolute left-[23px] top-6 hidden w-0.5 rounded-full bg-sc-primary-muted lg:block"
        style={{ height: `calc((100% - 48px) * ${progressPercent / 100})` }}
      />

      {steps.map((step, index) => {
        const isActive = index === currentIndex;
        const isDone = index < currentIndex;
        const isUpcoming = index > currentIndex;
        const isClickable = isDone;

        return (
          <button
            type="button"
            key={step.key}
            onClick={() => (isClickable ? onJumpBack(index) : undefined)}
            disabled={!isClickable}
            aria-current={isActive ? 'step' : undefined}
            aria-label={`Step ${index + 1}: ${step.label}`}
            className={`relative z-10 flex items-center gap-4 text-left transition-opacity lg:my-6 ${
              isClickable ? 'cursor-pointer' : 'cursor-default'
            } ${isUpcoming ? 'opacity-70' : ''}`}
          >
            <span
              className={`inline-flex h-12 w-12 flex-none items-center justify-center rounded-full border text-base font-semibold transition-colors ${
                isActive
                  ? 'border-sc-primary bg-sc-primary text-sc-primary-foreground shadow-[0_0_0_4px_rgba(89,213,136,0.18)]'
                  : isDone
                    ? 'border-sc-primary-muted bg-sc-primary-muted text-sc-primary-foreground'
                    : 'border-sc-border bg-sc-bg-card text-sc-text-muted'
              }`}
            >
              {isDone ? <Check size={20} strokeWidth={2.5} aria-hidden /> : index + 1}
            </span>
            <span className="hidden flex-col gap-0.5 lg:flex">
              <span
                className={`text-[11px] font-semibold uppercase tracking-[0.18em] ${isActive ? 'text-sc-text-muted' : 'text-sc-text-muted'}`}
              >
                Step {String(index + 1).padStart(2, '0')}
              </span>
              <span
                className={`text-base font-semibold tracking-tight ${isActive ? 'text-sc-text' : 'text-sc-text-muted'}`}
              >
                {step.short}
              </span>
            </span>
          </button>
        );
      })}
    </nav>
  );
}
