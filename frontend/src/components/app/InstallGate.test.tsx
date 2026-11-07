import { QueryClient, QueryClientProvider } from '@tanstack/react-query';
import { render, screen } from '@testing-library/react';
import type { ReactNode } from 'react';
import { MemoryRouter, Route, Routes } from 'react-router-dom';
import { afterEach, beforeEach, describe, expect, it, vi } from 'vitest';

import { InstallGate } from './InstallGate';

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

function renderAt(path: string) {
  const queryClient = new QueryClient({
    defaultOptions: { queries: { retry: false }, mutations: { retry: false } },
  });

  function Wrapper({ children }: { children: ReactNode }) {
    return <QueryClientProvider client={queryClient}>{children}</QueryClientProvider>;
  }

  return render(
    <MemoryRouter initialEntries={[path]}>
      <InstallGate>
        <Routes>
          <Route path="/" element={<div>Home content</div>} />
          <Route path="/login" element={<div>Login content</div>} />
          <Route path="/install" element={<div>Install wizard</div>} />
        </Routes>
      </InstallGate>
    </MemoryRouter>,
    { wrapper: Wrapper },
  );
}

describe('InstallGate', () => {
  beforeEach(() => {
    vi.stubGlobal('fetch', vi.fn());
  });

  afterEach(() => {
    vi.unstubAllGlobals();
    vi.restoreAllMocks();
  });

  it('should redirect any route to /install when the system is not installed', async () => {
    vi.mocked(fetch).mockResolvedValueOnce(mockResponse({ body: { is_installed: false } }));

    renderAt('/login');

    expect(await screen.findByText('Install wizard')).toBeInTheDocument();
    expect(screen.queryByText('Login content')).not.toBeInTheDocument();
  });

  it('should let the request through when the system is installed', async () => {
    vi.mocked(fetch).mockResolvedValueOnce(mockResponse({ body: { is_installed: true } }));

    renderAt('/login');

    expect(await screen.findByText('Login content')).toBeInTheDocument();
  });

  it('should keep /install reachable while not installed instead of looping', async () => {
    vi.mocked(fetch).mockResolvedValueOnce(mockResponse({ body: { is_installed: false } }));

    renderAt('/install');

    expect(await screen.findByText('Install wizard')).toBeInTheDocument();
  });

  it('should surface an api-unreachable message when status request fails', async () => {
    vi.mocked(fetch).mockResolvedValueOnce(mockResponse({ status: 500 }));

    renderAt('/login');

    expect(await screen.findByRole('alert')).toBeInTheDocument();
    expect(screen.queryByText('Login content')).not.toBeInTheDocument();
  });
});
