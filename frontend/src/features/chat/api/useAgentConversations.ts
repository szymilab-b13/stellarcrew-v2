import { useQuery } from '@tanstack/react-query';

import { fetchAgentConversations } from './chatApi';
import type { ListAgentConversationsResponse } from '../types';

export const agentConversationsQueryKey = () => ['chat', 'conversations'] as const;

export function useAgentConversations() {
  return useQuery<ListAgentConversationsResponse>({
    queryKey: agentConversationsQueryKey(),
    queryFn: () => fetchAgentConversations(),
    staleTime: 30_000,
  });
}
