import { useQuery } from '@tanstack/react-query';

import { fetchUserAudit, fetchUserDetail } from './usersApi';
import type { UserAuditEntry, UserDetail } from '../types';

export function userDetailQueryKey(id: string) {
  return ['user-management', 'detail', id] as const;
}

export function userAuditQueryKey(id: string) {
  return ['user-management', 'audit', id] as const;
}

export function useUserDetail(id: string) {
  return useQuery<UserDetail>({
    queryKey: userDetailQueryKey(id),
    queryFn: () => fetchUserDetail(id),
    enabled: id.length > 0,
    staleTime: 10_000,
  });
}

export function useUserAudit(id: string) {
  return useQuery<UserAuditEntry[]>({
    queryKey: userAuditQueryKey(id),
    queryFn: () => fetchUserAudit(id),
    enabled: id.length > 0,
    staleTime: 10_000,
  });
}
