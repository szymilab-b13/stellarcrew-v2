import { apiFetch } from '@/lib/apiClient';

import type { Workspace, WorkspaceCreateInput, WorkspaceUpdateInput } from '../types';

export async function fetchWorkspaces(): Promise<Workspace[]> {
  return apiFetch<Workspace[]>('/api/workspaces');
}

export async function createWorkspace(input: WorkspaceCreateInput): Promise<Workspace> {
  return apiFetch<Workspace>('/api/workspaces', {
    method: 'POST',
    body: JSON.stringify(input),
  });
}

export async function updateWorkspace(id: string, input: WorkspaceUpdateInput): Promise<Workspace> {
  return apiFetch<Workspace>(`/api/workspaces/${id}`, {
    method: 'PATCH',
    body: JSON.stringify(input),
  });
}

export async function deleteWorkspace(id: string): Promise<void> {
  await apiFetch<unknown>(`/api/workspaces/${id}`, { method: 'DELETE' });
}
