import { useQuery } from '@tanstack/react-query';

import { fetchAgentThreads } from './chatApi';
import type { ListAgentThreadsResponse } from '../types';

export const agentThreadsQueryKey = (agentId: string) =>
  ['chat', 'agent', agentId, 'threads'] as const;

export function useAgentThreads(agentId: string | null) {
  return useQuery<ListAgentThreadsResponse>({
    queryKey: agentId ? agentThreadsQueryKey(agentId) : ['chat', 'agent', 'disabled', 'threads'],
    queryFn: () => fetchAgentThreads(agentId as string),
    enabled: agentId !== null,
    staleTime: 30_000,
  });
}
