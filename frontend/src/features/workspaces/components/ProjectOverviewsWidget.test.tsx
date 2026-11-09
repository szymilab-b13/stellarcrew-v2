import { QueryClient, QueryClientProvider } from '@tanstack/react-query';
import { render, screen, waitFor, within } from '@testing-library/react';
import type { ReactNode } from 'react';
import { MemoryRouter } from 'react-router-dom';
import { afterEach, beforeEach, describe, expect, it, vi } from 'vitest';

import '@/i18n/i18n';
import { clearAuthToken, writeAuthToken } from '@/lib/authToken';

import { ProjectOverviewsWidget } from './ProjectOverviewsWidget';

function mockResponse(body: unknown, status = 200): Response {
  return new Response(body == null ? null : JSON.stringify(body), {
    status,
    headers: { 'content-type': 'application/json' },
  });
}

function sampleProject(index: number, overrides: Partial<Record<string, unknown>> = {}) {
  return {
    id: `${index}`.padStart(8, '0') + '-0000-4000-8000-000000000000',
    workspaceId: 'ws-1',
    name: `Project ${index}`,
    slug: `project-${index}`,
    description: `Description ${index}`,
    createdAt: '2026-11-03T10:00:00+00:00',
    updatedAt: '2026-11-03T10:00:00+00:00',
    ...overrides,
  };
}

function renderWidget(workspaceId = 'ws-1') {
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
  return render(<ProjectOverviewsWidget workspaceId={workspaceId} />, { wrapper: Wrapper });
}

describe('ProjectOverviewsWidget', () => {
  beforeEach(() => {
    writeAuthToken('test-token');
  });

  afterEach(() => {
    clearAuthToken();
    vi.unstubAllGlobals();
    vi.restoreAllMocks();
  });

  it('should render the Projects Overview heading and Open all link', async () => {
    vi.stubGlobal(
      'fetch',
      vi.fn(() => Promise.resolve(mockResponse({ items: [] }))),
    );

    renderWidget('ws-abc');

    expect(
      await screen.findByRole('heading', { level: 2, name: 'Projects Overview' }),
    ).toBeInTheDocument();
    const openAll = screen.getByRole('link', { name: /open all projects/i });
    expect(openAll).toHaveAttribute('href', '/workspace/ws-abc/projects');
  });

  it('should render one project tile per returned project plus a Show more tile', async () => {
    vi.stubGlobal(
      'fetch',
      vi.fn(() =>
        Promise.resolve(
          mockResponse({
            items: [sampleProject(1, { name: 'Alpha' }), sampleProject(2, { name: 'Beta' })],
          }),
        ),
      ),
    );

    renderWidget('ws-1');

    const list = await screen.findByRole('list');
    const items = within(list).getAllByRole('listitem');
    expect(items).toHaveLength(3);
    expect(within(list).getByRole('heading', { level: 2, name: 'Alpha' })).toBeInTheDocument();
    expect(within(list).getByRole('heading', { level: 2, name: 'Beta' })).toBeInTheDocument();
    const showMore = within(list).getByRole('link', { name: /show more projects/i });
    expect(showMore).toHaveAttribute('href', '/workspace/ws-1/projects');
  });

  it('should cap the preview at 7 project tiles even when more are returned', async () => {
    vi.stubGlobal(
      'fetch',
      vi.fn(() =>
        Promise.resolve(
          mockResponse({
            items: Array.from({ length: 10 }, (_, index) => sampleProject(index + 1)),
          }),
        ),
      ),
    );

    renderWidget();

    const list = await screen.findByRole('list');
    const items = within(list).getAllByRole('listitem');
    expect(items).toHaveLength(8);
  });

  it('should render an empty message when the workspace has no projects', async () => {
    vi.stubGlobal(
      'fetch',
      vi.fn(() => Promise.resolve(mockResponse({ items: [] }))),
    );

    renderWidget();

    expect(await screen.findByText(/No projects in this workspace yet/i)).toBeInTheDocument();
  });

  it('should render an error alert when the request fails', async () => {
    vi.stubGlobal(
      'fetch',
      vi.fn(() => Promise.resolve(mockResponse(null, 500))),
    );

    renderWidget();

    const alert = await screen.findByRole('alert');
    expect(alert).toHaveTextContent(/Could not load projects/i);
  });

  it('should keep the ProjectTile children out of the article role count', async () => {
    vi.stubGlobal(
      'fetch',
      vi.fn(() =>
        Promise.resolve(
          mockResponse({
            items: [sampleProject(1), sampleProject(2)],
          }),
        ),
      ),
    );

    renderWidget();

    await waitFor(() =>
      expect(screen.getAllByRole('heading', { level: 2 }).length).toBeGreaterThan(1),
    );
    expect(screen.getAllByRole('article')).toHaveLength(1);
  });
});
