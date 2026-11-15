import { QueryClient, QueryClientProvider } from '@tanstack/react-query';
import { renderHook, waitFor } from '@testing-library/react';
import type { ReactNode } from 'react';
import { afterEach, beforeEach, describe, expect, it, vi } from 'vitest';

import { clearAuthToken, writeAuthToken } from '@/lib/authToken';

import { useAgentTemplate, useAgentTemplates } from './useAgentTemplates';

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

const sampleTemplate = {
  id: '44444444-4444-4444-8444-444444444444',
  slug: 'maja',
  name: 'Maja',
  role: 'Project Manager',
  roleDescription: 'PM role',
  expertise: 'Coordination',
  workflow: 'Open briefing',
  style: 'Calm',
  outputFormat: 'TL;DR',
  createdAt: '2026-11-15T10:00:00+00:00',
  updatedAt: '2026-11-15T10:00:00+00:00',
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

describe('useAgentTemplates', () => {
  it('should request /api/agent-templates and unwrap items', async () => {
    vi.mocked(fetch).mockResolvedValueOnce(mockResponse({ items: [sampleTemplate] }));

    const { result } = renderHook(() => useAgentTemplates(), { wrapper: wrapper() });

    await waitFor(() => expect(result.current.isSuccess).toBe(true));
    expect(result.current.data).toEqual([sampleTemplate]);
    expect(vi.mocked(fetch).mock.calls[0]?.[0]).toBe('/api/agent-templates');
  });

  it('should surface 401 as an error', async () => {
    vi.mocked(fetch).mockResolvedValueOnce(mockResponse({ message: 'unauthorized' }, 401));

    const { result } = renderHook(() => useAgentTemplates(), { wrapper: wrapper() });

    await waitFor(() => expect(result.current.isError).toBe(true));
  });
});

describe('useAgentTemplate', () => {
  it('should request the single template endpoint by slug and return the entity', async () => {
    vi.mocked(fetch).mockResolvedValueOnce(mockResponse(sampleTemplate));

    const { result } = renderHook(() => useAgentTemplate('maja'), { wrapper: wrapper() });

    await waitFor(() => expect(result.current.isSuccess).toBe(true));
    expect(result.current.data).toEqual(sampleTemplate);
    expect(vi.mocked(fetch).mock.calls[0]?.[0]).toBe('/api/agent-templates/maja');
  });

  it('should stay disabled when idOrSlug is null', () => {
    const { result } = renderHook(() => useAgentTemplate(null), { wrapper: wrapper() });

    expect(result.current.isPending).toBe(true);
    expect(result.current.fetchStatus).toBe('idle');
    expect(vi.mocked(fetch)).not.toHaveBeenCalled();
  });
});
