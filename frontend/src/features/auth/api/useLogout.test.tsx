import { QueryClient, QueryClientProvider } from '@tanstack/react-query';
import { act, renderHook } from '@testing-library/react';
import type { ReactNode } from 'react';
import { afterEach, beforeEach, describe, expect, it, vi } from 'vitest';

import { writeAuthToken, clearAuthToken } from '@/lib/authToken';
import { useCurrentWorkspace } from '@/features/workspaces/state/useCurrentWorkspace';

import { useLogout } from './useLogout';

function mockResponse(status = 204): Response {
  return new Response(null, { status });
}

function renderLogout() {
  const queryClient = new QueryClient({
    defaultOptions: { queries: { retry: false }, mutations: { retry: false } },
  });

  function Wrapper({ children }: { children: ReactNode }) {
    return <QueryClientProvider client={queryClient}>{children}</QueryClientProvider>;
  }

  return renderHook(() => useLogout(), { wrapper: Wrapper });
}

describe('useLogout', () => {
  beforeEach(() => {
    writeAuthToken('test-token');
    window.localStorage.removeItem('sc-current-workspace');
    useCurrentWorkspace.setState({ currentWorkspaceId: null });
    vi.stubGlobal('fetch', vi.fn());
  });

  afterEach(() => {
    clearAuthToken();
    window.localStorage.removeItem('sc-current-workspace');
    useCurrentWorkspace.setState({ currentWorkspaceId: null });
    vi.unstubAllGlobals();
    vi.restoreAllMocks();
  });

  it('should resetCurrentWorkspaceWhenLogoutSucceeds', async () => {
    useCurrentWorkspace.getState().setCurrentWorkspaceId('some-ws-id');
    vi.mocked(fetch).mockResolvedValueOnce(mockResponse(204));

    const { result } = renderLogout();
    await act(async () => {
      await result.current.mutateAsync();
    });

    expect(useCurrentWorkspace.getState().currentWorkspaceId).toBeNull();
  });

  it('should resetCurrentWorkspaceEvenWhenLogoutRequestFails', async () => {
    useCurrentWorkspace.getState().setCurrentWorkspaceId('some-ws-id');
    vi.mocked(fetch).mockRejectedValueOnce(new Error('network down'));

    const { result } = renderLogout();
    await act(async () => {
      try {
        await result.current.mutateAsync();
      } catch {
        // mutation is expected to reject; onSettled still runs.
      }
    });

    expect(useCurrentWorkspace.getState().currentWorkspaceId).toBeNull();
  });
});
