import { useQuery } from '@tanstack/react-query';

import { fetchThreads } from './chatApi';
import type { ListThreadsResponse } from '../types';

export const threadsQueryKey = (sessionId: string) =>
  ['chat', 'session', sessionId, 'threads'] as const;

export function useThreads(sessionId: string | null) {
  return useQuery<ListThreadsResponse>({
    queryKey: sessionId ? threadsQueryKey(sessionId) : ['chat', 'session', 'disabled', 'threads'],
    queryFn: () => fetchThreads(sessionId as string),
    enabled: sessionId !== null,
    staleTime: 30_000,
  });
}
