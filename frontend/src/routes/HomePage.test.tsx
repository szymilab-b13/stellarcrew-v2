import { QueryClient, QueryClientProvider } from '@tanstack/react-query';
import { render, screen } from '@testing-library/react';
import userEvent from '@testing-library/user-event';
import type { ReactNode } from 'react';
import { MemoryRouter, Route, Routes } from 'react-router-dom';
import { afterEach, beforeEach, describe, expect, it, vi } from 'vitest';

import { clearAuthToken, writeAuthToken } from '@/lib/authToken';

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
            <Route path="/login" element={<p>login page stub</p>} />
          </Routes>
        </MemoryRouter>
      </QueryClientProvider>
    );
  }

  return render(<HomePage />, { wrapper: Wrapper });
}

describe('HomePage', () => {
  beforeEach(() => {
    clearAuthToken();
    vi.stubGlobal('fetch', vi.fn());
  });

  afterEach(() => {
    clearAuthToken();
    vi.unstubAllGlobals();
    vi.restoreAllMocks();
  });

  it('should redirect to /install when the system is not installed', async () => {
    vi.mocked(fetch).mockResolvedValueOnce(mockResponse({ body: { is_installed: false } }));

    renderAtRoot();

    expect(await screen.findByText('install page stub')).toBeInTheDocument();
  });

  it('should redirect to /login when installed but no auth token is present', async () => {
    vi.mocked(fetch).mockResolvedValueOnce(mockResponse({ body: { is_installed: true } }));

    renderAtRoot();

    expect(await screen.findByText('login page stub')).toBeInTheDocument();
  });

  it('should render the homepage content when installed and authenticated', async () => {
    writeAuthToken('jwt-token-stub');
    vi.mocked(fetch)
      .mockResolvedValueOnce(mockResponse({ body: { is_installed: true } }))
      .mockResolvedValueOnce(
        mockResponse({
          body: { email: 'admin@example.com', display_name: 'Tomek', role: 'admin' },
        }),
      );

    renderAtRoot();

    expect(
      await screen.findByRole('heading', { name: /stellarcrew/i, level: 1 }),
    ).toBeInTheDocument();
    expect(screen.getByText(/welcome, tomek/i)).toBeInTheDocument();
  });

  it('should redirect to /login when /api/auth/me returns 401', async () => {
    writeAuthToken('jwt-token-stub');
    vi.mocked(fetch)
      .mockResolvedValueOnce(mockResponse({ body: { is_installed: true } }))
      .mockResolvedValueOnce(mockResponse({ status: 401, body: {} }));

    renderAtRoot();

    expect(await screen.findByText('login page stub')).toBeInTheDocument();
  });

  it('should log out and redirect to /login when the user clicks logout', async () => {
    writeAuthToken('jwt-token-stub');
    vi.mocked(fetch)
      .mockResolvedValueOnce(mockResponse({ body: { is_installed: true } }))
      .mockResolvedValueOnce(
        mockResponse({
          body: { email: 'admin@example.com', display_name: 'Tomek', role: 'admin' },
        }),
      )
      .mockResolvedValueOnce(mockResponse({ status: 204, body: null }));

    renderAtRoot();

    const user = userEvent.setup();
    await user.click(await screen.findByRole('button', { name: /logout/i }));

    expect(await screen.findByText('login page stub')).toBeInTheDocument();
    expect(window.localStorage.getItem('sc-auth-token')).toBeNull();
  });

  it('should show an API-unavailable notice when the status endpoint fails', async () => {
    vi.mocked(fetch).mockRejectedValueOnce(new TypeError('Failed to fetch'));

    renderAtRoot();

    expect(await screen.findByText(/could not reach the api/i)).toBeInTheDocument();
  });
});
