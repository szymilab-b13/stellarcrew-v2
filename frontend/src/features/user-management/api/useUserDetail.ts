import { useMutation, useQuery, useQueryClient } from '@tanstack/react-query';

import {
  deactivateUser,
  fetchUserAudit,
  fetchUserDetail,
  reactivateUser,
  resetUserPassword,
} from './usersApi';
import { usersQueryKey } from './useUsers';
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

export function useDeactivateUser(id: string) {
  const queryClient = useQueryClient();
  return useMutation({
    mutationFn: () => deactivateUser(id),
    onSuccess: () => {
      queryClient.invalidateQueries({ queryKey: userDetailQueryKey(id) });
      queryClient.invalidateQueries({ queryKey: userAuditQueryKey(id) });
      queryClient.invalidateQueries({ queryKey: usersQueryKey });
    },
  });
}

export function useReactivateUser(id: string) {
  const queryClient = useQueryClient();
  return useMutation({
    mutationFn: () => reactivateUser(id),
    onSuccess: () => {
      queryClient.invalidateQueries({ queryKey: userDetailQueryKey(id) });
      queryClient.invalidateQueries({ queryKey: userAuditQueryKey(id) });
      queryClient.invalidateQueries({ queryKey: usersQueryKey });
    },
  });
}

export function useResetUserPassword(id: string) {
  const queryClient = useQueryClient();
  return useMutation({
    mutationFn: (newPassword: string) => resetUserPassword(id, newPassword),
    onSuccess: () => {
      queryClient.invalidateQueries({ queryKey: userAuditQueryKey(id) });
    },
  });
}
