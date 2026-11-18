import { useMutation, useQueryClient } from '@tanstack/react-query';

import { attachThread, type AttachThreadInput } from './chatApi';
import type { ChatThread } from '../types';

export function useAttachThread() {
  const queryClient = useQueryClient();
  return useMutation<ChatThread, Error, AttachThreadInput>({
    mutationFn: (input) => attachThread(input),
    onSuccess: (thread) => {
      queryClient.invalidateQueries({ queryKey: ['chat', 'session', thread.sessionId, 'threads'] });
      queryClient.invalidateQueries({ queryKey: ['chat', 'agent'] });
    },
  });
}
