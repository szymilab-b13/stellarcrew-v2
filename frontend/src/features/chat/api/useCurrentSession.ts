import { useQuery } from '@tanstack/react-query';

import { fetchCurrentSession } from './chatApi';
import type { ChatSession } from '../types';

export const currentSessionQueryKey = () => ['chat', 'session', 'current'] as const;

export function useCurrentSession() {
  return useQuery<ChatSession>({
    queryKey: currentSessionQueryKey(),
    queryFn: fetchCurrentSession,
    staleTime: 30_000,
  });
}
