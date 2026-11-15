import { useQuery } from '@tanstack/react-query';

import { fetchAgentTemplateByIdOrSlug, fetchAgentTemplates } from './agentTemplatesApi';
import type { AgentTemplate } from '../types';

export const agentTemplatesQueryKey = () => ['agent-templates'] as const;
export const agentTemplateQueryKey = (idOrSlug: string) => ['agent-template', idOrSlug] as const;

export function useAgentTemplates() {
  return useQuery<AgentTemplate[]>({
    queryKey: agentTemplatesQueryKey(),
    queryFn: fetchAgentTemplates,
    staleTime: 5 * 60_000,
  });
}

export function useAgentTemplate(idOrSlug: string | null) {
  return useQuery<AgentTemplate>({
    queryKey: idOrSlug ? agentTemplateQueryKey(idOrSlug) : ['agent-template', 'disabled'],
    queryFn: () => fetchAgentTemplateByIdOrSlug(idOrSlug as string),
    enabled: idOrSlug !== null,
    staleTime: 5 * 60_000,
  });
}
