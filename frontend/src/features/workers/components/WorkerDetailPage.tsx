import { ArrowLeft, Clock3, Cpu, HardDrive, Loader2, Server, ShieldAlert, ShieldCheck } from 'lucide-react';
import { useMemo } from 'react';
import { useTranslation } from 'react-i18next';
import { Link, useParams } from 'react-router-dom';

import { usePageBreadcrumbs } from '@/components/app/useBreadcrumbs';
import { Button } from '@/components/ui/Button';

import { useAcceptFingerprint } from '../api/useAcceptFingerprint';
import { useWorker } from '../api/useWorker';
import type { FingerprintChangeEntry, HostInfo } from '../types';

function formatBytes(bytes: number | undefined): string {
  if (typeof bytes !== 'number' || bytes <= 0) return '—';
  const gb = bytes / (1024 * 1024 * 1024);
  if (gb >= 1) return `${gb.toFixed(1)} GB`;
  const mb = bytes / (1024 * 1024);
  return `${mb.toFixed(0)} MB`;
}

function formatDateTime(iso: string | null): string {
  if (iso === null) return '—';
  return new Intl.DateTimeFormat(undefined, {
    year: 'numeric',
    month: '2-digit',
    day: '2-digit',
    hour: '2-digit',
    minute: '2-digit',
  }).format(new Date(iso));
}

function HardwareSection({ hostInfo }: { hostInfo: HostInfo | null }) {
  const { t } = useTranslation();
  if (hostInfo === null) {
    return (
      <p className="text-sm text-sc-text-muted">{t('workers.detail.hardware.empty')}</p>
    );
  }
  return (
    <dl className="grid grid-cols-1 gap-3 sm:grid-cols-2">
      <div className="flex flex-col gap-0.5">
        <dt className="text-xs uppercase tracking-wide text-sc-text-muted">
          {t('workers.detail.hardware.os')}
        </dt>
        <dd className="text-sm text-sc-text">{hostInfo.os ?? '—'}{hostInfo.osRelease ? ` (${hostInfo.osRelease})` : ''}</dd>
      </div>
      <div className="flex flex-col gap-0.5">
        <dt className="text-xs uppercase tracking-wide text-sc-text-muted">
          {t('workers.detail.hardware.hostname')}
        </dt>
        <dd className="text-sm text-sc-text">{hostInfo.hostname ?? '—'}</dd>
      </div>
      <div className="flex flex-col gap-0.5">
        <dt className="flex items-center gap-1 text-xs uppercase tracking-wide text-sc-text-muted">
          <Cpu size={12} aria-hidden /> {t('workers.detail.hardware.cpu')}
        </dt>
        <dd className="text-sm text-sc-text">
          {hostInfo.cpuModel ?? '—'}
          {typeof hostInfo.cpuCount === 'number' ? ` × ${hostInfo.cpuCount}` : ''}
        </dd>
      </div>
      <div className="flex flex-col gap-0.5">
        <dt className="flex items-center gap-1 text-xs uppercase tracking-wide text-sc-text-muted">
          <HardDrive size={12} aria-hidden /> {t('workers.detail.hardware.memory')}
        </dt>
        <dd className="text-sm text-sc-text">{formatBytes(hostInfo.memoryBytes)}</dd>
      </div>
      <div className="flex flex-col gap-0.5">
        <dt className="text-xs uppercase tracking-wide text-sc-text-muted">
          {t('workers.detail.hardware.kernel')}
        </dt>
        <dd className="text-sm text-sc-text">{hostInfo.kernelVersion ?? '—'}</dd>
      </div>
      <div className="flex flex-col gap-0.5">
        <dt className="text-xs uppercase tracking-wide text-sc-text-muted">
          {t('workers.detail.hardware.arch')}
        </dt>
        <dd className="text-sm text-sc-text">{hostInfo.arch ?? '—'}</dd>
      </div>
    </dl>
  );
}

function FingerprintHistoryTable({
  entries,
  workerId,
  acceptEnabled,
}: {
  entries: FingerprintChangeEntry[];
  workerId: string;
  acceptEnabled: boolean;
}) {
  const { t } = useTranslation();
  const acceptMutation = useAcceptFingerprint();

  if (entries.length === 0) {
    return <p className="text-sm text-sc-text-muted">{t('workers.detail.fingerprintHistory.empty')}</p>;
  }

  const latestIndex = entries.length - 1;

  return (
    <div className="overflow-hidden rounded-md border border-sc-border">
      <table className="w-full text-sm">
        <thead className="bg-sc-bg-card/60 text-left text-xs uppercase tracking-wide text-sc-text-muted">
          <tr>
            <th className="px-3 py-2">{t('workers.detail.fingerprintHistory.at')}</th>
            <th className="px-3 py-2">{t('workers.detail.fingerprintHistory.status')}</th>
            <th className="px-3 py-2">{t('workers.detail.fingerprintHistory.newHash')}</th>
            <th className="px-3 py-2">{t('workers.detail.fingerprintHistory.action')}</th>
          </tr>
        </thead>
        <tbody>
          {entries.map((entry, index) => {
            const isLatestDetected = index === latestIndex && entry.status === 'detected';
            return (
              <tr key={`${entry.at}-${index}`} className="border-t border-sc-border">
                <td className="px-3 py-2 text-sc-text">{formatDateTime(entry.at)}</td>
                <td className="px-3 py-2">
                  <span
                    className={
                      entry.status === 'detected'
                        ? 'inline-flex items-center gap-1 rounded-md border border-amber-400/50 bg-amber-500/10 px-2 py-0.5 text-xs text-amber-700 dark:text-amber-300'
                        : 'inline-flex items-center gap-1 rounded-md border border-emerald-400/50 bg-emerald-500/10 px-2 py-0.5 text-xs text-emerald-700 dark:text-emerald-300'
                    }
                  >
                    {entry.status === 'detected' ? <ShieldAlert size={12} aria-hidden /> : <ShieldCheck size={12} aria-hidden />}
                    {entry.status === 'detected'
                      ? t('workers.detail.fingerprintHistory.statusDetected')
                      : t('workers.detail.fingerprintHistory.statusAccepted')}
                  </span>
                </td>
                <td className="px-3 py-2 font-mono text-xs text-sc-text">
                  {entry.newHash !== null ? entry.newHash.slice(0, 12) : '—'}
                </td>
                <td className="px-3 py-2">
                  {isLatestDetected && acceptEnabled && entry.newHash !== null ? (
                    <Button
                      variant="primary"
                      onClick={() =>
                        acceptMutation.mutate({
                          id: workerId,
                          fingerprintHash: entry.newHash as string,
                          fingerprintMeta: entry.newMeta,
                        })
                      }
                      disabled={acceptMutation.isPending}
                    >
                      {acceptMutation.isPending
                        ? t('workers.detail.fingerprintHistory.accepting')
                        : t('workers.detail.fingerprintHistory.accept')}
                    </Button>
                  ) : null}
                </td>
              </tr>
            );
          })}
        </tbody>
      </table>
    </div>
  );
}

export function WorkerDetailPage() {
  const { t } = useTranslation();
  const { id } = useParams<{ id: string }>();
  const workerQuery = useWorker(id ?? null);

  const breadcrumbs = useMemo(
    () => [
      { label: t('workers.list.breadcrumb'), to: '/settings/workers' },
      { label: workerQuery.data?.name ?? t('workers.detail.loadingBreadcrumb') },
    ],
    [t, workerQuery.data?.name],
  );
  usePageBreadcrumbs(breadcrumbs);

  if (workerQuery.isPending) {
    return (
      <p className="flex items-center gap-2 text-sm text-sc-text-muted">
        <Loader2 className="animate-spin" size={14} aria-hidden />
        {t('workers.detail.loading')}
      </p>
    );
  }

  if (workerQuery.isError || !workerQuery.data) {
    return (
      <div className="flex flex-col gap-3">
        <Link to="/settings/workers" className="inline-flex items-center gap-1 text-sm text-sc-text-muted hover:text-sc-text">
          <ArrowLeft size={14} aria-hidden /> {t('workers.detail.backToList')}
        </Link>
        <p role="alert" className="rounded-md border border-red-300 bg-red-50 px-3 py-2 text-sm text-red-900">
          {t('workers.detail.loadError')}
        </p>
      </div>
    );
  }

  const worker = workerQuery.data;
  const online = worker.isOnline;

  return (
    <div className="flex w-full flex-col gap-6">
      <div>
        <Link to="/settings/workers" className="inline-flex items-center gap-1 text-sm text-sc-text-muted hover:text-sc-text">
          <ArrowLeft size={14} aria-hidden /> {t('workers.detail.backToList')}
        </Link>
      </div>
      <header className="flex flex-col gap-1">
        <h1 className="flex items-center gap-2 text-2xl font-semibold tracking-tight text-sc-text">
          <Server size={22} aria-hidden />
          {worker.name}
          <span
            aria-label={online ? t('workers.list.onlineAria') : t('workers.list.offlineAria')}
            className={`inline-block h-2 w-2 rounded-full ${online ? 'bg-emerald-500' : 'bg-sc-text-muted/40'}`}
          />
        </h1>
        {worker.hostLabel !== null ? (
          <p className="text-sm text-sc-text-muted">{worker.hostLabel}</p>
        ) : null}
      </header>

      <section className="grid grid-cols-1 gap-3 rounded-lg border border-sc-border bg-sc-bg-card/60 p-4 sm:grid-cols-3">
        <div className="flex flex-col gap-0.5">
          <span className="text-xs uppercase tracking-wide text-sc-text-muted">
            {t('workers.detail.info.pairedAt')}
          </span>
          <span className="text-sm text-sc-text">{formatDateTime(worker.createdAt)}</span>
        </div>
        <div className="flex flex-col gap-0.5">
          <span className="flex items-center gap-1 text-xs uppercase tracking-wide text-sc-text-muted">
            <Clock3 size={12} aria-hidden /> {t('workers.list.lastContact')}
          </span>
          <span className="text-sm text-sc-text">{formatDateTime(worker.lastHeartbeatAt)}</span>
        </div>
        <div className="flex flex-col gap-0.5">
          <span className="text-xs uppercase tracking-wide text-sc-text-muted">
            {t('workers.detail.info.currentHash')}
          </span>
          <span className="font-mono text-sm text-sc-text">
            {worker.fingerprintHash !== null ? worker.fingerprintHash.slice(0, 16) + '…' : '—'}
          </span>
        </div>
      </section>

      <section className="flex flex-col gap-3">
        <h2 className="text-lg font-semibold text-sc-text">{t('workers.detail.hardware.title')}</h2>
        <HardwareSection hostInfo={worker.hostInfo} />
      </section>

      <section className="flex flex-col gap-3">
        <h2 className="text-lg font-semibold text-sc-text">
          {t('workers.detail.fingerprintHistory.title')}
        </h2>
        <FingerprintHistoryTable
          entries={worker.fingerprintChangeHistory}
          workerId={worker.id}
          acceptEnabled={worker.fingerprintStatus === 'warning'}
        />
      </section>
    </div>
  );
}
