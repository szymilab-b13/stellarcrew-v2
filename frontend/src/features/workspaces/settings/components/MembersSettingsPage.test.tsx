import { QueryClient, QueryClientProvider } from '@tanstack/react-query';
import { render, screen, waitFor, within } from '@testing-library/react';
import userEvent from '@testing-library/user-event';
import type { ReactNode } from 'react';
import { MemoryRouter, Route, Routes } from 'react-router-dom';
import { afterEach, beforeEach, describe, expect, it, vi } from 'vitest';

import '@/i18n/i18n';
import { clearAuthToken, writeAuthToken } from '@/lib/authToken';

import { MembersSettingsPage } from './MembersSettingsPage';

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

function renderPage(initialPath: string = '/workspace/ws-1/settings/members') {
  const queryClient = new QueryClient({
    defaultOptions: { queries: { retry: false }, mutations: { retry: false } },
  });
  function Wrapper({ children }: { children: ReactNode }) {
    return (
      <QueryClientProvider client={queryClient}>
        <MemoryRouter initialEntries={[initialPath]}>
          <Routes>
            <Route path="/workspace/:wsId/settings/members" element={children} />
            <Route path="/workspaces" element={<div>workspaces fallback</div>} />
          </Routes>
        </MemoryRouter>
      </QueryClientProvider>
    );
  }
  return render(<MembersSettingsPage />, { wrapper: Wrapper });
}

type FetchCall = [string, RequestInit?];

function buildMockFetch(handlers: Record<string, (init?: RequestInit) => Response>) {
  return vi.fn((input: RequestInfo | URL, init?: RequestInit) => {
    const url = typeof input === 'string' ? input : input.toString();
    const method = (init?.method ?? 'GET').toUpperCase();
    const key = `${method} ${url.split('?')[0]}`;
    const anyKey = Object.keys(handlers).find((h) => h === key || h === `${method} ${url}`);
    const handler = anyKey ? handlers[anyKey] : null;
    if (!handler) {
      throw new Error(`Unexpected fetch ${method} ${url}`);
    }
    return Promise.resolve(handler(init));
  });
}

const MEMBERS_URL = 'GET /api/workspaces/ws-1/members';
const WORKSPACES_URL = 'GET /api/workspaces';
const ME_URL = 'GET /api/auth/me';
const VISIBLE_URL = 'GET /api/user-management/users/visible';

const superAdmin = {
  email: 'super@example.com',
  display_name: 'Super',
  role: 'admin',
};

const ownerMember = {
  userId: '00000000-0000-0000-0000-000000000001',
  email: 'owner@example.com',
  role: 'owner',
  joinedAt: '2026-11-01T10:00:00+00:00',
};

const regularMember = {
  userId: '00000000-0000-0000-0000-000000000002',
  email: 'member@example.com',
  role: 'member',
  joinedAt: '2026-11-02T10:00:00+00:00',
};

describe('MembersSettingsPage', () => {
  beforeEach(() => {
    writeAuthToken('test-token');
  });

  afterEach(() => {
    clearAuthToken();
    vi.unstubAllGlobals();
    vi.restoreAllMocks();
  });

  it('should render members table with role column and joined date', async () => {
    const mock = buildMockFetch({
      [WORKSPACES_URL]: () => mockResponse({ body: [{ id: 'ws-1', name: 'Shared' }] }),
      [ME_URL]: () => mockResponse({ body: superAdmin }),
      [MEMBERS_URL]: () => mockResponse({ body: { items: [ownerMember, regularMember] } }),
    });
    vi.stubGlobal('fetch', mock);

    renderPage();

    expect(await screen.findByText('owner@example.com')).toBeInTheDocument();
    expect(screen.getByText('member@example.com')).toBeInTheDocument();
    expect(screen.getByText('2 members total')).toBeInTheDocument();
  });

  it('should render owner role as a read-only badge (no select) and member role as editable select', async () => {
    const mock = buildMockFetch({
      [WORKSPACES_URL]: () => mockResponse({ body: [{ id: 'ws-1', name: 'Shared' }] }),
      [ME_URL]: () => mockResponse({ body: superAdmin }),
      [MEMBERS_URL]: () => mockResponse({ body: { items: [ownerMember, regularMember] } }),
    });
    vi.stubGlobal('fetch', mock);

    renderPage();

    await screen.findByText('owner@example.com');
    const ownerRow = screen.getByText('owner@example.com').closest('tr') as HTMLElement;
    expect(within(ownerRow).queryByRole('combobox')).toBeNull();
    const memberRow = screen.getByText('member@example.com').closest('tr') as HTMLElement;
    expect(within(memberRow).getByRole('combobox')).toBeInTheDocument();
  });

  it('should hide management controls when caller is a guest without manage permission', async () => {
    const guest = {
      ...regularMember,
      role: 'guest',
      email: 'guest@example.com',
      userId: '00000000-0000-0000-0000-000000000004',
    };
    const mock = buildMockFetch({
      [WORKSPACES_URL]: () => mockResponse({ body: [{ id: 'ws-1', name: 'Shared' }] }),
      [ME_URL]: () =>
        mockResponse({ body: { email: 'guest@example.com', display_name: null, role: 'user' } }),
      [MEMBERS_URL]: () => mockResponse({ body: { items: [ownerMember, guest] } }),
    });
    vi.stubGlobal('fetch', mock);

    renderPage();

    await screen.findByText('owner@example.com');
    expect(screen.queryByRole('combobox')).toBeNull();
    expect(screen.queryByText(/Add member/i)).toBeNull();
  });

  it('should show a not-found hint when the visible-users search returns no candidates', async () => {
    const fetchHandlers: Record<string, (init?: RequestInit) => Response> = {
      [WORKSPACES_URL]: () => mockResponse({ body: [{ id: 'ws-1', name: 'Shared' }] }),
      [ME_URL]: () => mockResponse({ body: superAdmin }),
      [MEMBERS_URL]: () => mockResponse({ body: { items: [ownerMember] } }),
      [VISIBLE_URL]: () => mockResponse({ body: { items: [] } }),
    };
    vi.stubGlobal('fetch', buildMockFetch(fetchHandlers));

    renderPage();

    await screen.findByText('owner@example.com');
    const search = screen.getByLabelText(/User email/i);
    await userEvent.type(search, 'nobody@example.com');

    expect(await screen.findByText(/Ask a super admin to create the account/i)).toBeInTheDocument();
  });

  it('should POST an assignment when the user picks a candidate and submits', async () => {
    let capturedCall: FetchCall | null = null;
    const fetchHandlers: Record<string, (init?: RequestInit) => Response> = {
      [WORKSPACES_URL]: () => mockResponse({ body: [{ id: 'ws-1', name: 'Shared' }] }),
      [ME_URL]: () => mockResponse({ body: superAdmin }),
      [MEMBERS_URL]: () => mockResponse({ body: { items: [ownerMember] } }),
      [VISIBLE_URL]: () =>
        mockResponse({
          body: {
            items: [
              {
                id: '00000000-0000-0000-0000-000000000003',
                email: 'new@example.com',
                is_super_admin: false,
                workspace_count: 0,
                created_at: '2026-11-07T10:00:00+00:00',
                deactivated_at: null,
              },
            ],
          },
        }),
      'POST /api/user-management/users/00000000-0000-0000-0000-000000000003/workspace-assignments':
        (init) => {
          capturedCall = [
            '/api/user-management/users/00000000-0000-0000-0000-000000000003/workspace-assignments',
            init,
          ];
          return mockResponse({ status: 201, body: {} });
        },
    };
    vi.stubGlobal('fetch', buildMockFetch(fetchHandlers));

    renderPage();

    await screen.findByText('owner@example.com');
    const search = screen.getByLabelText(/User email/i);
    await userEvent.type(search, 'new');

    const option = await screen.findByRole('option', { name: 'new@example.com' });
    await userEvent.click(option);
    await userEvent.click(screen.getByRole('button', { name: /^Add$/i }));

    await waitFor(() => expect(capturedCall).not.toBeNull());
    const body = JSON.parse(String((capturedCall![1] as RequestInit).body));
    expect(body).toEqual({ workspaceId: 'ws-1', role: 'member' });
  });

  it('should show the admin-downgrade hint when DELETE returns 422', async () => {
    const adminMember = {
      ...regularMember,
      userId: '00000000-0000-0000-0000-000000000005',
      email: 'adm@example.com',
      role: 'admin',
    };
    const fetchHandlers: Record<string, (init?: RequestInit) => Response> = {
      [WORKSPACES_URL]: () => mockResponse({ body: [{ id: 'ws-1', name: 'Shared' }] }),
      [ME_URL]: () => mockResponse({ body: superAdmin }),
      [MEMBERS_URL]: () => mockResponse({ body: { items: [ownerMember, adminMember] } }),
      'DELETE /api/user-management/users/00000000-0000-0000-0000-000000000005/workspace-assignments/ws-1':
        () =>
          mockResponse({
            status: 422,
            body: { error: { code: 'ADMIN_MUST_BE_DOWNGRADED_FIRST' } },
          }),
    };
    vi.stubGlobal('fetch', buildMockFetch(fetchHandlers));

    renderPage();

    await screen.findByText('adm@example.com');
    const row = screen.getByText('adm@example.com').closest('tr') as HTMLElement;
    await userEvent.click(
      within(row).getByRole('button', { name: /Remove member adm@example.com/i }),
    );
    await userEvent.click(screen.getByRole('button', { name: /^Remove$/i }));

    expect(
      await screen.findByText(/Downgrade the admin to Member before removing/i),
    ).toBeInTheDocument();
  });
});
