import { useMutation, useQuery, useQueryClient } from '@tanstack/react-query';

import { createUser, fetchUsers } from './usersApi';
import type { CreateUserInput, UserListFilters, UserListPage } from '../types';

export const usersQueryKey = ['user-management', 'users'] as const;

function usersListKey(filters: UserListFilters) {
  return [...usersQueryKey, filters] as const;
}

export function useUsersList(filters: UserListFilters = {}) {
  return useQuery<UserListPage>({
    queryKey: usersListKey(filters),
    queryFn: () => fetchUsers(filters),
    staleTime: 30_000,
  });
}

export function useCreateUser() {
  const queryClient = useQueryClient();
  return useMutation({
    mutationFn: (input: CreateUserInput) => createUser(input),
    onSuccess: () => {
      queryClient.invalidateQueries({ queryKey: usersQueryKey });
    },
  });
}
