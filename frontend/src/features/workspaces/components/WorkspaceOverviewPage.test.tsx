import { QueryClient, QueryClientProvider } from '@tanstack/react-query';
import { render, screen } from '@testing-library/react';
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

  it('should render the shell placeholder for the upcoming BL-WSUX-004 widget grid', async () => {
    vi.stubGlobal(
      'fetch',
      vi.fn(() => Promise.resolve(mockResponse([{ id: 'ws-1', name: 'Shared Workspace' }]))),
    );

    renderPage();

    expect(await screen.findByText(/coming after the workspace_ux milestone/i)).toBeInTheDocument();
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
