import { QueryClient, QueryClientProvider } from '@tanstack/react-query';
import { renderHook, waitFor } from '@testing-library/react';
import type { ReactNode } from 'react';
import { afterEach, beforeEach, describe, expect, it, vi } from 'vitest';

import { clearAuthToken, writeAuthToken } from '@/lib/authToken';

import { useMyOrganization } from './useMyOrganization';

function mockResponse(status: number, body: unknown = null): Response {
  return new Response(body === null ? null : JSON.stringify(body), {
    status,
    headers: { 'content-type': 'application/json' },
  });
}

function wrapper() {
  const client = new QueryClient({ defaultOptions: { queries: { retry: false } } });
  return function Wrapper({ children }: { children: ReactNode }) {
    return <QueryClientProvider client={client}>{children}</QueryClientProvider>;
  };
}

describe('useMyOrganization', () => {
  beforeEach(() => {
    writeAuthToken('test-token');
    vi.stubGlobal('fetch', vi.fn());
  });

  afterEach(() => {
    clearAuthToken();
    vi.unstubAllGlobals();
    vi.restoreAllMocks();
  });

  it('should return the organization payload when the API responds 200', async () => {
    vi.mocked(fetch).mockResolvedValueOnce(
      mockResponse(200, {
        id: 'abcd1234-aaaa-bbbb-cccc-111122223333',
        name: "Tomek's Organization",
        slug: 'tomeks-organization',
        ownerUserId: '00000000-0000-0000-0000-000000000001',
        createdAt: '2026-11-10T10:00:00+00:00',
        updatedAt: '2026-11-10T10:00:00+00:00',
      }),
    );

    const { result } = renderHook(() => useMyOrganization(), { wrapper: wrapper() });

    await waitFor(() => expect(result.current.isSuccess).toBe(true));
    expect(result.current.data?.id).toBe('abcd1234-aaaa-bbbb-cccc-111122223333');
    expect(result.current.data?.name).toBe("Tomek's Organization");
  });

  it('should return null when the API responds 404', async () => {
    vi.mocked(fetch).mockResolvedValueOnce(
      mockResponse(404, { error: 'User does not own an organization.' }),
    );

    const { result } = renderHook(() => useMyOrganization(), { wrapper: wrapper() });

    await waitFor(() => expect(result.current.isSuccess).toBe(true));
    expect(result.current.data).toBeNull();
  });
});
