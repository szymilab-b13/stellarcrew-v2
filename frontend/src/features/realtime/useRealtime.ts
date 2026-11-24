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

    source.onmessage = (event: MessageEvent<string>) => {
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
        queryClient.invalidateQueries({
          queryKey: ['chat', 'thread', parsed.threadId, 'messages'],
        });
        queryClient.invalidateQueries({ queryKey: ['chat', 'conversations'] });
        queryClient.invalidateQueries({ queryKey: ['chat', 'agent'] });
      } else if (parsed.type === 'worker.heartbeat') {
        queryClient.invalidateQueries({ queryKey: ['workers', 'list'] });
        queryClient.invalidateQueries({ queryKey: ['workers', 'detail', parsed.workerId] });
      }
    };

    return () => {
      source.close();
    };
  }, [queryClient]);
}
