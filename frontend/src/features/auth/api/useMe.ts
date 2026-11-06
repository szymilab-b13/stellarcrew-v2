import { useQuery } from '@tanstack/react-query';

import { readAuthToken } from '@/lib/authToken';

import { fetchMe } from './authApi';
import type { AuthUser } from '../types';

export const authMeQueryKey = ['auth', 'me'] as const;

export function useMe() {
  return useQuery<AuthUser>({
    queryKey: authMeQueryKey,
    queryFn: fetchMe,
    enabled: readAuthToken() !== null,
    retry: false,
    staleTime: 60_000,
  });
}
