import { apiFetch } from '@/lib/apiClient';

import type { Project } from '../types';

interface ListProjectsResponse {
  items: Project[];
}

export async function fetchProjectsByWorkspace(workspaceId: string): Promise<Project[]> {
  const response = await apiFetch<ListProjectsResponse>(`/api/workspaces/${workspaceId}/projects`);
  return response.items;
}
