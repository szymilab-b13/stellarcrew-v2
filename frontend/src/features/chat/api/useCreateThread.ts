import { useMutation, useQueryClient } from '@tanstack/react-query';

import { createThread, type CreateThreadInput } from './chatApi';
import type { ChatThread } from '../types';

export function useCreateThread() {
  const queryClient = useQueryClient();
  return useMutation<ChatThread, Error, CreateThreadInput>({
    mutationFn: (input) => createThread(input),
    onSuccess: (thread) => {
      queryClient.invalidateQueries({ queryKey: ['chat', 'session', thread.sessionId, 'threads'] });
      queryClient.invalidateQueries({ queryKey: ['chat', 'conversations'] });
      queryClient.invalidateQueries({ queryKey: ['chat', 'agent'] });
    },
  });
}
