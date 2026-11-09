import { useMutation, useQuery, useQueryClient } from '@tanstack/react-query';

import {
  createProject,
  deleteProject,
  fetchProjectById,
  fetchProjectsByWorkspace,
  updateProject,
  type CreateProjectInput,
  type UpdateProjectInput,
} from './projectsApi';
import type { Project } from '../types';

export const projectsQueryKey = (workspaceId: string) => ['projects', workspaceId] as const;
export const projectQueryKey = (projectId: string) => ['project', projectId] as const;

export function useProjectsList(workspaceId: string | null) {
  return useQuery<Project[]>({
    queryKey: workspaceId ? projectsQueryKey(workspaceId) : ['projects', 'disabled'],
    queryFn: () => fetchProjectsByWorkspace(workspaceId as string),
    enabled: workspaceId !== null,
    staleTime: 30_000,
  });
}

export function useProject(projectId: string | null) {
  return useQuery<Project>({
    queryKey: projectId ? projectQueryKey(projectId) : ['project', 'disabled'],
    queryFn: () => fetchProjectById(projectId as string),
    enabled: projectId !== null,
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

export interface UpdateProjectVariables {
  projectId: string;
  workspaceId: string;
  input: UpdateProjectInput;
}

export function useUpdateProject() {
  const queryClient = useQueryClient();
  return useMutation<Project, Error, UpdateProjectVariables>({
    mutationFn: ({ projectId, input }) => updateProject(projectId, input),
    onSuccess: (_project, variables) => {
      queryClient.invalidateQueries({ queryKey: projectsQueryKey(variables.workspaceId) });
      queryClient.invalidateQueries({ queryKey: projectQueryKey(variables.projectId) });
    },
  });
}

export interface DeleteProjectVariables {
  projectId: string;
  workspaceId: string;
}

export function useDeleteProject() {
  const queryClient = useQueryClient();
  return useMutation<void, Error, DeleteProjectVariables>({
    mutationFn: ({ projectId }) => deleteProject(projectId),
    onSuccess: (_void, variables) => {
      queryClient.invalidateQueries({ queryKey: projectsQueryKey(variables.workspaceId) });
      queryClient.invalidateQueries({ queryKey: projectQueryKey(variables.projectId) });
    },
  });
}
