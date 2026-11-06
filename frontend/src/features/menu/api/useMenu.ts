import { useQuery } from '@tanstack/react-query';

import { readAuthToken } from '@/lib/authToken';

import { fetchMenu } from './menuApi';
import type { MenuSection } from '../types';

export const menuQueryKey = ['menu'] as const;

export function useMenu() {
  return useQuery<MenuSection[]>({
    queryKey: menuQueryKey,
    queryFn: fetchMenu,
    enabled: readAuthToken() !== null,
    staleTime: 60_000,
    retry: false,
  });
}
