import { useMutation, useQueryClient } from '@tanstack/react-query';

import { revokeWorker } from './workersApi';
import { workersQueryKey } from './useWorkers';

export function useRevokeWorker() {
  const queryClient = useQueryClient();

  return useMutation<void, Error, string>({
    mutationFn: (id) => revokeWorker(id),
    onSuccess: () => {
      void queryClient.invalidateQueries({ queryKey: workersQueryKey });
    },
  });
}
