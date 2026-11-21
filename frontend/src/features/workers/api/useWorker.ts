import { useQuery } from '@tanstack/react-query';

import { fetchWorker } from './workersApi';
import type { WorkerDetail } from '../types';

export const workerQueryKey = (id: string) => ['workers', 'detail', id] as const;

export function useWorker(id: string | null) {
  return useQuery<WorkerDetail>({
    queryKey: id ? workerQueryKey(id) : ['workers', 'detail', 'disabled'],
    queryFn: () => fetchWorker(id as string),
    enabled: id !== null,
    staleTime: 15_000,
  });
}
