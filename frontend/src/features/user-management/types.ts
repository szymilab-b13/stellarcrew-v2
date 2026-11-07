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

export interface UserWorkspaceAssignment {
  workspaceId: string;
  workspaceName: string;
  workspaceSlug: string;
  iconColor: string;
  iconInitials: string;
  role: string;
  joinedAt: string;
}

export interface UserDetail {
  id: string;
  email: string;
  isSuperAdmin: boolean;
  displayName: string | null;
  avatarColor: string;
  createdAt: string;
  deactivatedAt: string | null;
  workspaceAssignments: UserWorkspaceAssignment[];
}

interface UserWorkspaceAssignmentPayload {
  workspace_id: string;
  workspace_name: string;
  workspace_slug: string;
  icon_color: string;
  icon_initials: string;
  role: string;
  joined_at: string;
}

interface UserDetailPayload {
  id: string;
  email: string;
  is_super_admin: boolean;
  display_name: string | null;
  avatar_color: string;
  created_at: string;
  deactivated_at: string | null;
  workspace_assignments: UserWorkspaceAssignmentPayload[];
}

export interface UserAuditEntry {
  id: string;
  actorUserId: string;
  actorEmail: string | null;
  action: string;
  resourceType: string | null;
  resourceId: string | null;
  occurredAt: string;
  undoneAt: string | null;
}

interface UserAuditEntryPayload {
  id: string;
  actor_user_id: string;
  actor_email: string | null;
  action: string;
  resource_type: string | null;
  resource_id: string | null;
  occurred_at: string;
  undone_at: string | null;
}

export function mapUserDetail(payload: UserDetailPayload): UserDetail {
  return {
    id: payload.id,
    email: payload.email,
    isSuperAdmin: payload.is_super_admin,
    displayName: payload.display_name,
    avatarColor: payload.avatar_color,
    createdAt: payload.created_at,
    deactivatedAt: payload.deactivated_at,
    workspaceAssignments: payload.workspace_assignments.map(mapAssignment),
  };
}

function mapAssignment(a: UserWorkspaceAssignmentPayload): UserWorkspaceAssignment {
  return {
    workspaceId: a.workspace_id,
    workspaceName: a.workspace_name,
    workspaceSlug: a.workspace_slug,
    iconColor: a.icon_color,
    iconInitials: a.icon_initials,
    role: a.role,
    joinedAt: a.joined_at,
  };
}

export function mapUserAuditEntry(payload: UserAuditEntryPayload): UserAuditEntry {
  return {
    id: payload.id,
    actorUserId: payload.actor_user_id,
    actorEmail: payload.actor_email,
    action: payload.action,
    resourceType: payload.resource_type,
    resourceId: payload.resource_id,
    occurredAt: payload.occurred_at,
    undoneAt: payload.undone_at,
  };
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
