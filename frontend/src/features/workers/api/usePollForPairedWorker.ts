import { useQuery, useQueryClient } from '@tanstack/react-query';
import { useEffect } from 'react';

import type { ListWorkersResponse } from '../types';
import { fetchWorkers } from './workersApi';
import { workersQueryKey } from './useWorkers';

interface UsePollForPairedWorkerInput {
  pairingIssuedAt: string | null;
  expiresAt: string | null;
  enabled: boolean;
  onPaired(workerId: string): void;
}

export function usePollForPairedWorker({
  pairingIssuedAt,
  expiresAt,
  enabled,
  onPaired,
}: UsePollForPairedWorkerInput) {
  const queryClient = useQueryClient();
  const expiryMs = expiresAt !== null ? new Date(expiresAt).getTime() : null;

  const query = useQuery<ListWorkersResponse>({
    queryKey: ['workers', 'pair-polling', pairingIssuedAt ?? 'idle'],
    queryFn: fetchWorkers,
    enabled,
    refetchInterval: (query) => {
      if (!enabled) return false;
      if (expiryMs !== null && Date.now() >= expiryMs) return false;
      if (query.state.data !== undefined && query.state.data.workers.length > 0) {
        return false;
      }
      return 2000;
    },
    refetchIntervalInBackground: false,
  });

  useEffect(() => {
    if (!enabled || query.data === undefined || pairingIssuedAt === null) return;
    const issuedMs = new Date(pairingIssuedAt).getTime();
    const fresh = query.data.workers.find(
      (worker) => new Date(worker.createdAt).getTime() >= issuedMs,
    );
    if (fresh) {
      void queryClient.invalidateQueries({ queryKey: workersQueryKey });
      onPaired(fresh.id);
    }
  }, [enabled, pairingIssuedAt, query.data, queryClient, onPaired]);
}
