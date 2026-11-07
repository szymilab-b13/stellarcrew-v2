export type WorkspaceRole = 'owner' | 'admin' | 'member' | 'guest';

export const ASSIGNABLE_ROLES: readonly WorkspaceRole[] = ['admin', 'member', 'guest'];

export function canManageMembers(role: WorkspaceRole | null): boolean {
  return role === 'owner' || role === 'admin';
}

export interface WorkspaceMember {
  userId: string;
  email: string;
  role: WorkspaceRole;
  joinedAt: string;
}

export interface WorkspaceMembersResponse {
  items: WorkspaceMember[];
}

export interface AssignMemberInput {
  workspaceId: string;
  targetUserId: string;
  role: Exclude<WorkspaceRole, 'owner'>;
}

export interface ChangeMemberRoleInput {
  workspaceId: string;
  targetUserId: string;
  role: Exclude<WorkspaceRole, 'owner'>;
}

export interface RemoveMemberInput {
  workspaceId: string;
  targetUserId: string;
}
