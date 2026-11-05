import { QueryClient, QueryClientProvider } from '@tanstack/react-query';
import { render, screen } from '@testing-library/react';
import type { ReactNode } from 'react';
import { MemoryRouter, Route, Routes } from 'react-router-dom';
import { afterEach, beforeEach, describe, expect, it, vi } from 'vitest';

import { HomePage } from './HomePage';

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

function renderAtRoot() {
  const queryClient = new QueryClient({
    defaultOptions: { queries: { retry: false }, mutations: { retry: false } },
  });

  function Wrapper({ children }: { children: ReactNode }) {
    return (
      <QueryClientProvider client={queryClient}>
        <MemoryRouter initialEntries={['/']}>
          <Routes>
            <Route path="/" element={children} />
            <Route path="/install" element={<p>install page stub</p>} />
          </Routes>
        </MemoryRouter>
      </QueryClientProvider>
    );
  }

  return render(<HomePage />, { wrapper: Wrapper });
}

describe('HomePage', () => {
  beforeEach(() => {
    vi.stubGlobal('fetch', vi.fn());
  });

  afterEach(() => {
    vi.unstubAllGlobals();
    vi.restoreAllMocks();
  });

  it('should redirect to /install when the system is not installed', async () => {
    vi.mocked(fetch).mockResolvedValueOnce(mockResponse({ body: { is_installed: false } }));

    renderAtRoot();

    expect(await screen.findByText('install page stub')).toBeInTheDocument();
  });

  it('should render the homepage content when the system is installed', async () => {
    vi.mocked(fetch).mockResolvedValueOnce(mockResponse({ body: { is_installed: true } }));

    renderAtRoot();

    expect(
      await screen.findByRole('heading', { name: /stellarcrew/i, level: 1 }),
    ).toBeInTheDocument();
  });

  it('should show an API-unavailable notice when the status endpoint fails', async () => {
    vi.mocked(fetch).mockRejectedValueOnce(new TypeError('Failed to fetch'));

    renderAtRoot();

    expect(await screen.findByText(/could not reach the api/i)).toBeInTheDocument();
  });
});
