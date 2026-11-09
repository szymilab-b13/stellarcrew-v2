import { QueryClient, QueryClientProvider } from '@tanstack/react-query';
import { render, screen } from '@testing-library/react';
import type { ReactNode } from 'react';
import { MemoryRouter, Navigate, Route, Routes } from 'react-router-dom';
import { afterEach, beforeEach, describe, expect, it, vi } from 'vitest';

import '@/i18n/i18n';
import { clearAuthToken, writeAuthToken } from '@/lib/authToken';

import { ProjectOverviewPage } from './ProjectOverviewPage';

function mockResponse(body: unknown, status = 200): Response {
  return new Response(body == null ? null : JSON.stringify(body), {
    status,
    headers: { 'content-type': 'application/json' },
  });
}

const workspace = { id: 'ws-1', name: 'Shared Workspace' };

const project = {
  id: '33333333-3333-4333-8333-333333333333',
  workspaceId: 'ws-1',
  name: 'Talent Bridge',
  slug: 'talent-bridge',
  description: 'HR integration layer.',
  createdAt: '2026-11-01T10:00:00+00:00',
  updatedAt: '2026-11-05T12:00:00+00:00',
};

interface MakeFetchOptions {
  workspaces?: Array<{ id: string; name: string }>;
  project?: unknown;
  projectStatus?: number;
}

function makeFetch({
  workspaces = [workspace],
  project: projectBody = project,
  projectStatus = 200,
}: MakeFetchOptions = {}) {
  return vi.fn(async (input: RequestInfo | URL) => {
    const url = typeof input === 'string' ? input : input.toString();
    if (url === '/api/workspaces') {
      return mockResponse(workspaces);
    }
    if (url.startsWith('/api/projects/')) {
      return mockResponse(projectBody, projectStatus);
    }
    return mockResponse({}, 404);
  });
}

function renderPage(initialPath = `/workspace/ws-1/project/${project.id}/overview`) {
  const queryClient = new QueryClient({
    defaultOptions: { queries: { retry: false }, mutations: { retry: false } },
  });
  function Wrapper({ children }: { children: ReactNode }) {
    return (
      <QueryClientProvider client={queryClient}>
        <MemoryRouter initialEntries={[initialPath]}>
          <Routes>
            <Route path="/workspace/:wsId/project/:projId/overview" element={children} />
            <Route
              path="/workspace/:wsId/projects"
              element={<div>workspace projects fallback</div>}
            />
            <Route path="/workspaces" element={<div>workspaces fallback</div>} />
          </Routes>
        </MemoryRouter>
      </QueryClientProvider>
    );
  }
  return render(<ProjectOverviewPage />, { wrapper: Wrapper });
}

describe('ProjectOverviewPage', () => {
  beforeEach(() => {
    writeAuthToken('test-token');
  });

  afterEach(() => {
    clearAuthToken();
    vi.unstubAllGlobals();
    vi.restoreAllMocks();
  });

  it('should render the project name, slug and description when loaded', async () => {
    vi.stubGlobal('fetch', makeFetch());

    renderPage();

    expect(
      await screen.findByRole('heading', { level: 1, name: 'Talent Bridge' }),
    ).toBeInTheDocument();
    expect(screen.getByText('talent-bridge')).toBeInTheDocument();
    expect(screen.getByText('HR integration layer.')).toBeInTheDocument();
  });

  it('should render all six overview shell slots', async () => {
    vi.stubGlobal('fetch', makeFetch());

    renderPage();

    await screen.findByRole('heading', { level: 1, name: 'Talent Bridge' });
    expect(screen.getAllByRole('article')).toHaveLength(6);
  });

  it('should redirect to the workspace projects page when the project is 404', async () => {
    vi.stubGlobal('fetch', makeFetch({ project: { error: 'not_found' }, projectStatus: 404 }));

    renderPage();

    expect(await screen.findByText('workspace projects fallback')).toBeInTheDocument();
  });

  it('should render an error alert when the project request fails with 500', async () => {
    vi.stubGlobal('fetch', makeFetch({ project: { error: 'server' }, projectStatus: 500 }));

    renderPage();

    const alert = await screen.findByRole('alert');
    expect(alert).toHaveTextContent(/Could not load the project/i);
  });

  it('should redirect to /workspaces when the workspace is not in the loaded list', async () => {
    vi.stubGlobal('fetch', makeFetch({ workspaces: [] }));

    renderPage();

    expect(await screen.findByText('workspaces fallback')).toBeInTheDocument();
  });

  it('should redirect /workspace/:wsId/project/:projId to /overview', async () => {
    vi.stubGlobal('fetch', makeFetch());

    const queryClient = new QueryClient({
      defaultOptions: { queries: { retry: false }, mutations: { retry: false } },
    });
    render(
      <QueryClientProvider client={queryClient}>
        <MemoryRouter initialEntries={[`/workspace/ws-1/project/${project.id}`]}>
          <Routes>
            <Route path="/workspace/:wsId/project/:projId" element={<ProjectRedirectForTests />} />
            <Route
              path="/workspace/:wsId/project/:projId/overview"
              element={<ProjectOverviewPage />}
            />
            <Route path="/workspaces" element={<div>workspaces fallback</div>} />
          </Routes>
        </MemoryRouter>
      </QueryClientProvider>,
    );

    expect(
      await screen.findByRole('heading', { level: 1, name: 'Talent Bridge' }),
    ).toBeInTheDocument();
  });
});

function ProjectRedirectForTests() {
  return <Navigate to="overview" replace />;
}
