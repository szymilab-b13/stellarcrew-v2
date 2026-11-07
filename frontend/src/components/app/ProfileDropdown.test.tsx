import { QueryClient, QueryClientProvider } from '@tanstack/react-query';
import { render, screen } from '@testing-library/react';
import userEvent from '@testing-library/user-event';
import type { ReactNode } from 'react';
import { MemoryRouter } from 'react-router-dom';
import { afterEach, beforeEach, describe, expect, it, vi } from 'vitest';

import { clearAuthToken, writeAuthToken } from '@/lib/authToken';
import type { Workspace } from '@/features/workspaces/types';

import { ProfileDropdown } from './ProfileDropdown';

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

const workspaceAlpha: Workspace = {
  id: '11111111-1111-1111-1111-111111111111',
  name: 'Alpha',
  slug: 'alpha',
  iconColor: '#8B5CF6',
  iconInitials: 'AL',
  ownerId: '00000000-0000-0000-0000-000000000001',
  role: 'owner',
};

const workspaceBeta: Workspace = {
  id: '22222222-2222-2222-2222-222222222222',
  name: 'Beta',
  slug: 'beta',
  iconColor: '#3B82F6',
  iconInitials: 'BE',
  ownerId: '00000000-0000-0000-0000-000000000001',
  role: 'admin',
};

const meUser = {
  email: 'me@example.com',
  display_name: null,
  role: 'ROLE_USER',
};

function renderDropdown() {
  const queryClient = new QueryClient({
    defaultOptions: { queries: { retry: false }, mutations: { retry: false } },
  });

  function Wrapper({ children }: { children: ReactNode }) {
    return (
      <QueryClientProvider client={queryClient}>
        <MemoryRouter>{children}</MemoryRouter>
      </QueryClientProvider>
    );
  }

  return render(<ProfileDropdown collapsed={false} />, { wrapper: Wrapper });
}

describe('ProfileDropdown', () => {
  beforeEach(() => {
    writeAuthToken('test-token');
    vi.stubGlobal('fetch', vi.fn());
  });

  afterEach(() => {
    clearAuthToken();
    vi.unstubAllGlobals();
    vi.restoreAllMocks();
  });

  it('opens the panel and lists my workspaces with role pills', async () => {
    vi.mocked(fetch).mockImplementation(async (input: RequestInfo | URL) => {
      const url = String(input);
      if (url.includes('/api/auth/me')) {
        return mockResponse({ body: meUser });
      }
      if (url.includes('/api/workspaces')) {
        return mockResponse({ body: [workspaceAlpha, workspaceBeta] });
      }
      return mockResponse({ status: 404, body: {} });
    });

    renderDropdown();
    const user = userEvent.setup();

    const trigger = await screen.findByRole('button', { name: /me@example.com/i });
    await user.click(trigger);

    const menu = await screen.findByRole('menu', { name: /profile/i });
    expect(menu).toBeInTheDocument();
    expect(screen.getByText(/alpha/i)).toBeInTheDocument();
    expect(screen.getByText(/beta/i)).toBeInTheDocument();
    expect(screen.getByTestId('role-pill-owner')).toBeInTheDocument();
    expect(screen.getByTestId('role-pill-admin')).toBeInTheDocument();
  });

  it('shows an empty state when the user has no workspaces', async () => {
    vi.mocked(fetch).mockImplementation(async (input: RequestInfo | URL) => {
      const url = String(input);
      if (url.includes('/api/auth/me')) {
        return mockResponse({ body: meUser });
      }
      if (url.includes('/api/workspaces')) {
        return mockResponse({ body: [] });
      }
      return mockResponse({ status: 404, body: {} });
    });

    renderDropdown();
    const user = userEvent.setup();

    await user.click(await screen.findByRole('button', { name: /me@example.com/i }));

    expect(await screen.findByText(/no workspaces yet/i)).toBeInTheDocument();
  });
});
