import { QueryClient, QueryClientProvider } from '@tanstack/react-query';
import { renderHook, waitFor } from '@testing-library/react';
import type { ReactNode } from 'react';
import { afterEach, beforeEach, describe, expect, it, vi } from 'vitest';

import { clearAuthToken, writeAuthToken } from '@/lib/authToken';

import { useDeleteProject, useProject, useProjectsList, useUpdateProject } from './useProjects';

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

describe('useProjectsList', () => {
  beforeEach(() => {
    writeAuthToken('test-token');
    vi.stubGlobal('fetch', vi.fn());
  });

  afterEach(() => {
    clearAuthToken();
    vi.unstubAllGlobals();
    vi.restoreAllMocks();
  });

  it('should request the workspace projects endpoint and unwrap items', async () => {
    const project = {
      id: '11111111-1111-4111-8111-111111111111',
      workspaceId: 'ws-1',
      name: 'Talent Bridge',
      slug: 'talent-bridge',
      description: 'HR integration',
      createdAt: '2026-11-09T10:00:00+00:00',
      updatedAt: '2026-11-09T10:00:00+00:00',
    };
    vi.mocked(fetch).mockResolvedValueOnce(mockResponse({ items: [project] }));

    const { result } = renderHook(() => useProjectsList('ws-1'), { wrapper: wrapper() });

    await waitFor(() => expect(result.current.isSuccess).toBe(true));
    expect(result.current.data).toEqual([project]);
    expect(vi.mocked(fetch).mock.calls[0]?.[0]).toBe('/api/workspaces/ws-1/projects');
  });

  it('should stay disabled when workspaceId is null', () => {
    const { result } = renderHook(() => useProjectsList(null), { wrapper: wrapper() });

    expect(result.current.isPending).toBe(true);
    expect(result.current.fetchStatus).toBe('idle');
    expect(vi.mocked(fetch)).not.toHaveBeenCalled();
  });
});

describe('useProject', () => {
  beforeEach(() => {
    writeAuthToken('test-token');
    vi.stubGlobal('fetch', vi.fn());
  });

  afterEach(() => {
    clearAuthToken();
    vi.unstubAllGlobals();
    vi.restoreAllMocks();
  });

  it('should request the single project endpoint and return the entity', async () => {
    const project = {
      id: '33333333-3333-4333-8333-333333333333',
      workspaceId: 'ws-1',
      name: 'Talent Bridge',
      slug: 'talent-bridge',
      description: 'HR integration',
      createdAt: '2026-11-09T10:00:00+00:00',
      updatedAt: '2026-11-09T10:00:00+00:00',
    };
    vi.mocked(fetch).mockResolvedValueOnce(mockResponse(project));

    const { result } = renderHook(() => useProject(project.id), { wrapper: wrapper() });

    await waitFor(() => expect(result.current.isSuccess).toBe(true));
    expect(result.current.data).toEqual(project);
    expect(vi.mocked(fetch).mock.calls[0]?.[0]).toBe(`/api/projects/${project.id}`);
  });

  it('should stay disabled when projectId is null', () => {
    const { result } = renderHook(() => useProject(null), { wrapper: wrapper() });

    expect(result.current.isPending).toBe(true);
    expect(result.current.fetchStatus).toBe('idle');
    expect(vi.mocked(fetch)).not.toHaveBeenCalled();
  });
});

describe('useUpdateProject', () => {
  beforeEach(() => {
    writeAuthToken('test-token');
    vi.stubGlobal('fetch', vi.fn());
  });

  afterEach(() => {
    clearAuthToken();
    vi.unstubAllGlobals();
    vi.restoreAllMocks();
  });

  it('should PUT to /api/projects/:id with partial payload and return the updated entity', async () => {
    const project = {
      id: '44444444-4444-4444-8444-444444444444',
      workspaceId: 'ws-1',
      name: 'Renamed',
      slug: 'renamed',
      description: null,
      createdAt: '2026-11-09T10:00:00+00:00',
      updatedAt: '2026-11-09T11:00:00+00:00',
    };
    vi.mocked(fetch).mockResolvedValueOnce(mockResponse(project));

    const { result } = renderHook(() => useUpdateProject(), { wrapper: wrapper() });
    result.current.mutate({
      projectId: project.id,
      workspaceId: 'ws-1',
      input: { name: 'Renamed', slug: 'renamed', description: null },
    });

    await waitFor(() => expect(result.current.isSuccess).toBe(true));
    expect(result.current.data).toEqual(project);
    expect(vi.mocked(fetch).mock.calls[0]?.[0]).toBe(`/api/projects/${project.id}`);
    const init = vi.mocked(fetch).mock.calls[0]?.[1] as RequestInit;
    expect(init.method).toBe('PUT');
    expect(JSON.parse(init.body as string)).toEqual({
      name: 'Renamed',
      slug: 'renamed',
      description: null,
    });
  });
});

describe('useDeleteProject', () => {
  beforeEach(() => {
    writeAuthToken('test-token');
    vi.stubGlobal('fetch', vi.fn());
  });

  afterEach(() => {
    clearAuthToken();
    vi.unstubAllGlobals();
    vi.restoreAllMocks();
  });

  it('should DELETE to /api/projects/:id and resolve to undefined on 204', async () => {
    vi.mocked(fetch).mockResolvedValueOnce(new Response(null, { status: 204 }));

    const { result } = renderHook(() => useDeleteProject(), { wrapper: wrapper() });
    result.current.mutate({
      projectId: '55555555-5555-4555-8555-555555555555',
      workspaceId: 'ws-1',
    });

    await waitFor(() => expect(result.current.isSuccess).toBe(true));
    expect(vi.mocked(fetch).mock.calls[0]?.[0]).toBe(
      '/api/projects/55555555-5555-4555-8555-555555555555',
    );
    expect((vi.mocked(fetch).mock.calls[0]?.[1] as RequestInit).method).toBe('DELETE');
  });
});
