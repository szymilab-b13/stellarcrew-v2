import { useQuery } from '@tanstack/react-query';

import { apiFetch } from '@/lib/apiClient';

import { mapUserListItem, type UserListItem, type UserListItemPayload } from '../types';

interface VisibleUsersPayload {
  items: UserListItemPayload[];
}

export const visibleUsersQueryKey = (q: string | null) =>
  ['user-management', 'visible', q] as const;

export function useVisibleUsers(q: string | null, enabled: boolean = true) {
  return useQuery<UserListItem[]>({
    queryKey: visibleUsersQueryKey(q),
    queryFn: async () => {
      const path =
        q && q.trim() !== ''
          ? `/api/user-management/users/visible?q=${encodeURIComponent(q.trim())}`
          : '/api/user-management/users/visible';
      const payload = await apiFetch<VisibleUsersPayload>(path);
      return payload.items.map(mapUserListItem);
    },
    enabled,
    staleTime: 10_000,
  });
}
