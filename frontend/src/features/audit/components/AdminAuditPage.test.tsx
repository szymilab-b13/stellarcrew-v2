import { QueryClient, QueryClientProvider } from '@tanstack/react-query';
import { render, screen, waitFor } from '@testing-library/react';
import userEvent from '@testing-library/user-event';
import type { ReactNode } from 'react';
import { MemoryRouter, Route, Routes } from 'react-router-dom';
import { afterEach, beforeEach, describe, expect, it, vi } from 'vitest';

import { clearAuthToken, writeAuthToken } from '@/lib/authToken';
import { RequireSuperAdmin } from '@/shared/guards/RequireSuperAdmin';

import { AdminAuditPage } from './AdminAuditPage';

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

function makeWrapper(initialEntry: string) {
  const queryClient = new QueryClient({
    defaultOptions: { queries: { retry: false }, mutations: { retry: false } },
  });
  return function Wrapper({ children }: { children: ReactNode }) {
    return (
      <QueryClientProvider client={queryClient}>
        <MemoryRouter initialEntries={[initialEntry]}>{children}</MemoryRouter>
      </QueryClientProvider>
    );
  };
}

function makeGuardWrapper(initialEntry: string) {
  const queryClient = new QueryClient({
    defaultOptions: { queries: { retry: false }, mutations: { retry: false } },
  });
  return function Wrapper() {
    return (
      <QueryClientProvider client={queryClient}>
        <MemoryRouter initialEntries={[initialEntry]}>
          <Routes>
            <Route element={<RequireSuperAdmin />}>
              <Route path="/admin/audit" element={<AdminAuditPage />} />
            </Route>
            <Route path="/dashboard" element={<div>Dashboard Landing</div>} />
            <Route path="/login" element={<div>Login Page</div>} />
          </Routes>
        </MemoryRouter>
      </QueryClientProvider>
    );
  };
}

function auditEntryPayload(
  overrides: Partial<Record<string, unknown>> = {},
): Record<string, unknown> {
  return {
    id: '019376ba-0000-7000-8000-000000000001',
    actor_user_id: '019376ba-aaaa-7000-8000-000000000001',
    actor_email: 'admin@example.com',
    action: 'user_management.user.created',
    resource_type: 'User',
    resource_id: '019376ba-bbbb-7000-8000-000000000001',
    before_state: null,
    after_state: null,
    metadata: {},
    occurred_at: '2026-11-07T10:00:00+00:00',
    undone_at: null,
    undone_by: null,
    undone_by_email: null,
    reverts_entry_id: null,
    ...overrides,
  };
}

describe('AdminAuditPage', () => {
  beforeEach(() => {
    writeAuthToken('test-token');
    vi.stubGlobal('fetch', vi.fn());
  });

  afterEach(() => {
    clearAuthToken();
    vi.unstubAllGlobals();
    vi.restoreAllMocks();
  });

  it('should render /admin/audit when the current user is a super admin', async () => {
    vi.mocked(fetch).mockImplementation(async (input) => {
      const url = typeof input === 'string' ? input : (input as Request).url;
      if (url.includes('/api/auth/me')) {
        return mockResponse({ body: { email: 'a@e.com', display_name: 'A', role: 'admin' } });
      }
      if (url.startsWith('/api/user-management/users/visible')) {
        return mockResponse({ body: { items: [] } });
      }
      if (url.startsWith('/api/audit/entries')) {
        return mockResponse({ body: { items: [], total: 0, page: 1, per_page: 50 } });
      }
      return mockResponse({ status: 404 });
    });

    const Wrapper = makeGuardWrapper('/admin/audit');
    render(<Wrapper />);

    expect(await screen.findByRole('heading', { name: /audit log/i })).toBeInTheDocument();
  });

  it('should redirect to /dashboard when the current user is not a super admin', async () => {
    vi.mocked(fetch).mockResolvedValueOnce(
      mockResponse({ body: { email: 'u@e.com', display_name: null, role: 'user' } }),
    );

    const Wrapper = makeGuardWrapper('/admin/audit');
    render(<Wrapper />);

    expect(await screen.findByText('Dashboard Landing')).toBeInTheDocument();
  });

  it('should call the entries endpoint with filter query parameters', async () => {
    vi.mocked(fetch).mockImplementation(async (input) => {
      const url = typeof input === 'string' ? input : (input as Request).url;
      if (url.startsWith('/api/user-management/users/visible')) {
        return mockResponse({ body: { items: [] } });
      }
      if (url.startsWith('/api/audit/entries')) {
        return mockResponse({ body: { items: [], total: 0, page: 1, per_page: 50 } });
      }
      return mockResponse({ status: 404 });
    });

    const user = userEvent.setup();
    render(<AdminAuditPage />, { wrapper: makeWrapper('/admin/audit') });

    await screen.findByRole('heading', { name: /audit log/i });

    const actionSelect = screen.getByLabelText(/^action$/i);
    await user.selectOptions(actionSelect, 'user_management.user.created');

    await waitFor(() => {
      const auditCalls = vi
        .mocked(fetch)
        .mock.calls.map((call) =>
          typeof call[0] === 'string' ? call[0] : (call[0] as Request).url,
        )
        .filter((url) => url.startsWith('/api/audit/entries'));
      expect(auditCalls.some((url) => url.includes('action=user_management.user.created'))).toBe(
        true,
      );
    });
  });

  it('should open the undo dialog with the describe-undo text when Undo is clicked', async () => {
    vi.mocked(fetch).mockImplementation(async (input) => {
      const url = typeof input === 'string' ? input : (input as Request).url;
      if (url.startsWith('/api/user-management/users/visible')) {
        return mockResponse({ body: { items: [] } });
      }
      if (url.includes('/describe-undo')) {
        return mockResponse({ body: { description: 'Restore user admin@example.com' } });
      }
      if (url.startsWith('/api/audit/entries')) {
        return mockResponse({
          body: { items: [auditEntryPayload()], total: 1, page: 1, per_page: 50 },
        });
      }
      return mockResponse({ status: 404 });
    });

    const user = userEvent.setup();
    render(<AdminAuditPage />, { wrapper: makeWrapper('/admin/audit') });

    const undoButton = await screen.findByRole('button', { name: /^undo$/i });
    await user.click(undoButton);

    expect(await screen.findByRole('dialog')).toBeInTheDocument();
    expect(await screen.findByText(/Restore user admin@example.com/)).toBeInTheDocument();
  });

  it('should POST the undo endpoint on confirm, close the dialog and refetch the list', async () => {
    const auditCalls: string[] = [];
    const undoCalls: string[] = [];
    vi.mocked(fetch).mockImplementation(async (input, init) => {
      const url = typeof input === 'string' ? input : (input as Request).url;
      if (url.startsWith('/api/user-management/users/visible')) {
        return mockResponse({ body: { items: [] } });
      }
      if (url.includes('/describe-undo')) {
        return mockResponse({ body: { description: 'Restore user' } });
      }
      if (url.includes('/undo') && (init?.method === 'POST' || !init?.method)) {
        if (init?.method === 'POST') {
          undoCalls.push(url);
          return mockResponse({
            body: { new_entry_id: '019376ba-cccc-7000-8000-000000000001' },
          });
        }
      }
      if (url.startsWith('/api/audit/entries')) {
        auditCalls.push(url);
        return mockResponse({
          body: { items: [auditEntryPayload()], total: 1, page: 1, per_page: 50 },
        });
      }
      return mockResponse({ status: 404 });
    });

    const user = userEvent.setup();
    render(<AdminAuditPage />, { wrapper: makeWrapper('/admin/audit') });

    await user.click(await screen.findByRole('button', { name: /^undo$/i }));
    await screen.findByRole('dialog');
    await user.click(screen.getByRole('button', { name: /^undo action$/i }));

    await waitFor(() => {
      expect(undoCalls).toHaveLength(1);
    });
    await waitFor(() => {
      expect(screen.queryByRole('dialog')).not.toBeInTheDocument();
    });
    await waitFor(() => {
      expect(auditCalls.length).toBeGreaterThanOrEqual(2);
    });
  });

  it('should render the undone badge for entries that have been undone', async () => {
    vi.mocked(fetch).mockImplementation(async (input) => {
      const url = typeof input === 'string' ? input : (input as Request).url;
      if (url.startsWith('/api/user-management/users/visible')) {
        return mockResponse({ body: { items: [] } });
      }
      if (url.startsWith('/api/audit/entries')) {
        return mockResponse({
          body: {
            items: [
              auditEntryPayload({
                undone_at: '2026-11-07T11:00:00+00:00',
                undone_by: '019376ba-aaaa-7000-8000-000000000002',
                undone_by_email: 'admin2@example.com',
              }),
            ],
            total: 1,
            page: 1,
            per_page: 50,
          },
        });
      }
      return mockResponse({ status: 404 });
    });

    render(<AdminAuditPage />, { wrapper: makeWrapper('/admin/audit') });

    expect(await screen.findByText(/^undone$/i)).toBeInTheDocument();
  });

  it('should seed filters from the URL search params for deep links', async () => {
    const auditCalls: string[] = [];
    vi.mocked(fetch).mockImplementation(async (input) => {
      const url = typeof input === 'string' ? input : (input as Request).url;
      if (url.startsWith('/api/user-management/users/visible')) {
        return mockResponse({ body: { items: [] } });
      }
      if (url.startsWith('/api/audit/entries')) {
        auditCalls.push(url);
        return mockResponse({ body: { items: [], total: 0, page: 1, per_page: 50 } });
      }
      return mockResponse({ status: 404 });
    });

    render(<AdminAuditPage />, {
      wrapper: makeWrapper(
        '/admin/audit?actor_user_id=019376ba-aaaa-7000-8000-000000000001&resource_id=019376ba-bbbb-7000-8000-000000000002',
      ),
    });

    await waitFor(() => {
      expect(
        auditCalls.some(
          (url) =>
            url.includes('actor_user_id=019376ba-aaaa-7000-8000-000000000001') &&
            url.includes('resource_id=019376ba-bbbb-7000-8000-000000000002'),
        ),
      ).toBe(true);
    });
  });
});
