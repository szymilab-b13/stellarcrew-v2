import { QueryClient, QueryClientProvider } from '@tanstack/react-query';
import { render, screen } from '@testing-library/react';
import type { ReactNode } from 'react';
import { MemoryRouter, Route, Routes } from 'react-router-dom';
import { afterEach, beforeEach, describe, expect, it, vi } from 'vitest';

import '@/i18n/i18n';
import { clearAuthToken, writeAuthToken } from '@/lib/authToken';

import type { Worker } from '../types';
import { WorkersListPage } from './WorkersListPage';

function mockResponse(body: unknown, status = 200): Response {
  return new Response(body == null ? null : JSON.stringify(body), {
    status,
    headers: { 'content-type': 'application/json' },
  });
}

function renderPage() {
  const queryClient = new QueryClient({
    defaultOptions: { queries: { retry: false }, mutations: { retry: false } },
  });
  function Wrapper({ children }: { children: ReactNode }) {
    return (
      <QueryClientProvider client={queryClient}>
        <MemoryRouter initialEntries={['/settings/workers']}>
          <Routes>
            <Route path="/settings/workers" element={children} />
            <Route path="/settings/workers/:id" element={<div>worker detail</div>} />
          </Routes>
        </MemoryRouter>
      </QueryClientProvider>
    );
  }
  return render(<WorkersListPage />, { wrapper: Wrapper });
}

const workerBase: Worker = {
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
};

describe('WorkersListPage', () => {
  beforeEach(() => {
    writeAuthToken('test-token');
  });

  afterEach(() => {
    clearAuthToken();
    vi.restoreAllMocks();
  });

  it('shows loading state while fetching', () => {
    vi.spyOn(globalThis, 'fetch').mockImplementation(() => new Promise(() => {}));

    renderPage();

    expect(screen.getByText(/Ładowanie workerów|Loading workers/i)).toBeInTheDocument();
  });

  it('renders worker cards when data arrives', async () => {
    vi.spyOn(globalThis, 'fetch').mockResolvedValue(
      mockResponse({
        workers: [
          workerBase,
          { ...workerBase, id: 'b', name: 'projekt-b', fingerprintStatus: 'warning' },
        ],
      }),
    );

    renderPage();

    expect(await screen.findByRole('link', { name: 'projekt-a' })).toBeInTheDocument();
    expect(screen.getByRole('link', { name: 'projekt-b' })).toBeInTheDocument();
    expect(screen.getByText(/Device changed|Zmiana urządzenia/i)).toBeInTheDocument();
  });

  it('shows empty state when no workers exist', async () => {
    vi.spyOn(globalThis, 'fetch').mockResolvedValue(mockResponse({ workers: [] }));

    renderPage();

    expect(
      await screen.findByText(/You don't have any workers yet|Nie masz jeszcze żadnego workera/i),
    ).toBeInTheDocument();
  });

  it('shows error state when fetch fails', async () => {
    vi.spyOn(globalThis, 'fetch').mockResolvedValue(mockResponse({ message: 'nope' }, 500));

    renderPage();

    expect(
      await screen.findByText(/Could not load workers|Nie udało się załadować workerów/i),
    ).toBeInTheDocument();
  });
});
