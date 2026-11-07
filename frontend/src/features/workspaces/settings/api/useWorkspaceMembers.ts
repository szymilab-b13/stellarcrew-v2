import { useMutation, useQuery, useQueryClient } from '@tanstack/react-query';

import type {
  AssignMemberInput,
  ChangeMemberRoleInput,
  RemoveMemberInput,
  WorkspaceMembersResponse,
} from '../types';
import {
  assignWorkspaceMember,
  changeWorkspaceMemberRole,
  fetchWorkspaceMembers,
  removeWorkspaceMember,
} from './membersApi';

export const workspaceMembersQueryKey = (workspaceId: string) =>
  ['workspaces', workspaceId, 'members'] as const;

export function useWorkspaceMembers(workspaceId: string | null) {
  return useQuery<WorkspaceMembersResponse>({
    queryKey: workspaceId
      ? workspaceMembersQueryKey(workspaceId)
      : ['workspaces', 'members', 'none'],
    queryFn: () => fetchWorkspaceMembers(workspaceId as string),
    enabled: workspaceId !== null,
    staleTime: 30_000,
  });
}

export function useAssignMember(workspaceId: string) {
  const queryClient = useQueryClient();
  return useMutation({
    mutationFn: (input: AssignMemberInput) => assignWorkspaceMember(input),
    onSuccess: () => {
      queryClient.invalidateQueries({ queryKey: workspaceMembersQueryKey(workspaceId) });
    },
  });
}

export function useChangeMemberRole(workspaceId: string) {
  const queryClient = useQueryClient();
  return useMutation({
    mutationFn: (input: ChangeMemberRoleInput) => changeWorkspaceMemberRole(input),
    onSuccess: () => {
      queryClient.invalidateQueries({ queryKey: workspaceMembersQueryKey(workspaceId) });
    },
  });
}

export function useRemoveMember(workspaceId: string) {
  const queryClient = useQueryClient();
  return useMutation({
    mutationFn: (input: RemoveMemberInput) => removeWorkspaceMember(input),
    onSuccess: () => {
      queryClient.invalidateQueries({ queryKey: workspaceMembersQueryKey(workspaceId) });
    },
  });
}
