import {
  CircleDashed,
  Clock3,
  Loader2,
  Plus,
  Server,
  ShieldAlert,
  ShieldCheck,
} from 'lucide-react';
import { useMemo, useState } from 'react';
import { useTranslation } from 'react-i18next';
import { Link } from 'react-router-dom';

import { usePageBreadcrumbs } from '@/components/app/useBreadcrumbs';
import { Button } from '@/components/ui/Button';

import { useWorkers } from '../api/useWorkers';
import type { FingerprintStatus, Worker } from '../types';
import { AddWorkerModal } from './AddWorkerModal';

function FingerprintBadge({ status }: { status: FingerprintStatus }) {
  const { t } = useTranslation();
  if (status === 'warning') {
    return (
      <span
        role="status"
        className="inline-flex items-center gap-1 rounded-md border border-amber-400/50 bg-amber-500/10 px-2 py-0.5 text-xs text-amber-700 dark:text-amber-300"
      >
        <ShieldAlert size={12} aria-hidden />
        {t('workers.list.fingerprint.warning')}
      </span>
    );
  }
  if (status === 'missing') {
    return (
      <span className="inline-flex items-center gap-1 rounded-md border border-sc-border bg-sc-bg-card/80 px-2 py-0.5 text-xs text-sc-text-muted">
        <CircleDashed size={12} aria-hidden />
        {t('workers.list.fingerprint.missing')}
      </span>
    );
  }
  return (
    <span className="inline-flex items-center gap-1 rounded-md border border-emerald-400/50 bg-emerald-500/10 px-2 py-0.5 text-xs text-emerald-700 dark:text-emerald-300">
      <ShieldCheck size={12} aria-hidden />
      {t('workers.list.fingerprint.ok')}
    </span>
  );
}

function OnlineDot({ isOnline }: { isOnline: boolean }) {
  const { t } = useTranslation();
  return (
    <span
      aria-label={isOnline ? t('workers.list.onlineAria') : t('workers.list.offlineAria')}
      className={`inline-block h-2 w-2 rounded-full ${isOnline ? 'bg-emerald-500' : 'bg-sc-text-muted/40'}`}
    />
  );
}

function RelativeTime({ iso }: { iso: string | null }) {
  const { t } = useTranslation();
  if (iso === null)
    return <span className="text-sc-text-muted">{t('workers.list.neverContacted')}</span>;
  const date = new Date(iso);
  const formatted = new Intl.DateTimeFormat(undefined, {
    year: 'numeric',
    month: '2-digit',
    day: '2-digit',
    hour: '2-digit',
    minute: '2-digit',
  }).format(date);
  return <span>{formatted}</span>;
}

function WorkerCard({ worker }: { worker: Worker }) {
  const { t } = useTranslation();
  return (
    <article className="flex flex-col gap-3 rounded-lg border border-sc-border bg-sc-bg-card/80 p-4">
      <header className="flex items-start justify-between gap-3">
        <div className="flex min-w-0 flex-1 flex-col gap-1">
          <div className="flex items-center gap-2">
            <OnlineDot isOnline={worker.isOnline} />
            <Link
              to={`/settings/workers/${worker.id}`}
              className="truncate text-base font-medium text-sc-text hover:underline"
            >
              {worker.name}
            </Link>
          </div>
          {worker.hostLabel !== null ? (
            <p className="truncate text-xs text-sc-text-muted">{worker.hostLabel}</p>
          ) : null}
        </div>
        <FingerprintBadge status={worker.fingerprintStatus} />
      </header>
      <dl className="grid grid-cols-2 gap-2 text-xs text-sc-text-muted">
        <div className="flex flex-col gap-0.5">
          <dt>{t('workers.list.lastContact')}</dt>
          <dd className="flex items-center gap-1 text-sc-text">
            <Clock3 size={12} aria-hidden />
            <RelativeTime iso={worker.lastHeartbeatAt} />
          </dd>
        </div>
        <div className="flex flex-col gap-0.5">
          <dt>{t('workers.list.fingerprintLabel')}</dt>
          <dd className="font-mono text-sc-text">{worker.fingerprintHashShort ?? '—'}</dd>
        </div>
      </dl>
    </article>
  );
}

export function WorkersListPage() {
  const { t } = useTranslation();
  const workersQuery = useWorkers();
  const [isAddOpen, setIsAddOpen] = useState(false);

  const breadcrumbs = useMemo(() => [{ label: t('workers.list.breadcrumb') }], [t]);
  usePageBreadcrumbs(breadcrumbs);

  return (
    <div className="flex w-full flex-col gap-6">
      <header className="flex items-start justify-between gap-3">
        <div className="flex flex-col gap-1">
          <h1 className="flex items-center gap-2 text-2xl font-semibold tracking-tight text-sc-text">
            <Server size={22} aria-hidden />
            {t('workers.list.title')}
          </h1>
          <p className="text-sm text-sc-text-muted">{t('workers.list.subtitle')}</p>
        </div>
        <Button variant="primary" onClick={() => setIsAddOpen(true)}>
          <Plus size={14} aria-hidden /> {t('workers.list.addCta')}
        </Button>
      </header>

      {workersQuery.isPending ? (
        <p className="flex items-center gap-2 text-sm text-sc-text-muted">
          <Loader2 className="animate-spin" size={14} aria-hidden />
          {t('workers.list.loading')}
        </p>
      ) : null}

      {workersQuery.isError ? (
        <p
          role="alert"
          className="rounded-md border border-red-300 bg-red-50 px-3 py-2 text-sm text-red-900"
        >
          {t('workers.list.loadError')}
        </p>
      ) : null}

      {workersQuery.isSuccess && workersQuery.data.workers.length === 0 ? (
        <section
          aria-label={t('workers.list.emptyAria')}
          className="flex min-h-[18rem] flex-col items-center justify-center gap-3 rounded-lg border border-dashed border-sc-border bg-sc-bg-card/40 p-8 text-center"
        >
          <Server size={32} aria-hidden className="text-sc-text-muted" />
          <p className="text-base font-medium text-sc-text">{t('workers.list.emptyTitle')}</p>
          <p className="max-w-md text-sm text-sc-text-muted">{t('workers.list.emptyBody')}</p>
          <Button variant="primary" onClick={() => setIsAddOpen(true)}>
            <Plus size={14} aria-hidden /> {t('workers.list.addCta')}
          </Button>
        </section>
      ) : null}

      {workersQuery.isSuccess && workersQuery.data.workers.length > 0 ? (
        <section
          aria-label={t('workers.list.listAria')}
          className="grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-3"
        >
          {workersQuery.data.workers.map((worker) => (
            <WorkerCard key={worker.id} worker={worker} />
          ))}
        </section>
      ) : null}

      {isAddOpen ? <AddWorkerModal onClose={() => setIsAddOpen(false)} /> : null}
    </div>
  );
}
