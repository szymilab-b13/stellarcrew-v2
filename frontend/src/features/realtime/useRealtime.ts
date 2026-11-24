import { useQueryClient } from '@tanstack/react-query';
import { useEffect } from 'react';

interface ChatMessageSent {
  type: 'message.sent';
  threadId: string;
  messageId: string;
}

interface WorkerHeartbeat {
  type: 'worker.heartbeat';
  workerId: string;
}

type RealtimeEvent = ChatMessageSent | WorkerHeartbeat;

function resolveHubUrl(): string | null {
  const env = import.meta.env.VITE_MERCURE_URL;
  if (typeof env === 'string' && env.length > 0) {
    return env;
  }
  if (typeof window === 'undefined') {
    return null;
  }
  return `${window.location.protocol}//${window.location.hostname}:3000/.well-known/mercure`;
}

export function useRealtime(): void {
  const queryClient = useQueryClient();

  useEffect(() => {
    const hub = resolveHubUrl();
    if (hub === null) {
      return;
    }

    const url = new URL(hub);
    url.searchParams.append('topic', '/chat/thread/{id}');
    url.searchParams.append('topic', '/workers/list');

    const source = new EventSource(url.toString());

    // eslint-disable-next-line no-console
    console.info('[realtime] subscribing', url.toString());

    source.onopen = () => {
      // eslint-disable-next-line no-console
      console.info('[realtime] connected');
    };

    source.onmessage = (event: MessageEvent<string>) => {
      // eslint-disable-next-line no-console
      console.info('[realtime] event', event.data);
      let parsed: RealtimeEvent | null = null;
      try {
        parsed = JSON.parse(event.data) as RealtimeEvent;
      } catch {
        return;
      }
      if (parsed === null) {
        return;
      }

      if (parsed.type === 'message.sent') {
        const threadId = parsed.threadId;
        const matches = queryClient.getQueryCache().findAll({
          predicate: (query) => {
            const key = query.queryKey;
            return (
              Array.isArray(key) &&
              key[0] === 'chat' &&
              key[1] === 'thread' &&
              key[2] === threadId &&
              key[3] === 'messages'
            );
          },
        });
        // eslint-disable-next-line no-console
        console.info(
          '[realtime] refetching',
          matches.length,
          'message queries for thread',
          threadId,
        );
        matches.forEach((query) => {
          void query.fetch();
        });
        void queryClient.refetchQueries({ queryKey: ['chat', 'conversations'] });
        void queryClient.refetchQueries({ queryKey: ['chat', 'agent'] });
      } else if (parsed.type === 'worker.heartbeat') {
        void queryClient.refetchQueries({ queryKey: ['workers', 'list'] });
        void queryClient.refetchQueries({ queryKey: ['workers', 'detail', parsed.workerId] });
      }
    };

    source.onerror = (event) => {
      // eslint-disable-next-line no-console
      console.error('[realtime] error', event);
    };

    return () => {
      source.close();
    };
  }, [queryClient]);
}
