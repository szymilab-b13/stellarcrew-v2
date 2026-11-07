import { QueryClient, QueryClientProvider } from '@tanstack/react-query';
import { renderHook, waitFor } from '@testing-library/react';
import type { ReactNode } from 'react';
import { afterEach, beforeEach, describe, expect, it, vi } from 'vitest';

import { clearAuthToken, writeAuthToken } from '@/lib/authToken';

import { useUsersList } from './useUsers';

interface MockResponseInit {
  status?: number;
  body?: unknown;
}

function mockResponse({ status = 200, body = null }: MockResponseInit = {}): Response {
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

describe('useUsersList', () => {
  beforeEach(() => {
    writeAuthToken('test-token');
    vi.stubGlobal('fetch', vi.fn());
  });

  afterEach(() => {
    clearAuthToken();
    vi.unstubAllGlobals();
    vi.restoreAllMocks();
  });

  it('should request the list endpoint without query params when filters are empty', async () => {
    vi.mocked(fetch).mockResolvedValueOnce(
      mockResponse({ body: { items: [], total: 0, page: 1, per_page: 20 } }),
    );

    const { result } = renderHook(() => useUsersList({}), { wrapper: wrapper() });

    await waitFor(() => expect(result.current.isSuccess).toBe(true));
    expect(vi.mocked(fetch).mock.calls[0]?.[0]).toBe('/api/user-management/users');
  });

  it('should pass the q filter as a query parameter', async () => {
    vi.mocked(fetch).mockResolvedValueOnce(
      mockResponse({ body: { items: [], total: 0, page: 1, per_page: 20 } }),
    );

    const { result } = renderHook(() => useUsersList({ q: 'admin' }), { wrapper: wrapper() });

    await waitFor(() => expect(result.current.isSuccess).toBe(true));
    expect(vi.mocked(fetch).mock.calls[0]?.[0]).toBe('/api/user-management/users?q=admin');
  });

  it('should map snake_case payload to camelCase items', async () => {
    vi.mocked(fetch).mockResolvedValueOnce(
      mockResponse({
        body: {
          items: [
            {
              id: 'abc',
              email: 'user@example.com',
              is_super_admin: true,
              workspace_count: 3,
              created_at: '2026-11-07T10:00:00+00:00',
              deactivated_at: null,
            },
          ],
          total: 1,
          page: 1,
          per_page: 20,
        },
      }),
    );

    const { result } = renderHook(() => useUsersList({}), { wrapper: wrapper() });

    await waitFor(() => expect(result.current.isSuccess).toBe(true));
    expect(result.current.data?.items[0]).toMatchObject({
      id: 'abc',
      email: 'user@example.com',
      isSuperAdmin: true,
      workspaceCount: 3,
      deactivatedAt: null,
    });
  });
});
