import { apiFetch } from '@/lib/apiClient';

import type {
  AssignMemberInput,
  ChangeMemberRoleInput,
  RemoveMemberInput,
  WorkspaceMembersResponse,
} from '../types';

export async function fetchWorkspaceMembers(
  workspaceId: string,
): Promise<WorkspaceMembersResponse> {
  return apiFetch<WorkspaceMembersResponse>(`/api/workspaces/${workspaceId}/members`);
}

export async function assignWorkspaceMember(input: AssignMemberInput): Promise<void> {
  await apiFetch<unknown>(
    `/api/user-management/users/${input.targetUserId}/workspace-assignments`,
    {
      method: 'POST',
      body: JSON.stringify({ workspaceId: input.workspaceId, role: input.role }),
    },
  );
}

export async function changeWorkspaceMemberRole(input: ChangeMemberRoleInput): Promise<void> {
  await apiFetch<unknown>(
    `/api/user-management/users/${input.targetUserId}/workspace-assignments/${input.workspaceId}`,
    {
      method: 'PATCH',
      body: JSON.stringify({ role: input.role }),
    },
  );
}

export async function removeWorkspaceMember(input: RemoveMemberInput): Promise<void> {
  await apiFetch<unknown>(
    `/api/user-management/users/${input.targetUserId}/workspace-assignments/${input.workspaceId}`,
    { method: 'DELETE' },
  );
}
