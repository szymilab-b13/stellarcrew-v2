import { Check } from 'lucide-react';

import type { InstallStepDescriptor } from '../../types';

interface StepRailProps {
  steps: readonly InstallStepDescriptor[];
  currentIndex: number;
  onJumpBack(targetIndex: number): void;
}

export function StepRail({ steps, currentIndex, onJumpBack }: StepRailProps) {
  return (
    <nav aria-label="Installation steps" className="flex flex-row gap-8 lg:flex-col lg:gap-10">
      {steps.map((step, index) => {
        const isActive = index === currentIndex;
        const isDone = index < currentIndex;
        const isUpcoming = index > currentIndex;

        return (
          <button
            type="button"
            key={step.key}
            onClick={() => (isDone ? onJumpBack(index) : undefined)}
            disabled={!isDone}
            aria-current={isActive ? 'step' : undefined}
            aria-label={`Step ${index + 1}: ${step.label}`}
            className={`flex items-center gap-3 text-left transition-opacity ${isUpcoming ? 'opacity-40' : ''} ${isDone ? 'cursor-pointer hover:opacity-100' : 'cursor-default'}`}
          >
            <span
              className={`inline-flex h-9 w-9 flex-none items-center justify-center rounded-full border text-sm font-semibold ${
                isActive
                  ? 'border-sc-text bg-sc-text text-sc-bg'
                  : isDone
                    ? 'border-sc-text bg-sc-bg-card text-sc-text'
                    : 'border-sc-border bg-sc-bg-card text-sc-text-muted'
              }`}
            >
              {isDone ? <Check size={16} strokeWidth={2.5} aria-hidden /> : index + 1}
            </span>
            <span className="hidden flex-col lg:flex">
              <span className="text-xs font-semibold uppercase tracking-wider text-sc-text-muted">
                Step {String(index + 1).padStart(2, '0')}
              </span>
              <span className="text-sm font-medium text-sc-text">{step.short}</span>
            </span>
          </button>
        );
      })}
    </nav>
  );
}
