import { useQuery } from '@tanstack/react-query';

import { fetchInstallStatus, type InstallStatus } from './installApi';

export const installStatusQueryKey = ['install', 'status'] as const;

export function useInstallStatus() {
  return useQuery<InstallStatus>({
    queryKey: installStatusQueryKey,
    queryFn: fetchInstallStatus,
    staleTime: 0,
    retry: false,
  });
}
