import type { ReactNode } from 'react';
import { useTranslation } from 'react-i18next';

import { LanguageSwitcher } from '@/features/settings/LanguageSwitcher';
import { ThemeToggle } from '@/features/theme/ThemeToggle';

import type { InstallStepDescriptor } from '../../types';

import { Sidebar } from './Sidebar';
import { StepRail } from './StepRail';
import { WizardFooter } from './WizardFooter';

interface InstallWizardLayoutProps {
  steps: readonly InstallStepDescriptor[];
  currentIndex: number;
  eyebrow: string;
  title: string;
  description: string;
  canContinue: boolean;
  isSubmitting: boolean;
  onBack(): void;
  onContinue(): void;
  onJumpBack(targetIndex: number): void;
  children: ReactNode;
}

export function InstallWizardLayout({
  steps,
  currentIndex,
  eyebrow,
  title,
  description,
  canContinue,
  isSubmitting,
  onBack,
  onContinue,
  onJumpBack,
  children,
}: InstallWizardLayoutProps) {
  const { t } = useTranslation();
  const isLastStep = currentIndex === steps.length - 1;

  return (
    <div className="flex min-h-screen bg-sc-bg text-sc-text">
      <Sidebar currentStepIndex={currentIndex} totalSteps={steps.length} />

      <main className="flex min-h-screen flex-1 flex-col">
        <div className="flex items-center justify-between border-b border-sc-border px-6 py-4 lg:px-10">
          <span className="flex items-center gap-2 text-xs font-semibold uppercase tracking-widest text-sc-text-muted">
            <span className="inline-block h-1.5 w-1.5 rounded-full bg-sc-primary" />
            {t('topbar.breadcrumb')}
          </span>
          <div className="flex items-center gap-2">
            <LanguageSwitcher />
            <ThemeToggle />
          </div>
        </div>

        <div className="mx-auto flex w-full max-w-5xl flex-1 flex-col gap-10 px-6 py-10 lg:flex-row lg:px-10">
          <div className="lg:pt-24">
            <StepRail steps={steps} currentIndex={currentIndex} onJumpBack={onJumpBack} />
          </div>

          <div className="flex min-w-0 flex-1 flex-col gap-8">
            <header className="flex flex-col gap-3">
              <span className="text-xs font-semibold uppercase tracking-widest text-sc-text-muted">
                {t('common.stepPrefix')} {String(currentIndex + 1).padStart(2, '0')} /{' '}
                {String(steps.length).padStart(2, '0')} · {eyebrow}
              </span>
              <h2 className="text-3xl font-semibold tracking-tight text-sc-text">{title}</h2>
              <p className="max-w-xl text-sm leading-relaxed text-sc-text-muted">{description}</p>
            </header>

            <div aria-live="polite" className="flex-1">
              {children}
            </div>

            <WizardFooter
              currentIndex={currentIndex}
              totalSteps={steps.length}
              isLastStep={isLastStep}
              isSubmitting={isSubmitting}
              canContinue={canContinue}
              onBack={onBack}
              onContinue={onContinue}
            />
          </div>
        </div>
      </main>
    </div>
  );
}
