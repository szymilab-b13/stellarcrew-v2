import { QueryClient, QueryClientProvider } from '@tanstack/react-query';
import { render, screen, waitFor, within } from '@testing-library/react';
import userEvent from '@testing-library/user-event';
import type { ReactNode } from 'react';
import { MemoryRouter, Route, Routes } from 'react-router-dom';
import { afterEach, beforeEach, describe, expect, it, vi } from 'vitest';

import { clearAuthToken, writeAuthToken } from '@/lib/authToken';

import { UserRowActionsMenu } from './UserRowActionsMenu';

function mockResponse(status: number): Response {
  return new Response(null, { status });
}

function makeWrapper() {
  const queryClient = new QueryClient({
    defaultOptions: { queries: { retry: false }, mutations: { retry: false } },
  });
  return function Wrapper({ children }: { children: ReactNode }) {
    return (
      <QueryClientProvider client={queryClient}>
        <MemoryRouter initialEntries={['/admin/users']}>
          <Routes>
            <Route path="/admin/users" element={children} />
            <Route path="/admin/users/:id" element={<div data-testid="detail-page">detail</div>} />
          </Routes>
        </MemoryRouter>
      </QueryClientProvider>
    );
  };
}

describe('UserRowActionsMenu', () => {
  beforeEach(() => {
    writeAuthToken('test-token');
    vi.stubGlobal('fetch', vi.fn());
  });

  afterEach(() => {
    clearAuthToken();
    vi.unstubAllGlobals();
    vi.restoreAllMocks();
  });

  it('does not render the dropdown before the burger is clicked', () => {
    render(<UserRowActionsMenu userId="abc" email="target@example.com" isDeactivated={false} />, {
      wrapper: makeWrapper(),
    });

    expect(screen.queryByRole('menu')).not.toBeInTheDocument();
    const burger = screen.getByRole('button', { name: /actions for user target@example.com/i });
    expect(burger).toHaveAttribute('aria-haspopup', 'menu');
    expect(burger).toHaveAttribute('aria-expanded', 'false');
  });

  it('opens the dropdown with Details, Deactivate and Reset password for an active user', async () => {
    const user = userEvent.setup();
    render(<UserRowActionsMenu userId="abc" email="target@example.com" isDeactivated={false} />, {
      wrapper: makeWrapper(),
    });

    await user.click(screen.getByRole('button', { name: /actions for user target@example.com/i }));

    const menu = screen.getByRole('menu');
    expect(within(menu).getByRole('menuitem', { name: /details/i })).toBeInTheDocument();
    expect(within(menu).getByRole('menuitem', { name: /^deactivate$/i })).toBeInTheDocument();
    expect(within(menu).getByRole('menuitem', { name: /reset password/i })).toBeInTheDocument();
    expect(within(menu).queryByRole('menuitem', { name: /^reactivate$/i })).not.toBeInTheDocument();
  });

  it('opens the dropdown with Details, Reactivate and Reset password for a deactivated user', async () => {
    const user = userEvent.setup();
    render(<UserRowActionsMenu userId="abc" email="target@example.com" isDeactivated />, {
      wrapper: makeWrapper(),
    });

    await user.click(screen.getByRole('button', { name: /actions for user target@example.com/i }));

    const menu = screen.getByRole('menu');
    expect(within(menu).getByRole('menuitem', { name: /^reactivate$/i })).toBeInTheDocument();
    expect(within(menu).queryByRole('menuitem', { name: /^deactivate$/i })).not.toBeInTheDocument();
  });

  it('navigates to the user detail page when Details is clicked and closes the dropdown', async () => {
    const user = userEvent.setup();
    render(
      <UserRowActionsMenu userId="nav-id" email="target@example.com" isDeactivated={false} />,
      { wrapper: makeWrapper() },
    );

    await user.click(screen.getByRole('button', { name: /actions for user target@example.com/i }));
    await user.click(screen.getByRole('menuitem', { name: /details/i }));

    expect(await screen.findByTestId('detail-page')).toBeInTheDocument();
    expect(screen.queryByRole('menu')).not.toBeInTheDocument();
  });

  it('opens the deactivate confirm dialog and POSTs to the deactivate endpoint on confirm', async () => {
    vi.mocked(fetch).mockResolvedValueOnce(mockResponse(204));
    const user = userEvent.setup();

    render(
      <UserRowActionsMenu userId="deact-id" email="target@example.com" isDeactivated={false} />,
      { wrapper: makeWrapper() },
    );

    await user.click(screen.getByRole('button', { name: /actions for user target@example.com/i }));
    await user.click(screen.getByRole('menuitem', { name: /^deactivate$/i }));

    const dialog = await screen.findByRole('dialog');
    await user.click(within(dialog).getByRole('button', { name: /^deactivate$/i }));

    await waitFor(() => {
      expect(vi.mocked(fetch)).toHaveBeenCalledWith(
        '/api/user-management/users/deact-id/deactivate',
        expect.objectContaining({ method: 'POST' }),
      );
    });
  });

  it('POSTs to the reactivate endpoint when confirming reactivation', async () => {
    vi.mocked(fetch).mockResolvedValueOnce(mockResponse(204));
    const user = userEvent.setup();

    render(<UserRowActionsMenu userId="react-id" email="target@example.com" isDeactivated />, {
      wrapper: makeWrapper(),
    });

    await user.click(screen.getByRole('button', { name: /actions for user target@example.com/i }));
    await user.click(screen.getByRole('menuitem', { name: /^reactivate$/i }));

    const dialog = await screen.findByRole('dialog');
    await user.click(within(dialog).getByRole('button', { name: /^reactivate$/i }));

    await waitFor(() => {
      expect(vi.mocked(fetch)).toHaveBeenCalledWith(
        '/api/user-management/users/react-id/reactivate',
        expect.objectContaining({ method: 'POST' }),
      );
    });
  });

  it('opens the reset password dialog and POSTs the new password on submit', async () => {
    vi.mocked(fetch).mockResolvedValueOnce(mockResponse(204));
    const user = userEvent.setup();

    render(
      <UserRowActionsMenu userId="reset-id" email="target@example.com" isDeactivated={false} />,
      { wrapper: makeWrapper() },
    );

    await user.click(screen.getByRole('button', { name: /actions for user target@example.com/i }));
    await user.click(screen.getByRole('menuitem', { name: /reset password/i }));

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

  it('closes the dropdown when clicking outside', async () => {
    const user = userEvent.setup();
    render(
      <div>
        <UserRowActionsMenu userId="abc" email="target@example.com" isDeactivated={false} />
        <button type="button">outside</button>
      </div>,
      { wrapper: makeWrapper() },
    );

    await user.click(screen.getByRole('button', { name: /actions for user target@example.com/i }));
    expect(screen.getByRole('menu')).toBeInTheDocument();

    await user.click(screen.getByRole('button', { name: /^outside$/i }));
    expect(screen.queryByRole('menu')).not.toBeInTheDocument();
  });
});
