import { QueryClient, QueryClientProvider } from '@tanstack/react-query';
import { render, screen } from '@testing-library/react';
import userEvent from '@testing-library/user-event';
import type { ReactNode } from 'react';
import { MemoryRouter, Route, Routes } from 'react-router-dom';
import { afterEach, beforeEach, describe, expect, it, vi } from 'vitest';

import { clearAuthToken } from '@/lib/authToken';

import { LoginPage } from './LoginPage';

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

function renderLogin() {
  const queryClient = new QueryClient({
    defaultOptions: { queries: { retry: false }, mutations: { retry: false } },
  });

  function Wrapper({ children }: { children: ReactNode }) {
    return (
      <QueryClientProvider client={queryClient}>
        <MemoryRouter initialEntries={['/login']}>
          <Routes>
            <Route path="/login" element={children} />
            <Route path="/" element={<p>home page stub</p>} />
          </Routes>
        </MemoryRouter>
      </QueryClientProvider>
    );
  }

  return render(<LoginPage />, { wrapper: Wrapper });
}

describe('LoginPage', () => {
  beforeEach(() => {
    clearAuthToken();
    vi.stubGlobal('fetch', vi.fn());
  });

  afterEach(() => {
    clearAuthToken();
    vi.unstubAllGlobals();
    vi.restoreAllMocks();
  });

  it('should render the sign-in form', async () => {
    renderLogin();

    expect(
      await screen.findByRole('heading', { name: /sign in to stellarcrew/i, level: 1 }),
    ).toBeInTheDocument();
    expect(screen.getByLabelText(/^email$/i)).toBeInTheDocument();
    expect(screen.getByLabelText(/^password$/i)).toBeInTheDocument();
    expect(screen.getByRole('button', { name: /^sign in$/i })).toBeInTheDocument();
  });

  it('should show a client-side error for an invalid email', async () => {
    renderLogin();
    const user = userEvent.setup();

    await user.type(screen.getByLabelText(/^email$/i), 'not-an-email');
    await user.type(screen.getByLabelText(/^password$/i), 'anything');
    await user.click(screen.getByRole('button', { name: /^sign in$/i }));

    expect(await screen.findByText(/enter a valid email address/i)).toBeInTheDocument();
    expect(vi.mocked(fetch)).not.toHaveBeenCalled();
  });

  it('should submit valid credentials and navigate to the home page on success', async () => {
    vi.mocked(fetch).mockResolvedValueOnce(
      mockResponse({
        body: {
          token: 'jwt-token-stub',
          user: { email: 'admin@example.com', display_name: 'Tomek', role: 'admin' },
        },
      }),
    );

    renderLogin();
    const user = userEvent.setup();

    await user.type(screen.getByLabelText(/^email$/i), 'admin@example.com');
    await user.type(screen.getByLabelText(/^password$/i), 'supersecret1');
    await user.click(screen.getByRole('button', { name: /^sign in$/i }));

    expect(await screen.findByText('home page stub')).toBeInTheDocument();
    expect(window.localStorage.getItem('sc-auth-token')).toBe('jwt-token-stub');

    const call = vi.mocked(fetch).mock.calls[0];
    expect(String(call[0])).toContain('/api/auth/login');
    const init = call[1] as RequestInit;
    expect(init.method).toBe('POST');
    expect(JSON.parse(String(init.body))).toEqual({
      email: 'admin@example.com',
      password: 'supersecret1',
    });
  });

  it('should show an invalid-credentials message when the API returns 401', async () => {
    vi.mocked(fetch).mockResolvedValueOnce(mockResponse({ status: 401, body: {} }));

    renderLogin();
    const user = userEvent.setup();

    await user.type(screen.getByLabelText(/^email$/i), 'admin@example.com');
    await user.type(screen.getByLabelText(/^password$/i), 'wrong-password');
    await user.click(screen.getByRole('button', { name: /^sign in$/i }));

    expect(await screen.findByText(/invalid email or password/i)).toBeInTheDocument();
    expect(window.localStorage.getItem('sc-auth-token')).toBeNull();
  });
});
