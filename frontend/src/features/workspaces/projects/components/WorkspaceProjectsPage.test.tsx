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

const workspace = { id: 'ws-1', name: 'Shared Workspace' };

function makeFetch(projectsBody: unknown, projectsStatus = 200) {
  return vi.fn(async (input: RequestInfo | URL) => {
    const url = typeof input === 'string' ? input : input.toString();
    if (url === '/api/workspaces') {
      return mockResponse([workspace]);
    }
    if (url === '/api/workspaces/ws-1/projects') {
      return mockResponse(projectsBody, projectsStatus);
    }
    return mockResponse({}, 404);
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
    vi.stubGlobal('fetch', makeFetch({ items: [] }));

    renderPage();

    expect(await screen.findByRole('heading', { name: /projects/i })).toBeInTheDocument();
    expect(await screen.findByText('Shared Workspace')).toBeInTheDocument();
  });

  it('should render a ProjectTile for each returned project', async () => {
    vi.stubGlobal(
      'fetch',
      makeFetch({
        items: [
          {
            id: '11111111-1111-4111-8111-111111111111',
            workspaceId: 'ws-1',
            name: 'Talent Bridge',
            slug: 'talent-bridge',
            description: 'HR integration layer.',
            createdAt: '2026-11-01T10:00:00+00:00',
            updatedAt: '2026-11-01T10:00:00+00:00',
          },
          {
            id: '22222222-2222-4222-8222-222222222222',
            workspaceId: 'ws-1',
            name: 'StellarCrew Core',
            slug: 'stellarcrew-core',
            description: null,
            createdAt: '2026-11-05T10:00:00+00:00',
            updatedAt: '2026-11-05T10:00:00+00:00',
          },
        ],
      }),
    );

    renderPage();

    expect(
      await screen.findByRole('heading', { level: 2, name: 'Talent Bridge' }),
    ).toBeInTheDocument();
    expect(screen.getByRole('heading', { level: 2, name: 'StellarCrew Core' })).toBeInTheDocument();
    expect(screen.getByText('HR integration layer.')).toBeInTheDocument();
  });

  it('should render the empty state when the workspace has no projects', async () => {
    vi.stubGlobal('fetch', makeFetch({ items: [] }));

    renderPage();

    await screen.findByRole('heading', { name: /projects/i });
    expect(await screen.findByText(/No projects yet/i)).toBeInTheDocument();
  });

  it('should render an error alert when the projects request fails', async () => {
    vi.stubGlobal('fetch', makeFetch(null, 500));

    renderPage();

    const alert = await screen.findByRole('alert');
    expect(alert).toHaveTextContent(/Could not load projects/i);
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
