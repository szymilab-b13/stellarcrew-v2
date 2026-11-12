import { useQuery } from '@tanstack/react-query';

import type { Organization } from '../types';

import { fetchMyOrganization } from './organizationsApi';

export const myOrganizationQueryKey = ['my-organization'] as const;

export function useMyOrganization() {
  return useQuery<Organization | null>({
    queryKey: myOrganizationQueryKey,
    queryFn: fetchMyOrganization,
    staleTime: 60_000,
  });
}
