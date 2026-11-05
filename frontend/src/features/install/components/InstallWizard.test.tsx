import { QueryClient, QueryClientProvider } from '@tanstack/react-query';
import { render, screen, waitFor } from '@testing-library/react';
import userEvent from '@testing-library/user-event';
import type { ReactNode } from 'react';
import { afterEach, beforeEach, describe, expect, it, vi } from 'vitest';

import { InstallWizard } from './InstallWizard';

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

function createWrapper() {
  const queryClient = new QueryClient({
    defaultOptions: { queries: { retry: false }, mutations: { retry: false } },
  });

  function Wrapper({ children }: { children: ReactNode }) {
    return <QueryClientProvider client={queryClient}>{children}</QueryClientProvider>;
  }

  return Wrapper;
}

describe('InstallWizard', () => {
  beforeEach(() => {
    vi.stubGlobal('fetch', vi.fn());
  });

  afterEach(() => {
    vi.unstubAllGlobals();
    vi.restoreAllMocks();
  });

  it('should render email and password form when status says not installed', async () => {
    vi.mocked(fetch).mockResolvedValueOnce(mockResponse({ body: { is_installed: false } }));

    render(<InstallWizard />, { wrapper: createWrapper() });

    expect(
      await screen.findByRole('heading', { name: /create administrator account/i }),
    ).toBeInTheDocument();
    expect(screen.getByLabelText(/email/i)).toBeInTheDocument();
    expect(screen.getByLabelText(/password/i)).toBeInTheDocument();
    expect(screen.getByRole('button', { name: /install/i })).toBeInTheDocument();
  });

  it('should render already-installed notice when status says installed', async () => {
    vi.mocked(fetch).mockResolvedValueOnce(mockResponse({ body: { is_installed: true } }));

    render(<InstallWizard />, { wrapper: createWrapper() });

    expect(
      await screen.findByRole('heading', { name: /system already installed/i }),
    ).toBeInTheDocument();
    expect(screen.queryByRole('button', { name: /install/i })).not.toBeInTheDocument();
  });

  it('should show validation errors when email is invalid and password is too short', async () => {
    vi.mocked(fetch).mockResolvedValueOnce(mockResponse({ body: { is_installed: false } }));

    render(<InstallWizard />, { wrapper: createWrapper() });

    const user = userEvent.setup();

    await user.type(await screen.findByLabelText(/email/i), 'not-an-email');
    await user.type(screen.getByLabelText(/password/i), 'short');
    await user.click(screen.getByRole('button', { name: /install/i }));

    expect(await screen.findByText(/enter a valid email address/i)).toBeInTheDocument();
    expect(screen.getByText(/password must have at least 10 characters/i)).toBeInTheDocument();
    expect(vi.mocked(fetch)).toHaveBeenCalledTimes(1);
  });

  it('should submit install payload and show success state on 201', async () => {
    vi.mocked(fetch)
      .mockResolvedValueOnce(mockResponse({ body: { is_installed: false } }))
      .mockResolvedValueOnce(mockResponse({ status: 201, body: { ok: true } }));

    render(<InstallWizard />, { wrapper: createWrapper() });

    const user = userEvent.setup();

    await user.type(await screen.findByLabelText(/email/i), 'admin@example.com');
    await user.type(screen.getByLabelText(/password/i), 'supersecret1');
    await user.click(screen.getByRole('button', { name: /install/i }));

    expect(
      await screen.findByRole('heading', { name: /administrator created/i }),
    ).toBeInTheDocument();

    await waitFor(() => {
      expect(vi.mocked(fetch)).toHaveBeenCalledTimes(2);
    });

    const installCall = vi.mocked(fetch).mock.calls[1];
    const requestInit = installCall[1] as RequestInit;
    expect(requestInit.method).toBe('POST');
    expect(JSON.parse(String(requestInit.body))).toEqual({
      email: 'admin@example.com',
      password: 'supersecret1',
    });
  });

  it('should show server error message when install endpoint returns 400', async () => {
    vi.mocked(fetch)
      .mockResolvedValueOnce(mockResponse({ body: { is_installed: false } }))
      .mockResolvedValueOnce(mockResponse({ status: 400, body: { error: 'bad_input' } }));

    render(<InstallWizard />, { wrapper: createWrapper() });

    const user = userEvent.setup();

    await user.type(await screen.findByLabelText(/email/i), 'admin@example.com');
    await user.type(screen.getByLabelText(/password/i), 'supersecret1');
    await user.click(screen.getByRole('button', { name: /install/i }));

    expect(await screen.findByText(/server rejected the submitted values/i)).toBeInTheDocument();
    expect(
      screen.queryByRole('heading', { name: /administrator created/i }),
    ).not.toBeInTheDocument();
  });

  it('should show already-installed server error when install endpoint returns 404', async () => {
    vi.mocked(fetch)
      .mockResolvedValueOnce(mockResponse({ body: { is_installed: false } }))
      .mockResolvedValueOnce(mockResponse({ status: 404, body: { error: 'already_installed' } }));

    render(<InstallWizard />, { wrapper: createWrapper() });

    const user = userEvent.setup();

    await user.type(await screen.findByLabelText(/email/i), 'admin@example.com');
    await user.type(screen.getByLabelText(/password/i), 'supersecret1');
    await user.click(screen.getByRole('button', { name: /install/i }));

    expect(await screen.findByText(/the system has already been installed/i)).toBeInTheDocument();
  });

  it('should show API-unavailable notice when status endpoint fails', async () => {
    vi.mocked(fetch).mockRejectedValueOnce(new TypeError('Failed to fetch'));

    render(<InstallWizard />, { wrapper: createWrapper() });

    expect(await screen.findByText(/could not reach the api/i)).toBeInTheDocument();
  });
});
