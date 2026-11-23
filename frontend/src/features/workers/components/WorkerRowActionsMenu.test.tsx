import { QueryClient, QueryClientProvider } from '@tanstack/react-query';
import { render, screen, waitFor, within } from '@testing-library/react';
import userEvent from '@testing-library/user-event';
import type { ReactNode } from 'react';
import { MemoryRouter, Route, Routes } from 'react-router-dom';
import { afterEach, beforeEach, describe, expect, it, vi } from 'vitest';

import { clearAuthToken, writeAuthToken } from '@/lib/authToken';

import { WorkerRowActionsMenu } from './WorkerRowActionsMenu';

function mockResponse(status: number, body?: unknown): Response {
  if (body === undefined) {
    return new Response(null, { status });
  }
  return new Response(JSON.stringify(body), {
    status,
    headers: { 'Content-Type': 'application/json' },
  });
}

function makeWrapper() {
  const queryClient = new QueryClient({
    defaultOptions: { queries: { retry: false }, mutations: { retry: false } },
  });
  return function Wrapper({ children }: { children: ReactNode }) {
    return (
      <QueryClientProvider client={queryClient}>
        <MemoryRouter initialEntries={['/settings/workers']}>
          <Routes>
            <Route path="/settings/workers" element={children} />
            <Route
              path="/settings/workers/:id"
              element={<div data-testid="detail-page">detail</div>}
            />
          </Routes>
        </MemoryRouter>
      </QueryClientProvider>
    );
  };
}

describe('WorkerRowActionsMenu', () => {
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
    render(<WorkerRowActionsMenu workerId="w-1" workerName="demo-1" />, {
      wrapper: makeWrapper(),
    });

    expect(screen.queryByRole('menu')).not.toBeInTheDocument();
    const burger = screen.getByRole('button', { name: /actions for worker demo-1/i });
    expect(burger).toHaveAttribute('aria-haspopup', 'menu');
    expect(burger).toHaveAttribute('aria-expanded', 'false');
  });

  it('opens the dropdown with Details, Copy ID and Revoke items', async () => {
    const user = userEvent.setup();
    render(<WorkerRowActionsMenu workerId="w-1" workerName="demo-1" />, {
      wrapper: makeWrapper(),
    });

    await user.click(screen.getByRole('button', { name: /actions for worker demo-1/i }));

    const menu = screen.getByRole('menu');
    expect(within(menu).getByRole('menuitem', { name: /details/i })).toBeInTheDocument();
    expect(within(menu).getByRole('menuitem', { name: /copy id/i })).toBeInTheDocument();
    expect(within(menu).getByRole('menuitem', { name: /^revoke$/i })).toBeInTheDocument();
  });

  it('navigates to the worker detail page when Details is clicked', async () => {
    const user = userEvent.setup();
    render(<WorkerRowActionsMenu workerId="nav-id" workerName="demo-1" />, {
      wrapper: makeWrapper(),
    });

    await user.click(screen.getByRole('button', { name: /actions for worker demo-1/i }));
    await user.click(screen.getByRole('menuitem', { name: /details/i }));

    expect(await screen.findByTestId('detail-page')).toBeInTheDocument();
    expect(screen.queryByRole('menu')).not.toBeInTheDocument();
  });

  it('writes the worker id to the clipboard when Copy ID is clicked', async () => {
    const user = userEvent.setup();
    const writeText = vi.spyOn(navigator.clipboard, 'writeText');

    render(<WorkerRowActionsMenu workerId="copy-id-123" workerName="demo-1" />, {
      wrapper: makeWrapper(),
    });

    await user.click(screen.getByRole('button', { name: /actions for worker demo-1/i }));
    await user.click(screen.getByRole('menuitem', { name: /copy id/i }));

    await waitFor(() => {
      expect(writeText).toHaveBeenCalledWith('copy-id-123');
    });
    expect(screen.queryByRole('menu')).not.toBeInTheDocument();
  });

  it('opens revoke confirm dialog and DELETEs the worker on confirm', async () => {
    vi.mocked(fetch).mockResolvedValueOnce(mockResponse(204));
    const user = userEvent.setup();

    render(<WorkerRowActionsMenu workerId="revoke-id" workerName="demo-1" />, {
      wrapper: makeWrapper(),
    });

    await user.click(screen.getByRole('button', { name: /actions for worker demo-1/i }));
    await user.click(screen.getByRole('menuitem', { name: /^revoke$/i }));

    const dialog = await screen.findByRole('dialog');
    await user.click(within(dialog).getByRole('button', { name: /^revoke worker$/i }));

    await waitFor(() => {
      expect(vi.mocked(fetch)).toHaveBeenCalledWith(
        '/api/workers/revoke-id',
        expect.objectContaining({ method: 'DELETE' }),
      );
    });
  });

  it('surfaces a 403 server message in the revoke dialog', async () => {
    vi.mocked(fetch).mockResolvedValueOnce(
      mockResponse(403, { title: 'Forbidden', detail: 'nope' }),
    );
    const user = userEvent.setup();

    render(<WorkerRowActionsMenu workerId="revoke-id" workerName="demo-1" />, {
      wrapper: makeWrapper(),
    });

    await user.click(screen.getByRole('button', { name: /actions for worker demo-1/i }));
    await user.click(screen.getByRole('menuitem', { name: /^revoke$/i }));

    const dialog = await screen.findByRole('dialog');
    await user.click(within(dialog).getByRole('button', { name: /^revoke worker$/i }));

    expect(
      await within(dialog).findByText(/do not have permission to revoke this worker/i),
    ).toBeInTheDocument();
  });

  it('renders the dropdown via portal, not inside the trigger container', async () => {
    const user = userEvent.setup();
    const { container } = render(<WorkerRowActionsMenu workerId="w-1" workerName="demo-1" />, {
      wrapper: makeWrapper(),
    });

    await user.click(screen.getByRole('button', { name: /actions for worker demo-1/i }));

    expect(screen.getByRole('menu')).toBeInTheDocument();
    expect(container.querySelector('[role="menu"]')).toBeNull();
  });

  it('closes the dropdown when clicking outside', async () => {
    const user = userEvent.setup();
    render(
      <div>
        <WorkerRowActionsMenu workerId="w-1" workerName="demo-1" />
        <button type="button">outside</button>
      </div>,
      { wrapper: makeWrapper() },
    );

    await user.click(screen.getByRole('button', { name: /actions for worker demo-1/i }));
    expect(screen.getByRole('menu')).toBeInTheDocument();

    await user.click(screen.getByRole('button', { name: /^outside$/i }));
    expect(screen.queryByRole('menu')).not.toBeInTheDocument();
  });
});
