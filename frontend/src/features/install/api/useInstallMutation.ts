import { useMutation, useQueryClient } from '@tanstack/react-query';

import { install, type InstallPayload } from './installApi';
import { installStatusQueryKey } from './useInstallStatus';

export function useInstallMutation() {
  const queryClient = useQueryClient();

  return useMutation({
    mutationFn: (payload: InstallPayload) => install(payload),
    onSuccess: () => {
      queryClient.setQueryData(installStatusQueryKey, { isInstalled: true });
    },
  });
}
