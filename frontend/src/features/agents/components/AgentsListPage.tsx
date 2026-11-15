import { Loader2, X } from 'lucide-react';
import { useEffect, useMemo, useState } from 'react';
import { useTranslation } from 'react-i18next';
import { Link, useLocation, useMatch, useNavigate } from 'react-router-dom';

import { usePageBreadcrumbs } from '@/components/app/useBreadcrumbs';
import { Button } from '@/components/ui/Button';

import { useAgentsList } from '../api/useAgents';
import { AddAgentTile } from './AddAgentTile';
import { AgentTile } from './AgentTile';
import { CreateAgentChoosePathDialog } from './CreateAgentChoosePathDialog';

interface LocationState {
  deletedAgentName?: string;
}

export function AgentsListPage() {
  const { t } = useTranslation();
  const navigate = useNavigate();
  const location = useLocation();
  const agentsQuery = useAgentsList();
  const createMatch = useMatch('/agents/new');

  const breadcrumbs = useMemo(() => [{ label: t('agents.breadcrumb') }], [t]);
  usePageBreadcrumbs(breadcrumbs);

  const navState = (location.state ?? null) as LocationState | null;
  const [deletedBanner, setDeletedBanner] = useState<string | null>(
    navState?.deletedAgentName ?? null,
  );
  useEffect(() => {
    if (navState?.deletedAgentName) {
      navigate(location.pathname, { replace: true, state: null });
    }
  }, [navState, navigate, location.pathname]);

  function openCreate() {
    navigate('/agents/new');
  }

  function closeCreate() {
    navigate('/agents');
  }

  function dismissBanner() {
    setDeletedBanner(null);
  }

  return (
    <div className="flex w-full flex-col gap-6">
      <header className="flex items-start justify-between gap-3">
        <div className="flex flex-col gap-1">
          <h1 className="text-2xl font-semibold tracking-tight text-sc-text">
            {t('agents.title')}
          </h1>
          <p className="text-sm text-sc-text-muted">{t('agents.subtitle')}</p>
        </div>
        <Button variant="primary" onClick={openCreate}>
          {t('agents.addTile.label')}
        </Button>
      </header>

      {deletedBanner ? (
        <div
          role="status"
          className="flex items-start justify-between gap-3 rounded-md border border-emerald-400/40 bg-emerald-500/10 px-3 py-2 text-sm text-emerald-700 dark:text-emerald-300"
        >
          <p>
            {t('agents.deletedBanner.body', { name: deletedBanner })}{' '}
            <Link to="/admin/audit" className="font-semibold underline">
              {t('agents.deletedBanner.undoLink')}
            </Link>
          </p>
          <button
            type="button"
            onClick={dismissBanner}
            aria-label={t('agents.deletedBanner.dismiss')}
            className="inline-flex h-6 w-6 flex-none items-center justify-center rounded-md text-emerald-700 hover:bg-emerald-500/20 dark:text-emerald-300"
          >
            <X size={14} aria-hidden />
          </button>
        </div>
      ) : null}

      {agentsQuery.isPending ? (
        <p className="flex items-center gap-2 text-sm text-sc-text-muted">
          <Loader2 className="animate-spin" size={14} aria-hidden />
          {t('agents.loading')}
        </p>
      ) : null}

      {agentsQuery.isError ? (
        <p
          role="alert"
          className="rounded-md border border-red-300 bg-red-50 px-3 py-2 text-sm text-red-900"
        >
          {t('agents.loadError')}
        </p>
      ) : null}

      {agentsQuery.isSuccess && agentsQuery.data.length === 0 ? (
        <section
          aria-label={t('agents.emptyAria')}
          className="flex min-h-[18rem] flex-col items-center justify-center gap-3 rounded-lg border border-dashed border-sc-border bg-sc-bg-card/40 p-8 text-center"
        >
          <p className="text-base font-medium text-sc-text">{t('agents.emptyTitle')}</p>
          <p className="max-w-md text-sm text-sc-text-muted">{t('agents.emptyBody')}</p>
          <Button variant="primary" onClick={openCreate}>
            {t('agents.addTile.label')}
          </Button>
        </section>
      ) : null}

      {agentsQuery.isSuccess && agentsQuery.data.length > 0 ? (
        <section
          aria-label={t('agents.listAria')}
          className="grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4"
        >
          {agentsQuery.data.map((agent) => (
            <AgentTile key={agent.id} agent={agent} />
          ))}
          <AddAgentTile onClick={openCreate} />
        </section>
      ) : null}

      {createMatch ? <CreateAgentChoosePathDialog onClose={closeCreate} /> : null}
    </div>
  );
}
