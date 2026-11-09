import { useQuery } from '@tanstack/react-query';

import { fetchProjectsByWorkspace } from './projectsApi';
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
