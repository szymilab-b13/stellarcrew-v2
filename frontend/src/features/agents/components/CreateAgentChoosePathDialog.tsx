import { ArrowRight, Sparkles, Wand2, X } from 'lucide-react';
import { useTranslation } from 'react-i18next';
import { useNavigate } from 'react-router-dom';

interface CreateAgentChoosePathDialogProps {
  onClose: () => void;
}

export function CreateAgentChoosePathDialog({ onClose }: CreateAgentChoosePathDialogProps) {
  const { t } = useTranslation();
  const navigate = useNavigate();

  function chooseTemplate() {
    navigate('/agents/new/template');
  }

  function chooseScratch() {
    navigate('/agents/new/scratch');
  }

  return (
    <div
      role="dialog"
      aria-modal="true"
      aria-labelledby="create-agent-choose-path-title"
      className="fixed inset-0 z-50 flex items-center justify-center bg-black/50 p-4"
    >
      <div className="flex w-full max-w-2xl flex-col gap-5 rounded-lg border border-sc-border bg-sc-bg-card p-6 shadow-xl">
        <header className="flex items-start justify-between gap-3">
          <div className="flex flex-col gap-1">
            <h2 id="create-agent-choose-path-title" className="text-lg font-semibold text-sc-text">
              {t('agents.choosePath.title')}
            </h2>
            <p className="text-sm text-sc-text-muted">{t('agents.choosePath.subtitle')}</p>
          </div>
          <button
            type="button"
            onClick={onClose}
            aria-label={t('common.close')}
            className="inline-flex h-7 w-7 flex-none items-center justify-center rounded-md text-sc-text-muted transition-colors hover:bg-sc-bg hover:text-sc-text"
          >
            <X size={16} aria-hidden />
          </button>
        </header>

        <div className="grid grid-cols-1 gap-3 sm:grid-cols-2">
          <button
            type="button"
            onClick={chooseTemplate}
            aria-label={t('agents.choosePath.template.aria')}
            className="group flex flex-col items-start gap-3 rounded-lg border border-sc-border bg-sc-bg p-5 text-left transition-colors hover:border-sc-primary hover:bg-sc-bg-card"
          >
            <span
              aria-hidden
              className="inline-flex h-10 w-10 items-center justify-center rounded-full bg-sc-primary/10 text-sc-primary"
            >
              <Sparkles size={20} />
            </span>
            <h3 className="text-base font-semibold text-sc-text">
              {t('agents.choosePath.template.title')}
            </h3>
            <p className="text-sm text-sc-text-muted">{t('agents.choosePath.template.body')}</p>
            <span className="mt-auto inline-flex items-center gap-1 text-xs font-medium text-sc-primary">
              {t('agents.choosePath.template.cta')}
              <ArrowRight size={12} aria-hidden />
            </span>
          </button>

          <button
            type="button"
            onClick={chooseScratch}
            aria-label={t('agents.choosePath.scratch.aria')}
            className="group flex flex-col items-start gap-3 rounded-lg border border-sc-border bg-sc-bg p-5 text-left transition-colors hover:border-sc-primary hover:bg-sc-bg-card"
          >
            <span
              aria-hidden
              className="inline-flex h-10 w-10 items-center justify-center rounded-full bg-sc-primary/10 text-sc-primary"
            >
              <Wand2 size={20} />
            </span>
            <h3 className="text-base font-semibold text-sc-text">
              {t('agents.choosePath.scratch.title')}
            </h3>
            <p className="text-sm text-sc-text-muted">{t('agents.choosePath.scratch.body')}</p>
            <span className="mt-auto inline-flex items-center gap-1 text-xs font-medium text-sc-primary">
              {t('agents.choosePath.scratch.cta')}
              <ArrowRight size={12} aria-hidden />
            </span>
          </button>
        </div>
      </div>
    </div>
  );
}
