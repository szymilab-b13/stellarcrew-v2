import { useMutation, useQuery, useQueryClient } from '@tanstack/react-query';

import {
  createWorkspace,
  deleteWorkspace,
  fetchWorkspaces,
  updateWorkspace,
} from './workspacesApi';
import type { Workspace, WorkspaceCreateInput, WorkspaceUpdateInput } from '../types';

export const workspacesQueryKey = ['workspaces'] as const;

export function useWorkspacesList() {
  return useQuery<Workspace[]>({
    queryKey: workspacesQueryKey,
    queryFn: fetchWorkspaces,
    staleTime: 30_000,
  });
}

export function useCreateWorkspace() {
  const queryClient = useQueryClient();
  return useMutation({
    mutationFn: (input: WorkspaceCreateInput) => createWorkspace(input),
    onSuccess: () => {
      queryClient.invalidateQueries({ queryKey: workspacesQueryKey });
    },
  });
}

export interface UpdateWorkspaceVariables {
  id: string;
  input: WorkspaceUpdateInput;
}

export function useUpdateWorkspace() {
  const queryClient = useQueryClient();
  return useMutation({
    mutationFn: ({ id, input }: UpdateWorkspaceVariables) => updateWorkspace(id, input),
    onSuccess: () => {
      queryClient.invalidateQueries({ queryKey: workspacesQueryKey });
    },
  });
}

export function useDeleteWorkspace() {
  const queryClient = useQueryClient();
  return useMutation({
    mutationFn: (id: string) => deleteWorkspace(id),
    onSuccess: () => {
      queryClient.invalidateQueries({ queryKey: workspacesQueryKey });
    },
  });
}
