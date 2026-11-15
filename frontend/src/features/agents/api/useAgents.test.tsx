import { QueryClient, QueryClientProvider } from '@tanstack/react-query';
import { renderHook, waitFor } from '@testing-library/react';
import type { ReactNode } from 'react';
import { afterEach, beforeEach, describe, expect, it, vi } from 'vitest';

import { clearAuthToken, writeAuthToken } from '@/lib/authToken';

import {
  useAgent,
  useAgentsList,
  useCreateAgent,
  useCreateAgentFromTemplate,
  useDeleteAgent,
  useUpdateAgent,
} from './useAgents';

function mockResponse(body: unknown, status = 200): Response {
  return new Response(body == null ? null : JSON.stringify(body), {
    status,
    headers: { 'content-type': 'application/json' },
  });
}

function wrapper() {
  const queryClient = new QueryClient({
    defaultOptions: { queries: { retry: false }, mutations: { retry: false } },
  });
  return function Wrapper({ children }: { children: ReactNode }) {
    return <QueryClientProvider client={queryClient}>{children}</QueryClientProvider>;
  };
}

const sampleAgent = {
  id: '11111111-1111-4111-8111-111111111111',
  organizationId: '22222222-2222-4222-8222-222222222222',
  ownerUserId: '33333333-3333-4333-8333-333333333333',
  sourceTemplateId: null,
  slug: 'maja',
  name: 'Maja',
  role: 'Project Manager',
  roleDescription: 'PM role',
  expertise: 'Coordination',
  workflow: 'Open briefing',
  style: 'Calm',
  outputFormat: 'TL;DR',
  isActive: true,
  createdAt: '2026-11-15T14:00:00+00:00',
  updatedAt: '2026-11-15T14:00:00+00:00',
};

beforeEach(() => {
  writeAuthToken('test-token');
  vi.stubGlobal('fetch', vi.fn());
});

afterEach(() => {
  clearAuthToken();
  vi.unstubAllGlobals();
  vi.restoreAllMocks();
});

describe('useAgentsList', () => {
  it('should request /api/agents and return the array', async () => {
    vi.mocked(fetch).mockResolvedValueOnce(mockResponse([sampleAgent]));

    const { result } = renderHook(() => useAgentsList(), { wrapper: wrapper() });

    await waitFor(() => expect(result.current.isSuccess).toBe(true));
    expect(result.current.data).toEqual([sampleAgent]);
    expect(vi.mocked(fetch).mock.calls[0]?.[0]).toBe('/api/agents');
  });

  it('should surface 401 as an error', async () => {
    vi.mocked(fetch).mockResolvedValueOnce(mockResponse({ message: 'unauthorized' }, 401));

    const { result } = renderHook(() => useAgentsList(), { wrapper: wrapper() });

    await waitFor(() => expect(result.current.isError).toBe(true));
  });
});

describe('useAgent', () => {
  it('should request the single agent endpoint and return the entity', async () => {
    vi.mocked(fetch).mockResolvedValueOnce(mockResponse(sampleAgent));

    const { result } = renderHook(() => useAgent(sampleAgent.id), { wrapper: wrapper() });

    await waitFor(() => expect(result.current.isSuccess).toBe(true));
    expect(result.current.data).toEqual(sampleAgent);
    expect(vi.mocked(fetch).mock.calls[0]?.[0]).toBe(`/api/agents/${sampleAgent.id}`);
  });

  it('should stay disabled when agentId is null', () => {
    const { result } = renderHook(() => useAgent(null), { wrapper: wrapper() });

    expect(result.current.isPending).toBe(true);
    expect(result.current.fetchStatus).toBe('idle');
    expect(vi.mocked(fetch)).not.toHaveBeenCalled();
  });
});

describe('useCreateAgent', () => {
  it('should POST /api/agents with the full 5-section payload and return the created agent', async () => {
    vi.mocked(fetch).mockResolvedValueOnce(mockResponse(sampleAgent, 201));

    const { result } = renderHook(() => useCreateAgent(), { wrapper: wrapper() });
    result.current.mutate({
      name: 'Maja',
      role: 'Project Manager',
      roleDescription: 'PM role',
      expertise: 'Coordination',
      workflow: 'Open briefing',
      style: 'Calm',
      outputFormat: 'TL;DR',
    });

    await waitFor(() => expect(result.current.isSuccess).toBe(true));
    expect(result.current.data).toEqual(sampleAgent);
    expect(vi.mocked(fetch).mock.calls[0]?.[0]).toBe('/api/agents');
    const init = vi.mocked(fetch).mock.calls[0]?.[1] as RequestInit;
    expect(init.method).toBe('POST');
    expect(JSON.parse(init.body as string)).toEqual({
      name: 'Maja',
      role: 'Project Manager',
      roleDescription: 'PM role',
      expertise: 'Coordination',
      workflow: 'Open briefing',
      style: 'Calm',
      outputFormat: 'TL;DR',
    });
  });

  it('should surface 409 as an error', async () => {
    vi.mocked(fetch).mockResolvedValueOnce(mockResponse({ code: 'AGENT_LIMIT_REACHED' }, 409));

    const { result } = renderHook(() => useCreateAgent(), { wrapper: wrapper() });
    result.current.mutate({
      name: 'Maja',
      role: 'PM',
      roleDescription: '',
      expertise: '',
      workflow: '',
      style: '',
      outputFormat: '',
    });

    await waitFor(() => expect(result.current.isError).toBe(true));
  });
});

describe('useCreateAgentFromTemplate', () => {
  it('should POST /api/agents/from-template with templateSlug and return the created agent', async () => {
    vi.mocked(fetch).mockResolvedValueOnce(mockResponse(sampleAgent, 201));

    const { result } = renderHook(() => useCreateAgentFromTemplate(), { wrapper: wrapper() });
    result.current.mutate({ templateSlug: 'maja' });

    await waitFor(() => expect(result.current.isSuccess).toBe(true));
    expect(result.current.data).toEqual(sampleAgent);
    expect(vi.mocked(fetch).mock.calls[0]?.[0]).toBe('/api/agents/from-template');
    const init = vi.mocked(fetch).mock.calls[0]?.[1] as RequestInit;
    expect(init.method).toBe('POST');
    expect(JSON.parse(init.body as string)).toEqual({ templateSlug: 'maja' });
  });

  it('should surface 404 as an error for unknown template', async () => {
    vi.mocked(fetch).mockResolvedValueOnce(mockResponse({ message: 'not found' }, 404));

    const { result } = renderHook(() => useCreateAgentFromTemplate(), { wrapper: wrapper() });
    result.current.mutate({ templateSlug: 'unknown' });

    await waitFor(() => expect(result.current.isError).toBe(true));
  });
});

describe('useUpdateAgent', () => {
  it('should PUT /api/agents/:id with a partial payload', async () => {
    vi.mocked(fetch).mockResolvedValueOnce(mockResponse(sampleAgent));

    const { result } = renderHook(() => useUpdateAgent(), { wrapper: wrapper() });
    result.current.mutate({
      agentId: sampleAgent.id,
      input: { name: 'Renamed' },
    });

    await waitFor(() => expect(result.current.isSuccess).toBe(true));
    expect(vi.mocked(fetch).mock.calls[0]?.[0]).toBe(`/api/agents/${sampleAgent.id}`);
    const init = vi.mocked(fetch).mock.calls[0]?.[1] as RequestInit;
    expect(init.method).toBe('PUT');
    expect(JSON.parse(init.body as string)).toEqual({ name: 'Renamed' });
  });
});

describe('useDeleteAgent', () => {
  it('should DELETE /api/agents/:id and resolve on 204', async () => {
    vi.mocked(fetch).mockResolvedValueOnce(new Response(null, { status: 204 }));

    const { result } = renderHook(() => useDeleteAgent(), { wrapper: wrapper() });
    result.current.mutate({ agentId: sampleAgent.id });

    await waitFor(() => expect(result.current.isSuccess).toBe(true));
    expect(vi.mocked(fetch).mock.calls[0]?.[0]).toBe(`/api/agents/${sampleAgent.id}`);
    expect((vi.mocked(fetch).mock.calls[0]?.[1] as RequestInit).method).toBe('DELETE');
  });
});
