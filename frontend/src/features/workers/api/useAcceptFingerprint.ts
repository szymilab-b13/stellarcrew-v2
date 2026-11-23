import { useMutation, useQueryClient } from '@tanstack/react-query';

import { acceptFingerprint } from './workersApi';
import { workerQueryKey } from './useWorker';
import { workersQueryKey } from './useWorkers';

export interface AcceptFingerprintInput {
  id: string;
  fingerprintHash: string;
  fingerprintMeta?: Record<string, unknown> | null;
}

export function useAcceptFingerprint() {
  const queryClient = useQueryClient();

  return useMutation<void, Error, AcceptFingerprintInput>({
    mutationFn: ({ id, fingerprintHash, fingerprintMeta }) =>
      acceptFingerprint(id, { fingerprintHash, fingerprintMeta }),
    onSuccess: (_result, variables) => {
      void queryClient.invalidateQueries({ queryKey: workersQueryKey });
      void queryClient.invalidateQueries({ queryKey: workerQueryKey(variables.id) });
    },
  });
}
