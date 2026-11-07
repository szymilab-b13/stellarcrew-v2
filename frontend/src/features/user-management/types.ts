export interface UserListItem {
  id: string;
  email: string;
  isSuperAdmin: boolean;
  workspaceCount: number;
  createdAt: string;
  deactivatedAt: string | null;
}

export interface UserListPage {
  items: UserListItem[];
  total: number;
  page: number;
  perPage: number;
}

export interface UserListFilters {
  q?: string | null;
  workspaceId?: string | null;
  hasSuperAdmin?: boolean | null;
  onlyDeactivated?: boolean | null;
  page?: number;
  perPage?: number;
}

export interface CreateUserInput {
  email: string;
  password: string;
  isSuperAdmin: boolean;
}

export interface CreatedUser {
  id: string;
  email: string;
  isSuperAdmin: boolean;
}

interface UserListItemPayload {
  id: string;
  email: string;
  is_super_admin: boolean;
  workspace_count: number;
  created_at: string;
  deactivated_at: string | null;
}

interface UserListPagePayload {
  items: UserListItemPayload[];
  total: number;
  page: number;
  per_page: number;
}

interface CreatedUserPayload {
  id: string;
  email: string;
  is_super_admin: boolean;
}

export function mapUserListItem(payload: UserListItemPayload): UserListItem {
  return {
    id: payload.id,
    email: payload.email,
    isSuperAdmin: payload.is_super_admin,
    workspaceCount: payload.workspace_count,
    createdAt: payload.created_at,
    deactivatedAt: payload.deactivated_at,
  };
}

export function mapUserListPage(payload: UserListPagePayload): UserListPage {
  return {
    items: payload.items.map(mapUserListItem),
    total: payload.total,
    page: payload.page,
    perPage: payload.per_page,
  };
}

export function mapCreatedUser(payload: CreatedUserPayload): CreatedUser {
  return {
    id: payload.id,
    email: payload.email,
    isSuperAdmin: payload.is_super_admin,
  };
}

export type { UserListItemPayload, UserListPagePayload, CreatedUserPayload };
