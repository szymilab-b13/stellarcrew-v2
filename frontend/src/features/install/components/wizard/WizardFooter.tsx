import { ArrowLeft, ArrowRight, Check, Loader2, Shield } from 'lucide-react';
import { useTranslation } from 'react-i18next';

interface WizardFooterProps {
  currentIndex: number;
  totalSteps: number;
  isLastStep: boolean;
  isSubmitting: boolean;
  canContinue: boolean;
  onBack(): void;
  onContinue(): void;
}

export function WizardFooter({
  currentIndex,
  totalSteps,
  isLastStep,
  isSubmitting,
  canContinue,
  onBack,
  onContinue,
}: WizardFooterProps) {
  const { t } = useTranslation();

  return (
    <footer className="flex items-center justify-between gap-4 border-t border-sc-border pt-6">
      <div className="flex items-center gap-4">
        {currentIndex > 0 ? (
          <button
            type="button"
            onClick={onBack}
            disabled={isSubmitting}
            className="inline-flex items-center gap-2 rounded-md border border-sc-border bg-sc-bg-card px-4 py-2 text-sm font-medium text-sc-text transition-colors hover:bg-sc-bg-secondary disabled:opacity-60"
          >
            <ArrowLeft size={16} aria-hidden />
            {t('common.back')}
          </button>
        ) : (
          <span className="inline-flex items-center gap-2 text-xs text-sc-text-muted">
            <Shield size={14} aria-hidden /> {t('common.shieldNote')}
          </span>
        )}
      </div>

      <div className="flex items-center gap-4">
        <span className="hidden text-xs text-sc-text-muted sm:inline">
          {t('common.ofSteps', { current: currentIndex + 1, total: totalSteps })}
        </span>
        <button
          type="button"
          onClick={onContinue}
          disabled={!canContinue || isSubmitting}
          className="inline-flex items-center gap-2 rounded-md bg-sc-primary px-4 py-2 text-sm font-semibold text-sc-primary-foreground shadow-sm transition-opacity hover:opacity-90 disabled:opacity-60"
        >
          {isSubmitting ? (
            <>
              <Loader2 className="animate-spin" size={16} aria-hidden />
              <span>{t('common.installing')}</span>
            </>
          ) : (
            <>
              <span>{isLastStep ? t('common.finish') : t('common.continue')}</span>
              {isLastStep ? <Check size={16} aria-hidden /> : <ArrowRight size={16} aria-hidden />}
            </>
          )}
        </button>
      </div>
    </footer>
  );
}
