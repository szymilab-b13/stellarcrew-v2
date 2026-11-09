import { apiFetch } from '@/lib/apiClient';

import type { Project } from '../types';

interface ListProjectsResponse {
  items: Project[];
}

export interface CreateProjectInput {
  name: string;
  slug?: string;
  description?: string | null;
}

export interface UpdateProjectInput {
  name?: string;
  slug?: string;
  description?: string | null;
}

export async function fetchProjectsByWorkspace(workspaceId: string): Promise<Project[]> {
  const response = await apiFetch<ListProjectsResponse>(`/api/workspaces/${workspaceId}/projects`);
  return response.items;
}

export async function fetchProjectById(projectId: string): Promise<Project> {
  return apiFetch<Project>(`/api/projects/${projectId}`);
}

export async function createProject(
  workspaceId: string,
  input: CreateProjectInput,
): Promise<Project> {
  return apiFetch<Project>(`/api/workspaces/${workspaceId}/projects`, {
    method: 'POST',
    body: JSON.stringify(input),
  });
}

export async function updateProject(
  projectId: string,
  input: UpdateProjectInput,
): Promise<Project> {
  return apiFetch<Project>(`/api/projects/${projectId}`, {
    method: 'PUT',
    body: JSON.stringify(input),
  });
}

export async function deleteProject(projectId: string): Promise<void> {
  await apiFetch<void>(`/api/projects/${projectId}`, {
    method: 'DELETE',
  });
}
