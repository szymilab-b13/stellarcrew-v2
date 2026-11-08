import { QueryClient, QueryClientProvider } from '@tanstack/react-query';
import { render, screen, within } from '@testing-library/react';
import type { ReactNode } from 'react';
import { MemoryRouter, Route, Routes } from 'react-router-dom';
import { afterEach, beforeEach, describe, expect, it, vi } from 'vitest';

import '@/i18n/i18n';
import { clearAuthToken, writeAuthToken } from '@/lib/authToken';

import { WorkspaceOverviewPage } from './WorkspaceOverviewPage';

function mockResponse(body: unknown, status = 200): Response {
  return new Response(body == null ? null : JSON.stringify(body), {
    status,
    headers: { 'content-type': 'application/json' },
  });
}

function renderPage(initialPath = '/workspace/ws-1/overview') {
  const queryClient = new QueryClient({
    defaultOptions: { queries: { retry: false }, mutations: { retry: false } },
  });
  function Wrapper({ children }: { children: ReactNode }) {
    return (
      <QueryClientProvider client={queryClient}>
        <MemoryRouter initialEntries={[initialPath]}>
          <Routes>
            <Route path="/workspace/:wsId/overview" element={children} />
            <Route path="/workspaces" element={<div>workspaces fallback</div>} />
          </Routes>
        </MemoryRouter>
      </QueryClientProvider>
    );
  }
  return render(<WorkspaceOverviewPage />, { wrapper: Wrapper });
}

describe('WorkspaceOverviewPage', () => {
  beforeEach(() => {
    writeAuthToken('test-token');
  });

  afterEach(() => {
    clearAuthToken();
    vi.unstubAllGlobals();
    vi.restoreAllMocks();
  });

  it('should render the overview heading when the workspace is loaded', async () => {
    vi.stubGlobal(
      'fetch',
      vi.fn(() => Promise.resolve(mockResponse([{ id: 'ws-1', name: 'Shared Workspace' }]))),
    );

    renderPage();

    expect(await screen.findByRole('heading', { name: /overview/i })).toBeInTheDocument();
    expect(await screen.findByText('Shared Workspace')).toBeInTheDocument();
  });

  it('should render a 2-row widget grid with 5 slots in row 1 and 3 slots in row 2', async () => {
    vi.stubGlobal(
      'fetch',
      vi.fn(() => Promise.resolve(mockResponse([{ id: 'ws-1', name: 'Shared Workspace' }]))),
    );

    renderPage();

    await screen.findByRole('heading', { name: /overview/i });

    const row1 = screen.getByRole('region', { name: /row 1/i });
    const row2 = screen.getByRole('region', { name: /row 2/i });
    expect(within(row1).getAllByRole('article')).toHaveLength(5);
    expect(within(row2).getAllByRole('article')).toHaveLength(3);
  });

  it('should render a PROTOTYP badge and BL reference for each stub widget slot', async () => {
    vi.stubGlobal(
      'fetch',
      vi.fn(() => Promise.resolve(mockResponse([{ id: 'ws-1', name: 'Shared Workspace' }]))),
    );

    renderPage();

    await screen.findByRole('heading', { name: /overview/i });

    expect(screen.getAllByText('PROTOTYP')).toHaveLength(8);
    for (const bl of ['BL-WSUX-009', 'BL-WSUX-010', 'BL-WSUX-011', 'BL-WSUX-012']) {
      expect(screen.getByText(new RegExp(bl))).toBeInTheDocument();
    }
    expect(screen.queryByText(/BL-WSUX-005/)).not.toBeInTheDocument();
    expect(screen.queryByText(/BL-WSUX-006/)).not.toBeInTheDocument();
    expect(screen.queryByText(/BL-WSUX-007/)).not.toBeInTheDocument();
    expect(screen.queryByText(/BL-WSUX-008/)).not.toBeInTheDocument();
    expect(screen.getAllByText(/Coming after AGENTS milestone/i).length).toBeGreaterThanOrEqual(1);
  });

  it('should render localized widget titles for each of the 8 slots', async () => {
    vi.stubGlobal(
      'fetch',
      vi.fn(() => Promise.resolve(mockResponse([{ id: 'ws-1', name: 'Shared Workspace' }]))),
    );

    renderPage();

    await screen.findByRole('heading', { name: /overview/i });

    for (const title of [
      'Active Tasks',
      'Active Agents',
      'Tasks Progress',
      'Milestones',
      'Projects',
      'Boss Actions',
      'Last Agents Activity',
      'Workspace Info',
    ]) {
      expect(screen.getByRole('heading', { level: 2, name: title })).toBeInTheDocument();
    }
  });

  it('should redirect to /workspaces when wsId is missing from the loaded list', async () => {
    vi.stubGlobal(
      'fetch',
      vi.fn(() => Promise.resolve(mockResponse([]))),
    );

    renderPage('/workspace/unknown/overview');

    expect(await screen.findByText('workspaces fallback')).toBeInTheDocument();
  });
});
