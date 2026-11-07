import { QueryClient, QueryClientProvider } from '@tanstack/react-query';
import { render, screen, waitFor } from '@testing-library/react';
import type { ReactNode } from 'react';
import { MemoryRouter, Route, Routes } from 'react-router-dom';
import { afterEach, beforeEach, describe, expect, it, vi } from 'vitest';

import { writeAuthToken, clearAuthToken } from '@/lib/authToken';

import { CurrentWorkspaceGuard } from './CurrentWorkspaceGuard';
import { useCurrentWorkspace } from '../state/useCurrentWorkspace';
import type { Workspace } from '../types';

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

function renderGuard(initialPath = '/dashboard') {
  const queryClient = new QueryClient({
    defaultOptions: { queries: { retry: false }, mutations: { retry: false } },
  });

  function Wrapper({ children }: { children: ReactNode }) {
    return <QueryClientProvider client={queryClient}>{children}</QueryClientProvider>;
  }

  return render(
    <MemoryRouter initialEntries={[initialPath]}>
      <Routes>
        <Route element={<CurrentWorkspaceGuard />}>
          <Route path="/dashboard" element={<div>Protected Dashboard</div>} />
        </Route>
        <Route path="/workspaces" element={<div>Workspaces Landing</div>} />
      </Routes>
    </MemoryRouter>,
    { wrapper: Wrapper },
  );
}

describe('CurrentWorkspaceGuard', () => {
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

  it('should renderOutletWhenUserHasExactlyOneWorkspace', async () => {
    vi.mocked(fetch).mockResolvedValueOnce(mockResponse({ body: [workspaceAlpha] }));

    renderGuard();

    expect(await screen.findByText('Protected Dashboard')).toBeInTheDocument();
    await waitFor(() => {
      expect(useCurrentWorkspace.getState().currentWorkspaceId).toBe(workspaceAlpha.id);
    });
  });

  it('should redirectToWorkspacesWhenUserHasMultipleWorkspacesAndNoSelection', async () => {
    vi.mocked(fetch).mockResolvedValueOnce(mockResponse({ body: [workspaceAlpha, workspaceBeta] }));

    renderGuard();

    expect(await screen.findByText('Workspaces Landing')).toBeInTheDocument();
    expect(useCurrentWorkspace.getState().currentWorkspaceId).toBeNull();
  });

  it('should redirectToWorkspacesWhenUserHasZeroWorkspaces', async () => {
    vi.mocked(fetch).mockResolvedValueOnce(mockResponse({ body: [] }));

    renderGuard();

    expect(await screen.findByText('Workspaces Landing')).toBeInTheDocument();
  });

  it('should renderOutletWhenStoredSelectionExistsInList', async () => {
    useCurrentWorkspace.setState({ currentWorkspaceId: workspaceBeta.id });
    vi.mocked(fetch).mockResolvedValueOnce(mockResponse({ body: [workspaceAlpha, workspaceBeta] }));

    renderGuard();

    expect(await screen.findByText('Protected Dashboard')).toBeInTheDocument();
    expect(useCurrentWorkspace.getState().currentWorkspaceId).toBe(workspaceBeta.id);
  });

  it('should clearSelectionAndRedirectWhenStoredWorkspaceNoLongerExists', async () => {
    useCurrentWorkspace.setState({ currentWorkspaceId: 'deleted-id' });
    vi.mocked(fetch).mockResolvedValueOnce(mockResponse({ body: [workspaceAlpha, workspaceBeta] }));

    renderGuard();

    expect(await screen.findByText('Workspaces Landing')).toBeInTheDocument();
    await waitFor(() => {
      expect(useCurrentWorkspace.getState().currentWorkspaceId).toBeNull();
    });
  });

  it('should showErrorAlertWhenWorkspacesRequestFails', async () => {
    vi.mocked(fetch).mockResolvedValueOnce(mockResponse({ status: 500, body: {} }));

    renderGuard();

    expect(await screen.findByRole('alert')).toBeInTheDocument();
  });
});
