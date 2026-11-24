import { keepPreviousData, useQuery } from '@tanstack/react-query';

import { fetchMessages } from './chatApi';
import type { ListMessagesResponse } from '../types';

export interface UseMessagesInput {
  threadId: string | null;
  limit?: number;
  offset?: number;
}

export const messagesQueryKey = (threadId: string, limit?: number, offset?: number) =>
  [
    'chat',
    'thread',
    threadId,
    'messages',
    { limit: limit ?? null, offset: offset ?? null },
  ] as const;

const AGENT_REPLY_POLL_INTERVAL_MS = 3_000;

export function useMessages({ threadId, limit, offset }: UseMessagesInput) {
  return useQuery<ListMessagesResponse>({
    queryKey: threadId
      ? messagesQueryKey(threadId, limit, offset)
      : ['chat', 'thread', 'disabled', 'messages'],
    queryFn: () => fetchMessages({ threadId: threadId as string, limit, offset }),
    enabled: threadId !== null,
    placeholderData: keepPreviousData,
    staleTime: 2_000,
    refetchInterval: AGENT_REPLY_POLL_INTERVAL_MS,
    refetchIntervalInBackground: false,
  });
}
