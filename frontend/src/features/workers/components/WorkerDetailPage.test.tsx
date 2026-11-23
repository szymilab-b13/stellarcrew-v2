import { QueryClient, QueryClientProvider } from '@tanstack/react-query';
import { render, screen } from '@testing-library/react';
import type { ReactNode } from 'react';
import { MemoryRouter, Route, Routes } from 'react-router-dom';
import { afterEach, beforeEach, describe, expect, it, vi } from 'vitest';

import '@/i18n/i18n';
import { clearAuthToken, writeAuthToken } from '@/lib/authToken';

import type { WorkerDetail } from '../types';
import { WorkerDetailPage } from './WorkerDetailPage';

function mockResponse(body: unknown, status = 200): Response {
  return new Response(body == null ? null : JSON.stringify(body), {
    status,
    headers: { 'content-type': 'application/json' },
  });
}

function renderPage(id = 'aaaaaaaa-aaaa-4aaa-8aaa-aaaaaaaaaaaa') {
  const queryClient = new QueryClient({
    defaultOptions: { queries: { retry: false }, mutations: { retry: false } },
  });
  function Wrapper({ children }: { children: ReactNode }) {
    return (
      <QueryClientProvider client={queryClient}>
        <MemoryRouter initialEntries={[`/settings/workers/${id}`]}>
          <Routes>
            <Route path="/settings/workers/:id" element={children} />
            <Route path="/settings/workers" element={<div>list</div>} />
          </Routes>
        </MemoryRouter>
      </QueryClientProvider>
    );
  }
  return render(<WorkerDetailPage />, { wrapper: Wrapper });
}

const workerBase: WorkerDetail = {
  id: 'aaaaaaaa-aaaa-4aaa-8aaa-aaaaaaaaaaaa',
  name: 'projekt-a',
  hostLabel: 'srv-01.example.pl',
  rabbitmqUser: 'worker-abc',
  lastHeartbeatAt: '2026-11-21T13:00:00+00:00',
  isOnline: true,
  createdAt: '2026-11-21T12:00:00+00:00',
  updatedAt: '2026-11-21T13:00:00+00:00',
  fingerprintHashShort: 'abc123def456',
  fingerprintStatus: 'ok',
  fingerprintHash: 'abc123def456' + 'f'.repeat(52),
  fingerprintMeta: { hostname: 'srv-01' },
  fingerprintChangeHistory: [],
  hostInfo: {
    os: 'Linux',
    osRelease: '6.1.0',
    cpuModel: 'AMD EPYC',
    cpuCount: 16,
    memoryBytes: 32 * 1024 * 1024 * 1024,
    hostname: 'srv-01',
    kernelVersion: '6.1.0-generic',
    arch: 'x64',
  },
};

describe('WorkerDetailPage', () => {
  beforeEach(() => {
    writeAuthToken('test-token');
  });

  afterEach(() => {
    clearAuthToken();
    vi.restoreAllMocks();
  });

  it('shows worker name and hardware info', async () => {
    vi.spyOn(globalThis, 'fetch').mockResolvedValue(mockResponse(workerBase));

    renderPage();

    expect(await screen.findByRole('heading', { name: /projekt-a/ })).toBeInTheDocument();
    expect(screen.getByText(/Linux/)).toBeInTheDocument();
    expect(screen.getByText(/AMD EPYC/)).toBeInTheDocument();
    expect(screen.getByText(/32\.0 GB/)).toBeInTheDocument();
  });

  it('shows accept button only for the latest detected entry', async () => {
    vi.spyOn(globalThis, 'fetch').mockResolvedValue(
      mockResponse({
        ...workerBase,
        fingerprintStatus: 'warning',
        fingerprintChangeHistory: [
          {
            at: '2026-11-20T12:00:00+00:00',
            status: 'detected',
            previousHash: 'a'.repeat(64),
            previousMeta: null,
            newHash: 'b'.repeat(64),
            newMeta: { hostname: 'intermediate' },
          },
          {
            at: '2026-11-20T13:00:00+00:00',
            status: 'accepted',
            previousHash: 'a'.repeat(64),
            previousMeta: null,
            newHash: 'b'.repeat(64),
            newMeta: null,
          },
          {
            at: '2026-11-21T10:00:00+00:00',
            status: 'detected',
            previousHash: 'b'.repeat(64),
            previousMeta: null,
            newHash: 'c'.repeat(64),
            newMeta: { hostname: 'new' },
          },
        ],
      }),
    );

    renderPage();

    const acceptButtons = await screen.findAllByRole('button', { name: /Zaakceptuj|Accept/ });
    expect(acceptButtons).toHaveLength(1);
  });

  it('does not render the fingerprint warning banner when status is ok', async () => {
    vi.spyOn(globalThis, 'fetch').mockResolvedValue(mockResponse(workerBase));

    renderPage();

    await screen.findByRole('heading', { name: /projekt-a/ });
    expect(screen.queryByRole('alert')).not.toBeInTheDocument();
  });

  it('renders the fingerprint warning banner when status is warning', async () => {
    vi.spyOn(globalThis, 'fetch').mockResolvedValue(
      mockResponse({
        ...workerBase,
        fingerprintStatus: 'warning',
        fingerprintChangeHistory: [
          {
            at: '2026-11-21T10:00:00+00:00',
            status: 'detected',
            previousHash: 'a'.repeat(64),
            previousMeta: null,
            newHash: 'c'.repeat(64),
            newMeta: { hostname: 'new' },
          },
        ],
      }),
    );

    renderPage();

    const banner = await screen.findByRole('alert');
    expect(banner).toHaveTextContent(/Device fingerprint changed|Zmiana odcisku urządzenia/i);
  });

  it('shows error state when fetch fails', async () => {
    vi.spyOn(globalThis, 'fetch').mockResolvedValue(mockResponse({ message: 'nope' }, 500));

    renderPage();

    expect(
      await screen.findByText(/Could not load worker|Nie udało się załadować workera/i),
    ).toBeInTheDocument();
  });
});
