import { QueryClient, QueryClientProvider } from '@tanstack/react-query';
import { renderHook, waitFor } from '@testing-library/react';
import type { ReactNode } from 'react';
import { afterEach, beforeEach, describe, expect, it, vi } from 'vitest';

import { clearAuthToken, writeAuthToken } from '@/lib/authToken';

import type { ListWorkersResponse, PairingCodeResponse, WorkerDetail } from '../types';
import { useAcceptFingerprint } from './useAcceptFingerprint';
import { useCreatePairingCode } from './useCreatePairingCode';
import { useRevokeWorker } from './useRevokeWorker';
import { useWorker } from './useWorker';
import { useWorkers } from './useWorkers';

function mockResponse(body: unknown, status = 200): Response {
  return new Response(body == null ? null : JSON.stringify(body), {
    status,
    headers: body == null ? {} : { 'content-type': 'application/json' },
  });
}

function createWrapper() {
  const queryClient = new QueryClient({
    defaultOptions: { queries: { retry: false }, mutations: { retry: false } },
  });
  const Wrapper = ({ children }: { children: ReactNode }) => (
    <QueryClientProvider client={queryClient}>{children}</QueryClientProvider>
  );
  return { queryClient, Wrapper };
}

const workerId = 'aaaaaaaa-aaaa-4aaa-8aaa-aaaaaaaaaaaa';

const sampleWorker = {
  id: workerId,
  name: 'projekt-a',
  hostLabel: 'srv-01.example.pl',
  rabbitmqUser: 'worker-abc',
  lastHeartbeatAt: '2026-11-21T13:00:00+00:00',
  isOnline: true,
  createdAt: '2026-11-21T12:00:00+00:00',
  updatedAt: '2026-11-21T13:00:00+00:00',
  fingerprintHashShort: 'abc123def456',
  fingerprintStatus: 'ok' as const,
};

describe('workers api hooks', () => {
  beforeEach(() => {
    writeAuthToken('test-token');
  });

  afterEach(() => {
    clearAuthToken();
    vi.restoreAllMocks();
  });

  it('useWorkers returns list of workers', async () => {
    const response: ListWorkersResponse = { workers: [sampleWorker] };
    const fetchSpy = vi.spyOn(globalThis, 'fetch').mockResolvedValue(mockResponse(response));
    const { Wrapper } = createWrapper();

    const { result } = renderHook(() => useWorkers(), { wrapper: Wrapper });

    await waitFor(() => expect(result.current.isSuccess).toBe(true));
    expect(result.current.data).toEqual(response);
    expect(fetchSpy).toHaveBeenCalledWith(expect.stringContaining('/api/workers'), expect.anything());
  });

  it('useWorker fetches detail when id provided', async () => {
    const detail: WorkerDetail = {
      ...sampleWorker,
      fingerprintHash: 'abc123def456' + 'f'.repeat(52),
      fingerprintMeta: { os: 'Linux' },
      fingerprintChangeHistory: [],
      hostInfo: { os: 'Linux', cpuCount: 8 },
    };
    vi.spyOn(globalThis, 'fetch').mockResolvedValue(mockResponse(detail));
    const { Wrapper } = createWrapper();

    const { result } = renderHook(() => useWorker(workerId), { wrapper: Wrapper });

    await waitFor(() => expect(result.current.isSuccess).toBe(true));
    expect(result.current.data?.id).toBe(workerId);
    expect(result.current.data?.hostInfo?.cpuCount).toBe(8);
  });

  it('useWorker stays disabled when id is null', () => {
    const fetchSpy = vi.spyOn(globalThis, 'fetch');
    const { Wrapper } = createWrapper();

    const { result } = renderHook(() => useWorker(null), { wrapper: Wrapper });

    expect(result.current.fetchStatus).toBe('idle');
    expect(fetchSpy).not.toHaveBeenCalled();
  });

  it('useCreatePairingCode posts to pairing-codes endpoint', async () => {
    const response: PairingCodeResponse = {
      code: 'ABCD-2345',
      expiresAt: '2026-11-21T14:00:00+00:00',
      pairCommand: 'curl -fsSL http://localhost:8000/install-worker.sh | bash -s -- --code ABCD-2345',
    };
    const fetchSpy = vi.spyOn(globalThis, 'fetch').mockResolvedValue(mockResponse(response, 201));
    const { Wrapper } = createWrapper();

    const { result } = renderHook(() => useCreatePairingCode(), { wrapper: Wrapper });

    const output = await result.current.mutateAsync({});

    expect(output).toEqual(response);
    expect(fetchSpy).toHaveBeenCalledWith(
      expect.stringContaining('/api/workers/pairing-codes'),
      expect.objectContaining({ method: 'POST' }),
    );
  });

  it('useRevokeWorker invalidates workers list on success', async () => {
    const fetchSpy = vi.spyOn(globalThis, 'fetch').mockResolvedValue(mockResponse(null, 204));
    const { queryClient, Wrapper } = createWrapper();
    const invalidateSpy = vi.spyOn(queryClient, 'invalidateQueries');

    const { result } = renderHook(() => useRevokeWorker(), { wrapper: Wrapper });

    await result.current.mutateAsync(workerId);

    expect(fetchSpy).toHaveBeenCalledWith(
      expect.stringContaining(`/api/workers/${workerId}`),
      expect.objectContaining({ method: 'DELETE' }),
    );
    expect(invalidateSpy).toHaveBeenCalledWith({ queryKey: ['workers', 'list'] });
  });

  it('useAcceptFingerprint invalidates list and detail on success', async () => {
    const fetchSpy = vi.spyOn(globalThis, 'fetch').mockResolvedValue(mockResponse(null, 204));
    const { queryClient, Wrapper } = createWrapper();
    const invalidateSpy = vi.spyOn(queryClient, 'invalidateQueries');

    const { result } = renderHook(() => useAcceptFingerprint(), { wrapper: Wrapper });

    await result.current.mutateAsync({
      id: workerId,
      fingerprintHash: 'a'.repeat(64),
      fingerprintMeta: { hostname: 'new' },
    });

    expect(fetchSpy).toHaveBeenCalledWith(
      expect.stringContaining(`/api/workers/${workerId}/fingerprint/accept`),
      expect.objectContaining({ method: 'POST' }),
    );
    expect(invalidateSpy).toHaveBeenCalledWith({ queryKey: ['workers', 'list'] });
    expect(invalidateSpy).toHaveBeenCalledWith({ queryKey: ['workers', 'detail', workerId] });
  });
});
