import { QueryClient, QueryClientProvider } from '@tanstack/react-query';
import { render, screen } from '@testing-library/react';
import type { ReactNode } from 'react';
import { MemoryRouter, Route, Routes } from 'react-router-dom';
import { afterEach, beforeEach, describe, expect, it, vi } from 'vitest';

import { writeAuthToken, clearAuthToken } from '@/lib/authToken';

import { RequireSuperAdmin } from './RequireSuperAdmin';

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

function renderGuard() {
  const queryClient = new QueryClient({
    defaultOptions: { queries: { retry: false }, mutations: { retry: false } },
  });

  function Wrapper({ children }: { children: ReactNode }) {
    return <QueryClientProvider client={queryClient}>{children}</QueryClientProvider>;
  }

  return render(
    <MemoryRouter initialEntries={['/admin/users']}>
      <Routes>
        <Route element={<RequireSuperAdmin />}>
          <Route path="/admin/users" element={<div>Admin Content</div>} />
        </Route>
        <Route path="/dashboard" element={<div>Dashboard Landing</div>} />
        <Route path="/login" element={<div>Login Page</div>} />
      </Routes>
    </MemoryRouter>,
    { wrapper: Wrapper },
  );
}

describe('RequireSuperAdmin', () => {
  beforeEach(() => {
    writeAuthToken('test-token');
    vi.stubGlobal('fetch', vi.fn());
  });

  afterEach(() => {
    clearAuthToken();
    vi.unstubAllGlobals();
    vi.restoreAllMocks();
  });

  it('should render outlet when the current user is a super admin', async () => {
    vi.mocked(fetch).mockResolvedValueOnce(
      mockResponse({
        body: { email: 'admin@example.com', display_name: 'Tomek', role: 'admin' },
      }),
    );

    renderGuard();

    expect(await screen.findByText('Admin Content')).toBeInTheDocument();
  });

  it('should redirect to dashboard when the current user is a regular user', async () => {
    vi.mocked(fetch).mockResolvedValueOnce(
      mockResponse({
        body: { email: 'user@example.com', display_name: null, role: 'user' },
      }),
    );

    renderGuard();

    expect(await screen.findByText('Dashboard Landing')).toBeInTheDocument();
  });

  it('should redirect to login when the me request fails', async () => {
    vi.mocked(fetch).mockResolvedValueOnce(mockResponse({ status: 401, body: {} }));

    renderGuard();

    expect(await screen.findByText('Login Page')).toBeInTheDocument();
  });
});
