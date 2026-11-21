import { useQuery } from '@tanstack/react-query';

import { fetchWorkers } from './workersApi';
import type { ListWorkersResponse } from '../types';

export const workersQueryKey = ['workers', 'list'] as const;

export function useWorkers() {
  return useQuery<ListWorkersResponse>({
    queryKey: workersQueryKey,
    queryFn: fetchWorkers,
    staleTime: 30_000,
  });
}
