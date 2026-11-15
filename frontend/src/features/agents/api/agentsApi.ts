import { apiFetch } from '@/lib/apiClient';

import type { Agent } from '../types';

export interface CreateAgentInput {
  name: string;
  role: string;
  roleDescription: string;
  expertise: string;
  workflow: string;
  style: string;
  outputFormat: string;
  slug?: string;
}

export interface CreateAgentFromTemplateInput {
  templateSlug: string;
  name?: string;
  role?: string;
  roleDescription?: string;
  expertise?: string;
  workflow?: string;
  style?: string;
  outputFormat?: string;
  slug?: string;
}

export interface UpdateAgentInput {
  name?: string;
  role?: string;
  roleDescription?: string;
  expertise?: string;
  workflow?: string;
  style?: string;
  outputFormat?: string;
}

export async function fetchAgents(): Promise<Agent[]> {
  return apiFetch<Agent[]>('/api/agents');
}

export async function fetchAgentById(agentId: string): Promise<Agent> {
  return apiFetch<Agent>(`/api/agents/${agentId}`);
}

export async function createAgent(input: CreateAgentInput): Promise<Agent> {
  return apiFetch<Agent>('/api/agents', {
    method: 'POST',
    body: JSON.stringify(input),
  });
}

export async function createAgentFromTemplate(input: CreateAgentFromTemplateInput): Promise<Agent> {
  return apiFetch<Agent>('/api/agents/from-template', {
    method: 'POST',
    body: JSON.stringify(input),
  });
}

export async function updateAgent(agentId: string, input: UpdateAgentInput): Promise<Agent> {
  return apiFetch<Agent>(`/api/agents/${agentId}`, {
    method: 'PUT',
    body: JSON.stringify(input),
  });
}

export async function deleteAgent(agentId: string): Promise<void> {
  await apiFetch<void>(`/api/agents/${agentId}`, {
    method: 'DELETE',
  });
}
