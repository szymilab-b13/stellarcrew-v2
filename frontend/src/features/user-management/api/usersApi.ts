import { apiFetch } from '@/lib/apiClient';

import {
  mapCreatedUser,
  mapUserListPage,
  type CreateUserInput,
  type CreatedUser,
  type CreatedUserPayload,
  type UserListFilters,
  type UserListPage,
  type UserListPagePayload,
} from '../types';

export async function fetchUsers(filters: UserListFilters = {}): Promise<UserListPage> {
  const payload = await apiFetch<UserListPagePayload>(buildListPath(filters));
  return mapUserListPage(payload);
}

export async function createUser(input: CreateUserInput): Promise<CreatedUser> {
  const payload = await apiFetch<CreatedUserPayload>('/api/user-management/users', {
    method: 'POST',
    body: JSON.stringify({
      email: input.email,
      password: input.password,
      is_super_admin: input.isSuperAdmin,
    }),
  });
  return mapCreatedUser(payload);
}

function buildListPath(filters: UserListFilters): string {
  const params = new URLSearchParams();
  if (filters.q) params.set('q', filters.q);
  if (filters.workspaceId) params.set('workspace_id', filters.workspaceId);
  if (filters.hasSuperAdmin !== null && filters.hasSuperAdmin !== undefined) {
    params.set('has_super_admin', filters.hasSuperAdmin ? 'true' : 'false');
  }
  if (filters.onlyDeactivated !== null && filters.onlyDeactivated !== undefined) {
    params.set('only_deactivated', filters.onlyDeactivated ? 'true' : 'false');
  }
  if (filters.page !== undefined) params.set('page', String(filters.page));
  if (filters.perPage !== undefined) params.set('per_page', String(filters.perPage));

  const query = params.toString();
  return query === '' ? '/api/user-management/users' : `/api/user-management/users?${query}`;
}
