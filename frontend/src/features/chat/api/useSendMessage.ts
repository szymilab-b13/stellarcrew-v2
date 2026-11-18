import { useMutation, useQueryClient } from '@tanstack/react-query';

import { sendMessage, type SendMessageInput } from './chatApi';
import type { ChatMessage } from '../types';

export function useSendMessage() {
  const queryClient = useQueryClient();
  return useMutation<ChatMessage, Error, SendMessageInput>({
    mutationFn: (input) => sendMessage(input),
    onSuccess: (message) => {
      queryClient.invalidateQueries({ queryKey: ['chat', 'thread', message.threadId, 'messages'] });
      queryClient.invalidateQueries({ queryKey: ['chat', 'agent'] });
      queryClient.invalidateQueries({ queryKey: ['chat', 'conversations'] });
    },
  });
}
