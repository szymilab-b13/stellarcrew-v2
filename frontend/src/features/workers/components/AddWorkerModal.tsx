import { Check, Copy, Loader2, Terminal, X } from 'lucide-react';
import { useEffect, useState } from 'react';
import { useTranslation } from 'react-i18next';

import { Button } from '@/components/ui/Button';

import { useCreatePairingCode } from '../api/useCreatePairingCode';
import type { PairingCodeResponse } from '../types';

interface AddWorkerModalProps {
  onClose(): void;
}

function formatCountdown(ms: number): string {
  const totalSeconds = Math.max(0, Math.floor(ms / 1000));
  const minutes = Math.floor(totalSeconds / 60);
  const seconds = totalSeconds % 60;
  return `${minutes}:${seconds.toString().padStart(2, '0')}`;
}

export function AddWorkerModal({ onClose }: AddWorkerModalProps) {
  const { t } = useTranslation();
  const createCode = useCreatePairingCode();
  const [pairingCode, setPairingCode] = useState<PairingCodeResponse | null>(null);
  const [copied, setCopied] = useState(false);
  const [remainingMs, setRemainingMs] = useState<number>(0);

  useEffect(() => {
    function handleKey(event: KeyboardEvent) {
      if (event.key === 'Escape') {
        onClose();
      }
    }
    document.addEventListener('keydown', handleKey);
    return () => document.removeEventListener('keydown', handleKey);
  }, [onClose]);

  useEffect(() => {
    if (pairingCode === null) return;
    const expiresAt = new Date(pairingCode.expiresAt).getTime();
    const tick = () => setRemainingMs(expiresAt - Date.now());
    tick();
    const id = window.setInterval(tick, 1000);
    return () => window.clearInterval(id);
  }, [pairingCode]);

  function handleGenerate() {
    setCopied(false);
    createCode.mutate(
      {},
      {
        onSuccess: (response) => setPairingCode(response),
      },
    );
  }

  async function handleCopy() {
    if (pairingCode === null) return;
    await navigator.clipboard.writeText(pairingCode.pairCommand);
    setCopied(true);
    window.setTimeout(() => setCopied(false), 2000);
  }

  const expired = pairingCode !== null && remainingMs <= 0;

  return (
    <div
      role="dialog"
      aria-modal="true"
      aria-label={t('workers.add.title')}
      className="fixed inset-0 z-50 flex items-center justify-center bg-black/40 p-4"
      onClick={(event) => {
        if (event.target === event.currentTarget) {
          onClose();
        }
      }}
    >
      <div className="flex max-h-[85vh] w-full max-w-2xl flex-col gap-4 overflow-auto rounded-lg border border-sc-border bg-sc-bg p-5 shadow-lg">
        <header className="flex items-center justify-between gap-2">
          <h2 className="flex items-center gap-2 text-lg font-semibold text-sc-text">
            <Terminal size={18} aria-hidden />
            {t('workers.add.title')}
          </h2>
          <button
            type="button"
            onClick={onClose}
            aria-label={t('workers.add.close')}
            className="inline-flex h-7 w-7 items-center justify-center rounded-md text-sc-text-muted hover:bg-sc-bg-card hover:text-sc-text"
          >
            <X size={16} aria-hidden />
          </button>
        </header>

        {pairingCode === null ? (
          <section className="flex flex-col gap-3">
            <p className="text-sm text-sc-text-muted">{t('workers.add.intro')}</p>
            {createCode.isError ? (
              <p
                role="alert"
                className="rounded-md border border-red-300 bg-red-50 px-3 py-2 text-sm text-red-900"
              >
                {t('workers.add.generateError')}
              </p>
            ) : null}
            <div className="flex justify-end">
              <Button variant="primary" onClick={handleGenerate} disabled={createCode.isPending}>
                {createCode.isPending ? (
                  <>
                    <Loader2 className="animate-spin" size={14} aria-hidden />{' '}
                    {t('workers.add.generating')}
                  </>
                ) : (
                  t('workers.add.generate')
                )}
              </Button>
            </div>
          </section>
        ) : (
          <section className="flex flex-col gap-4">
            <div className="flex flex-col gap-2 rounded-md border border-sc-border bg-sc-bg-card/60 p-3">
              <div className="flex items-center justify-between gap-3">
                <span className="text-xs uppercase tracking-wide text-sc-text-muted">
                  {t('workers.add.codeLabel')}
                </span>
                <span
                  className={`text-xs ${expired ? 'text-red-600 dark:text-red-400' : 'text-sc-text-muted'}`}
                >
                  {expired
                    ? t('workers.add.expired')
                    : t('workers.add.expiresIn', { time: formatCountdown(remainingMs) })}
                </span>
              </div>
              <div className="font-mono text-2xl font-semibold tracking-wider text-sc-text">
                {pairingCode.code}
              </div>
            </div>

            <div className="flex flex-col gap-2">
              <span className="text-xs uppercase tracking-wide text-sc-text-muted">
                {t('workers.add.commandLabel')}
              </span>
              <div className="flex items-stretch gap-2">
                <pre className="flex-1 overflow-x-auto rounded-md border border-sc-border bg-sc-bg-card/60 p-3 font-mono text-xs text-sc-text">
                  {pairingCode.pairCommand}
                </pre>
                <button
                  type="button"
                  onClick={handleCopy}
                  aria-label={t('workers.add.copy')}
                  className="inline-flex h-auto w-10 flex-none items-center justify-center rounded-md border border-sc-border bg-sc-bg-card text-sc-text hover:bg-sc-bg-card/70"
                >
                  {copied ? <Check size={16} aria-hidden /> : <Copy size={16} aria-hidden />}
                </button>
              </div>
              {copied ? (
                <p role="status" className="text-xs text-emerald-600 dark:text-emerald-400">
                  {t('workers.add.copied')}
                </p>
              ) : null}
              <p className="text-xs text-sc-text-muted">{t('workers.add.hint')}</p>
            </div>

            {expired ? (
              <div className="flex justify-end gap-2">
                <Button variant="primary" onClick={handleGenerate} disabled={createCode.isPending}>
                  {createCode.isPending ? t('workers.add.generating') : t('workers.add.regenerate')}
                </Button>
              </div>
            ) : null}
          </section>
        )}
      </div>
    </div>
  );
}
