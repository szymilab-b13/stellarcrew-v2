import { useMutation, useQuery, useQueryClient } from '@tanstack/react-query';

import { createProject, fetchProjectsByWorkspace, type CreateProjectInput } from './projectsApi';
import type { Project } from '../types';

export const projectsQueryKey = (workspaceId: string) => ['projects', workspaceId] as const;

export function useProjectsList(workspaceId: string | null) {
  return useQuery<Project[]>({
    queryKey: workspaceId ? projectsQueryKey(workspaceId) : ['projects', 'disabled'],
    queryFn: () => fetchProjectsByWorkspace(workspaceId as string),
    enabled: workspaceId !== null,
    staleTime: 30_000,
  });
}

export interface CreateProjectVariables {
  workspaceId: string;
  input: CreateProjectInput;
}

export function useCreateProject() {
  const queryClient = useQueryClient();
  return useMutation<Project, Error, CreateProjectVariables>({
    mutationFn: ({ workspaceId, input }) => createProject(workspaceId, input),
    onSuccess: (_project, variables) => {
      queryClient.invalidateQueries({ queryKey: projectsQueryKey(variables.workspaceId) });
    },
  });
}
