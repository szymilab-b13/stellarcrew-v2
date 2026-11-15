import { useMutation, useQuery, useQueryClient } from '@tanstack/react-query';

import {
  createAgent,
  createAgentFromTemplate,
  deleteAgent,
  fetchAgentById,
  fetchAgents,
  updateAgent,
  type CreateAgentFromTemplateInput,
  type CreateAgentInput,
  type UpdateAgentInput,
} from './agentsApi';
import type { Agent } from '../types';

export const agentsQueryKey = () => ['agents'] as const;
export const agentQueryKey = (agentId: string) => ['agent', agentId] as const;

export function useAgentsList() {
  return useQuery<Agent[]>({
    queryKey: agentsQueryKey(),
    queryFn: fetchAgents,
    staleTime: 30_000,
  });
}

export function useAgent(agentId: string | null) {
  return useQuery<Agent>({
    queryKey: agentId ? agentQueryKey(agentId) : ['agent', 'disabled'],
    queryFn: () => fetchAgentById(agentId as string),
    enabled: agentId !== null,
    staleTime: 30_000,
  });
}

export function useCreateAgent() {
  const queryClient = useQueryClient();
  return useMutation<Agent, Error, CreateAgentInput>({
    mutationFn: (input) => createAgent(input),
    onSuccess: () => {
      queryClient.invalidateQueries({ queryKey: agentsQueryKey() });
    },
  });
}

export function useCreateAgentFromTemplate() {
  const queryClient = useQueryClient();
  return useMutation<Agent, Error, CreateAgentFromTemplateInput>({
    mutationFn: (input) => createAgentFromTemplate(input),
    onSuccess: () => {
      queryClient.invalidateQueries({ queryKey: agentsQueryKey() });
    },
  });
}

export interface UpdateAgentVariables {
  agentId: string;
  input: UpdateAgentInput;
}

export function useUpdateAgent() {
  const queryClient = useQueryClient();
  return useMutation<Agent, Error, UpdateAgentVariables>({
    mutationFn: ({ agentId, input }) => updateAgent(agentId, input),
    onSuccess: (_agent, variables) => {
      queryClient.invalidateQueries({ queryKey: agentsQueryKey() });
      queryClient.invalidateQueries({ queryKey: agentQueryKey(variables.agentId) });
    },
  });
}

export interface DeleteAgentVariables {
  agentId: string;
}

export function useDeleteAgent() {
  const queryClient = useQueryClient();
  return useMutation<void, Error, DeleteAgentVariables>({
    mutationFn: ({ agentId }) => deleteAgent(agentId),
    onSuccess: (_void, variables) => {
      queryClient.invalidateQueries({ queryKey: agentsQueryKey() });
      queryClient.invalidateQueries({ queryKey: agentQueryKey(variables.agentId) });
    },
  });
}
