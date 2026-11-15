import { apiFetch } from '@/lib/apiClient';

import type { AgentTemplate } from '../types';

interface ListAgentTemplatesResponse {
  items: AgentTemplate[];
}

export async function fetchAgentTemplates(): Promise<AgentTemplate[]> {
  const response = await apiFetch<ListAgentTemplatesResponse>('/api/agent-templates');
  return response.items;
}

export async function fetchAgentTemplateByIdOrSlug(idOrSlug: string): Promise<AgentTemplate> {
  return apiFetch<AgentTemplate>(`/api/agent-templates/${idOrSlug}`);
}
