import { QueryClient, QueryClientProvider } from '@tanstack/react-query';
import { render, screen } from '@testing-library/react';
import userEvent from '@testing-library/user-event';
import type { ReactNode } from 'react';
import { MemoryRouter, Route, Routes } from 'react-router-dom';
import { afterEach, beforeEach, describe, expect, it, vi } from 'vitest';

import { writeAuthToken, clearAuthToken } from '@/lib/authToken';
import { useCurrentWorkspace } from '@/features/workspaces/state/useCurrentWorkspace';
import type { Workspace } from '@/features/workspaces/types';

import { WorkspaceSwitcher } from './WorkspaceSwitcher';

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
};

const workspaceBeta: Workspace = {
  id: '22222222-2222-2222-2222-222222222222',
  name: 'Beta',
  slug: 'beta',
  iconColor: '#3B82F6',
  iconInitials: 'BE',
  ownerId: '00000000-0000-0000-0000-000000000001',
};

function renderSwitcher(options: { collapsed?: boolean; initialPath?: string } = {}) {
  const { collapsed = false, initialPath = '/dashboard' } = options;
  const queryClient = new QueryClient({
    defaultOptions: { queries: { retry: false }, mutations: { retry: false } },
  });

  function Wrapper({ children }: { children: ReactNode }) {
    return <QueryClientProvider client={queryClient}>{children}</QueryClientProvider>;
  }

  return render(
    <MemoryRouter initialEntries={[initialPath]}>
      <Routes>
        <Route path="/dashboard" element={<WorkspaceSwitcher collapsed={collapsed} />} />
        <Route path="/workspaces" element={<div>Workspaces Landing</div>} />
      </Routes>
    </MemoryRouter>,
    { wrapper: Wrapper },
  );
}

describe('WorkspaceSwitcher', () => {
  beforeEach(() => {
    writeAuthToken('test-token');
    window.localStorage.removeItem('sc-current-workspace');
    useCurrentWorkspace.setState({ currentWorkspaceId: null });
    vi.stubGlobal('fetch', vi.fn());
  });

  afterEach(() => {
    clearAuthToken();
    window.localStorage.removeItem('sc-current-workspace');
    useCurrentWorkspace.setState({ currentWorkspaceId: null });
    vi.unstubAllGlobals();
    vi.restoreAllMocks();
  });

  it('should render current workspace name when selection matches the list', async () => {
    useCurrentWorkspace.setState({ currentWorkspaceId: workspaceBeta.id });
    vi.mocked(fetch).mockResolvedValueOnce(mockResponse({ body: [workspaceAlpha, workspaceBeta] }));

    renderSwitcher();

    expect(await screen.findByRole('button', { name: /beta/i })).toBeInTheDocument();
  });

  it('should open the menu and list all workspaces plus manage link', async () => {
    useCurrentWorkspace.setState({ currentWorkspaceId: workspaceAlpha.id });
    vi.mocked(fetch).mockResolvedValueOnce(mockResponse({ body: [workspaceAlpha, workspaceBeta] }));

    renderSwitcher();
    const user = userEvent.setup();

    const toggle = await screen.findByRole('button', { name: /alpha/i });
    await user.click(toggle);

    const menu = await screen.findByRole('menu');
    expect(menu).toBeInTheDocument();
    expect(screen.getByRole('menuitemradio', { name: /alpha/i })).toBeInTheDocument();
    expect(screen.getByRole('menuitemradio', { name: /beta/i })).toBeInTheDocument();
    expect(screen.getByRole('menuitem', { name: /manage workspaces/i })).toHaveAttribute(
      'href',
      '/workspaces',
    );
  });

  it('should update current workspace in store and close menu on selection', async () => {
    useCurrentWorkspace.setState({ currentWorkspaceId: workspaceAlpha.id });
    vi.mocked(fetch).mockResolvedValueOnce(mockResponse({ body: [workspaceAlpha, workspaceBeta] }));

    renderSwitcher();
    const user = userEvent.setup();

    await user.click(await screen.findByRole('button', { name: /alpha/i }));
    await user.click(await screen.findByRole('menuitemradio', { name: /beta/i }));

    expect(useCurrentWorkspace.getState().currentWorkspaceId).toBe(workspaceBeta.id);
    expect(screen.queryByRole('menu')).not.toBeInTheDocument();
  });

  it('should render empty state when store has no current workspace', async () => {
    vi.mocked(fetch).mockResolvedValueOnce(mockResponse({ body: [workspaceAlpha, workspaceBeta] }));

    renderSwitcher();

    expect(await screen.findByRole('button', { name: /select workspace/i })).toBeInTheDocument();
  });

  it('should show the role pill for the current workspace and each menu item', async () => {
    const alphaWithRole: Workspace = { ...workspaceAlpha, role: 'owner' };
    const betaWithRole: Workspace = { ...workspaceBeta, role: 'admin' };
    useCurrentWorkspace.setState({ currentWorkspaceId: alphaWithRole.id });
    vi.mocked(fetch).mockResolvedValueOnce(mockResponse({ body: [alphaWithRole, betaWithRole] }));

    renderSwitcher();
    const user = userEvent.setup();

    expect(await screen.findByTestId('role-pill-owner')).toBeInTheDocument();

    await user.click(await screen.findByRole('button', { name: /alpha/i }));

    const menu = await screen.findByRole('menu');
    expect(menu).toContainElement(screen.getAllByTestId('role-pill-owner')[1] ?? null);
    expect(menu).toContainElement(screen.getByTestId('role-pill-admin'));
  });

  it('should render only the initials tile when collapsed', async () => {
    useCurrentWorkspace.setState({ currentWorkspaceId: workspaceAlpha.id });
    vi.mocked(fetch).mockResolvedValueOnce(mockResponse({ body: [workspaceAlpha] }));

    renderSwitcher({ collapsed: true });

    const tile = await screen.findByTitle(/alpha/i);
    expect(tile).toHaveTextContent('AL');
    expect(screen.queryByRole('button', { name: /chevron/i })).not.toBeInTheDocument();
  });
});
