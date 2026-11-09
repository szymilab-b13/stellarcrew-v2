import { QueryClient, QueryClientProvider } from '@tanstack/react-query';
import { render, screen } from '@testing-library/react';
import type { ReactNode } from 'react';
import { MemoryRouter, Route, Routes } from 'react-router-dom';
import { afterEach, beforeEach, describe, expect, it, vi } from 'vitest';

import '@/i18n/i18n';
import { clearAuthToken, writeAuthToken } from '@/lib/authToken';

import { WorkspaceProjectsPage } from './WorkspaceProjectsPage';

function mockResponse(body: unknown, status = 200): Response {
  return new Response(body == null ? null : JSON.stringify(body), {
    status,
    headers: { 'content-type': 'application/json' },
  });
}

function renderPage(initialPath = '/workspace/ws-1/projects') {
  const queryClient = new QueryClient({
    defaultOptions: { queries: { retry: false }, mutations: { retry: false } },
  });
  function Wrapper({ children }: { children: ReactNode }) {
    return (
      <QueryClientProvider client={queryClient}>
        <MemoryRouter initialEntries={[initialPath]}>
          <Routes>
            <Route path="/workspace/:wsId/projects" element={children} />
            <Route path="/workspaces" element={<div>workspaces fallback</div>} />
          </Routes>
        </MemoryRouter>
      </QueryClientProvider>
    );
  }
  return render(<WorkspaceProjectsPage />, { wrapper: Wrapper });
}

describe('WorkspaceProjectsPage', () => {
  beforeEach(() => {
    writeAuthToken('test-token');
  });

  afterEach(() => {
    clearAuthToken();
    vi.unstubAllGlobals();
    vi.restoreAllMocks();
  });

  it('should render the Projects heading when the workspace is loaded', async () => {
    vi.stubGlobal(
      'fetch',
      vi.fn(() => Promise.resolve(mockResponse([{ id: 'ws-1', name: 'Shared Workspace' }]))),
    );

    renderPage();

    expect(await screen.findByRole('heading', { name: /projects/i })).toBeInTheDocument();
    expect(await screen.findByText('Shared Workspace')).toBeInTheDocument();
  });

  it('should render the PROJECTS milestone placeholder message', async () => {
    vi.stubGlobal(
      'fetch',
      vi.fn(() => Promise.resolve(mockResponse([{ id: 'ws-1', name: 'Shared Workspace' }]))),
    );

    renderPage();

    await screen.findByRole('heading', { name: /projects/i });
    expect(screen.getByText(/Coming after PROJECTS milestone/i)).toBeInTheDocument();
  });

  it('should redirect to /workspaces when wsId is missing from the loaded list', async () => {
    vi.stubGlobal(
      'fetch',
      vi.fn(() => Promise.resolve(mockResponse([]))),
    );

    renderPage('/workspace/unknown/projects');

    expect(await screen.findByText('workspaces fallback')).toBeInTheDocument();
  });
});
