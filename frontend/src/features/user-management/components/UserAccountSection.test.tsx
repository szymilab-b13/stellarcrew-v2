import { QueryClient, QueryClientProvider } from '@tanstack/react-query';
import { render, screen, waitFor, within } from '@testing-library/react';
import userEvent from '@testing-library/user-event';
import type { ReactNode } from 'react';
import { afterEach, beforeEach, describe, expect, it, vi } from 'vitest';

import { clearAuthToken, writeAuthToken } from '@/lib/authToken';

import { UserAccountSection } from './UserAccountSection';

function mockResponse(status: number): Response {
  return new Response(null, { status });
}

function makeWrapper() {
  const queryClient = new QueryClient({
    defaultOptions: { queries: { retry: false }, mutations: { retry: false } },
  });
  return function Wrapper({ children }: { children: ReactNode }) {
    return <QueryClientProvider client={queryClient}>{children}</QueryClientProvider>;
  };
}

describe('UserAccountSection', () => {
  beforeEach(() => {
    writeAuthToken('test-token');
    vi.stubGlobal('fetch', vi.fn());
  });

  afterEach(() => {
    clearAuthToken();
    vi.unstubAllGlobals();
    vi.restoreAllMocks();
  });

  it('renders the Deactivate button when the user is active', () => {
    render(<UserAccountSection userId="abc" email="target@example.com" isDeactivated={false} />, {
      wrapper: makeWrapper(),
    });

    expect(screen.getByRole('button', { name: /deactivate$/i })).toBeInTheDocument();
    expect(screen.queryByRole('button', { name: /reactivate$/i })).not.toBeInTheDocument();
  });

  it('renders the Reactivate button when the user is deactivated', () => {
    render(<UserAccountSection userId="abc" email="target@example.com" isDeactivated />, {
      wrapper: makeWrapper(),
    });

    expect(screen.getByRole('button', { name: /^reactivate$/i })).toBeInTheDocument();
  });

  it('opens the confirm dialog on button click and posts to the deactivate endpoint on confirm', async () => {
    vi.mocked(fetch).mockResolvedValueOnce(mockResponse(204));
    const user = userEvent.setup();

    render(
      <UserAccountSection userId="abc-id" email="target@example.com" isDeactivated={false} />,
      { wrapper: makeWrapper() },
    );

    await user.click(screen.getByRole('button', { name: /deactivate$/i }));
    const dialog = await screen.findByRole('dialog');

    await user.click(within(dialog).getByRole('button', { name: /^deactivate$/i }));

    await waitFor(() => {
      expect(vi.mocked(fetch)).toHaveBeenCalledWith(
        '/api/user-management/users/abc-id/deactivate',
        expect.objectContaining({ method: 'POST' }),
      );
    });
  });

  it('posts to the reactivate endpoint when confirming reactivation', async () => {
    vi.mocked(fetch).mockResolvedValueOnce(mockResponse(204));
    const user = userEvent.setup();

    render(<UserAccountSection userId="xyz-id" email="target@example.com" isDeactivated />, {
      wrapper: makeWrapper(),
    });

    await user.click(screen.getByRole('button', { name: /^reactivate$/i }));
    const dialog = await screen.findByRole('dialog');
    await user.click(within(dialog).getByRole('button', { name: /^reactivate$/i }));

    await waitFor(() => {
      expect(vi.mocked(fetch)).toHaveBeenCalledWith(
        '/api/user-management/users/xyz-id/reactivate',
        expect.objectContaining({ method: 'POST' }),
      );
    });
  });

  it('closes the dialog on cancel', async () => {
    const user = userEvent.setup();

    render(
      <UserAccountSection userId="abc-id" email="target@example.com" isDeactivated={false} />,
      { wrapper: makeWrapper() },
    );

    await user.click(screen.getByRole('button', { name: /deactivate$/i }));
    expect(await screen.findByRole('dialog')).toBeInTheDocument();

    await user.click(screen.getByRole('button', { name: /cancel/i }));

    expect(screen.queryByRole('dialog')).not.toBeInTheDocument();
  });

  it('exposes an enabled Reset password button', () => {
    render(<UserAccountSection userId="abc" email="target@example.com" isDeactivated={false} />, {
      wrapper: makeWrapper(),
    });

    const resetButton = screen.getByRole('button', { name: /reset password/i });
    expect(resetButton).toBeEnabled();
  });

  it('opens the reset password dialog and POSTs the new password on submit', async () => {
    vi.mocked(fetch).mockResolvedValueOnce(mockResponse(204));
    const user = userEvent.setup();

    render(
      <UserAccountSection userId="reset-id" email="target@example.com" isDeactivated={false} />,
      { wrapper: makeWrapper() },
    );

    await user.click(screen.getByRole('button', { name: /reset password/i }));
    const dialog = await screen.findByRole('dialog');
    await user.type(within(dialog).getByLabelText('New password'), 'brand-new-password-ok');
    await user.type(within(dialog).getByLabelText('Repeat new password'), 'brand-new-password-ok');
    await user.click(within(dialog).getByRole('button', { name: /^reset password$/i }));

    await waitFor(() => {
      expect(vi.mocked(fetch)).toHaveBeenCalledWith(
        '/api/user-management/users/reset-id/reset-password',
        expect.objectContaining({
          method: 'POST',
          body: JSON.stringify({ newPassword: 'brand-new-password-ok' }),
        }),
      );
    });
  });
});
