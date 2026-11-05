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

async function fillValidAdminStep(user: ReturnType<typeof userEvent.setup>) {
  await user.type(await screen.findByLabelText(/^email\s*\*?$/i), 'admin@example.com');
  await user.type(screen.getByLabelText(/^password\s*\*?$/i), 'supersecret1');
  await user.type(screen.getByLabelText(/confirm password\s*\*?$/i), 'supersecret1');
  await user.type(screen.getByLabelText(/how should we address you/i), 'Tomek');
}

async function clickContinue(user: ReturnType<typeof userEvent.setup>) {
  await user.click(screen.getByRole('button', { name: /continue/i }));
}

describe('InstallWizard', () => {
  beforeEach(() => {
    vi.stubGlobal('fetch', vi.fn());
  });

  afterEach(() => {
    vi.unstubAllGlobals();
    vi.restoreAllMocks();
  });

  it('should render the administrator step when the system is not installed', async () => {
    vi.mocked(fetch).mockResolvedValueOnce(mockResponse({ body: { is_installed: false } }));

    render(<InstallWizard />, { wrapper: createWrapper() });

    expect(
      await screen.findByRole('heading', { name: /create your administrator account/i, level: 2 }),
    ).toBeInTheDocument();
    expect(screen.getByLabelText(/^email\s*\*?$/i)).toBeInTheDocument();
    expect(screen.getByLabelText(/^password\s*\*?$/i)).toBeInTheDocument();
    expect(screen.getByLabelText(/confirm password\s*\*?$/i)).toBeInTheDocument();
    expect(screen.getByLabelText(/how should we address you/i)).toBeInTheDocument();
  });

  it('should render already-installed notice when the system is installed', async () => {
    vi.mocked(fetch).mockResolvedValueOnce(mockResponse({ body: { is_installed: true } }));

    render(<InstallWizard />, { wrapper: createWrapper() });

    expect(
      await screen.findByRole('heading', { name: /system already installed/i }),
    ).toBeInTheDocument();
  });

  it('should show validation errors when email is invalid and passwords differ', async () => {
    vi.mocked(fetch).mockResolvedValueOnce(mockResponse({ body: { is_installed: false } }));

    render(<InstallWizard />, { wrapper: createWrapper() });
    const user = userEvent.setup();

    await user.type(await screen.findByLabelText(/^email\s*\*?$/i), 'not-an-email');
    await user.type(screen.getByLabelText(/^password\s*\*?$/i), 'short');
    await user.type(screen.getByLabelText(/confirm password\s*\*?$/i), 'different');
    await clickContinue(user);

    expect(await screen.findByText(/enter a valid email address/i)).toBeInTheDocument();
    expect(screen.getByText(/password must have at least 10 characters/i)).toBeInTheDocument();
    expect(screen.getByText(/passwords do not match/i)).toBeInTheDocument();
    expect(vi.mocked(fetch)).toHaveBeenCalledTimes(1);
  });

  it('should walk admin through all steps and show the review screen before submitting', async () => {
    vi.mocked(fetch).mockResolvedValueOnce(mockResponse({ body: { is_installed: false } }));

    render(<InstallWizard />, { wrapper: createWrapper() });
    const user = userEvent.setup();

    await fillValidAdminStep(user);
    await clickContinue(user); // → branding
    await clickContinue(user); // → logo
    await clickContinue(user); // → workspace
    await user.type(await screen.findByLabelText(/workspace name\s*\*?$/i), 'Acme HQ');
    await clickContinue(user); // → review

    expect(
      await screen.findByRole('heading', { name: /review and finish/i, level: 2 }),
    ).toBeInTheDocument();
    expect(screen.getByText('admin@example.com')).toBeInTheDocument();
    expect(screen.getByText('Tomek')).toBeInTheDocument();
    expect(screen.getByText('Acme HQ')).toBeInTheDocument();
  });

  it('should require a workspace name before leaving the workspace step', async () => {
    vi.mocked(fetch).mockResolvedValueOnce(mockResponse({ body: { is_installed: false } }));

    render(<InstallWizard />, { wrapper: createWrapper() });
    const user = userEvent.setup();

    await fillValidAdminStep(user);
    await clickContinue(user);
    await clickContinue(user);
    await clickContinue(user);
    await clickContinue(user); // tries to leave workspace with empty name

    expect(await screen.findByText(/give your workspace a name/i)).toBeInTheDocument();
  });

  it('should submit the install payload and show the completion screen on 201', async () => {
    vi.mocked(fetch)
      .mockResolvedValueOnce(mockResponse({ body: { is_installed: false } }))
      .mockResolvedValueOnce(mockResponse({ status: 201, body: { ok: true } }));

    render(<InstallWizard />, { wrapper: createWrapper() });
    const user = userEvent.setup();

    await fillValidAdminStep(user);
    await clickContinue(user);
    await clickContinue(user);
    await clickContinue(user);
    await user.type(await screen.findByLabelText(/workspace name\s*\*?$/i), 'Acme HQ');
    await clickContinue(user);
    await user.click(screen.getByRole('button', { name: /finish setup/i }));

    expect(
      await screen.findByRole('heading', { name: /you are all set, tomek/i }),
    ).toBeInTheDocument();

    await waitFor(() => {
      expect(vi.mocked(fetch)).toHaveBeenCalledTimes(2);
    });

    const installCall = vi.mocked(fetch).mock.calls[1];
    const requestInit = installCall[1] as RequestInit;
    expect(requestInit.method).toBe('POST');
    expect(requestInit.body).toBeInstanceOf(FormData);
    const sentForm = requestInit.body as FormData;
    expect(sentForm.get('email')).toBe('admin@example.com');
    expect(sentForm.get('password')).toBe('supersecret1');
    expect(sentForm.get('display_name')).toBe('Tomek');
    expect(sentForm.get('workspace_name')).toBe('Acme HQ');
    expect(sentForm.get('locale')).toBe('en');
  });

  it('should show a server error message when the install endpoint returns 400', async () => {
    vi.mocked(fetch)
      .mockResolvedValueOnce(mockResponse({ body: { is_installed: false } }))
      .mockResolvedValueOnce(mockResponse({ status: 400, body: { error: 'bad_input' } }));

    render(<InstallWizard />, { wrapper: createWrapper() });
    const user = userEvent.setup();

    await fillValidAdminStep(user);
    await clickContinue(user);
    await clickContinue(user);
    await clickContinue(user);
    await user.type(await screen.findByLabelText(/workspace name\s*\*?$/i), 'Acme HQ');
    await clickContinue(user);
    await user.click(screen.getByRole('button', { name: /finish setup/i }));

    expect(await screen.findByText(/server rejected the submitted values/i)).toBeInTheDocument();
    expect(screen.queryByRole('heading', { name: /you are all set/i })).not.toBeInTheDocument();
  });

  it('should show an API-unavailable notice when the status endpoint fails', async () => {
    vi.mocked(fetch).mockRejectedValueOnce(new TypeError('Failed to fetch'));

    render(<InstallWizard />, { wrapper: createWrapper() });

    expect(await screen.findByText(/could not reach the api/i)).toBeInTheDocument();
  });
});
