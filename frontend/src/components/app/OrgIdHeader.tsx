import { Check, Copy, Loader2 } from 'lucide-react';
import { useState } from 'react';
import { useTranslation } from 'react-i18next';

import { useMyOrganization } from '@/features/organizations/api/useMyOrganization';

interface OrgIdHeaderProps {
  collapsed: boolean;
}

function shortenUuid(uuid: string): string {
  return uuid.replace(/-/g, '').slice(0, 8);
}

export function OrgIdHeader({ collapsed }: OrgIdHeaderProps) {
  const { t } = useTranslation();
  const { data, isPending, isError } = useMyOrganization();
  const [copied, setCopied] = useState(false);

  async function handleCopy() {
    if (!data) return;
    if (typeof navigator === 'undefined' || !navigator.clipboard) return;
    try {
      await navigator.clipboard.writeText(data.id);
      setCopied(true);
      window.setTimeout(() => setCopied(false), 1500);
    } catch {
      // clipboard denied — silent fallback (button stays idle)
    }
  }

  if (collapsed) {
    if (isPending || !data) return null;
    const short = shortenUuid(data.id);
    return (
      <div
        className="mx-auto flex h-7 w-7 items-center justify-center rounded-md border border-sc-border bg-sc-bg-card text-[10px] font-mono font-semibold text-sc-text-muted"
        title={t('sidebar.orgIdLabel') + ': ' + data.id}
        aria-label={t('sidebar.orgIdLabel') + ': ' + short}
      >
        {short.slice(0, 2).toUpperCase()}
      </div>
    );
  }

  if (isPending) {
    return (
      <div className="flex items-center gap-2 px-3 py-1.5 text-xs text-sc-text-muted">
        <Loader2 size={12} className="animate-spin" aria-hidden />
        <span>{t('sidebar.orgIdLoading')}</span>
      </div>
    );
  }

  if (isError || !data) {
    return null;
  }

  const short = shortenUuid(data.id);
  const label = copied ? t('sidebar.orgIdCopied') : t('sidebar.orgIdLabel');

  return (
    <button
      type="button"
      onClick={handleCopy}
      aria-label={t('sidebar.orgIdCopyAria', { id: data.id })}
      title={data.id}
      className="flex w-full items-center justify-between gap-2 rounded-md px-3 py-1.5 text-left text-xs text-sc-text-muted transition-colors hover:bg-sc-bg-card hover:text-sc-text"
    >
      <span className="flex min-w-0 items-center gap-1.5">
        <span className="truncate">{label}:</span>
        <span className="font-mono text-[11px] font-semibold text-sc-text">{short}</span>
      </span>
      {copied ? (
        <Check size={12} className="flex-none text-emerald-500" aria-hidden />
      ) : (
        <Copy size={12} className="flex-none opacity-60" aria-hidden />
      )}
    </button>
  );
}
