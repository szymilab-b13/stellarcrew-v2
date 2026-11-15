import { QueryClient, QueryClientProvider } from '@tanstack/react-query';
import { render, screen } from '@testing-library/react';
import userEvent from '@testing-library/user-event';
import type { ReactNode } from 'react';
import { MemoryRouter, Route, Routes } from 'react-router-dom';
import { afterEach, beforeEach, describe, expect, it, vi } from 'vitest';

import '@/i18n/i18n';
import { clearAuthToken, writeAuthToken } from '@/lib/authToken';

import { AgentsListPage } from './AgentsListPage';

function mockResponse(body: unknown, status = 200): Response {
  return new Response(body == null ? null : JSON.stringify(body), {
    status,
    headers: { 'content-type': 'application/json' },
  });
}

function makeFetch(agentsBody: unknown, agentsStatus = 200) {
  return vi.fn(async (input: RequestInfo | URL) => {
    const url = typeof input === 'string' ? input : input.toString();
    if (url === '/api/agents') {
      return mockResponse(agentsBody, agentsStatus);
    }
    return mockResponse({}, 404);
  });
}

function renderPage(initialPath = '/agents') {
  const queryClient = new QueryClient({
    defaultOptions: { queries: { retry: false }, mutations: { retry: false } },
  });
  function Wrapper({ children }: { children: ReactNode }) {
    return (
      <QueryClientProvider client={queryClient}>
        <MemoryRouter initialEntries={[initialPath]}>
          <Routes>
            <Route path="/agents" element={children} />
            <Route path="/agents/new" element={<div>choose path</div>} />
          </Routes>
        </MemoryRouter>
      </QueryClientProvider>
    );
  }
  return render(<AgentsListPage />, { wrapper: Wrapper });
}

describe('AgentsListPage', () => {
  beforeEach(() => {
    writeAuthToken('test-token');
  });

  afterEach(() => {
    clearAuthToken();
    vi.unstubAllGlobals();
    vi.restoreAllMocks();
  });

  it('should render the Agents heading', async () => {
    vi.stubGlobal('fetch', makeFetch([]));

    renderPage();

    expect(await screen.findByRole('heading', { name: /agents/i })).toBeInTheDocument();
  });

  it('should render an AgentTile for each returned agent', async () => {
    vi.stubGlobal(
      'fetch',
      makeFetch([
        {
          id: '11111111-1111-4111-8111-111111111111',
          organizationId: 'org-1',
          ownerUserId: 'user-1',
          sourceTemplateId: null,
          slug: 'alpha',
          name: 'Alpha',
          role: 'Builder',
          roleDescription: '',
          expertise: '',
          workflow: '',
          style: '',
          outputFormat: '',
          isActive: true,
          createdAt: '2026-11-01T10:00:00+00:00',
          updatedAt: '2026-11-01T10:00:00+00:00',
        },
        {
          id: '22222222-2222-4222-8222-222222222222',
          organizationId: 'org-1',
          ownerUserId: 'user-1',
          sourceTemplateId: 'tpl-1',
          slug: 'beta',
          name: 'Beta',
          role: 'Reviewer',
          roleDescription: '',
          expertise: '',
          workflow: '',
          style: '',
          outputFormat: '',
          isActive: true,
          createdAt: '2026-11-05T10:00:00+00:00',
          updatedAt: '2026-11-05T10:00:00+00:00',
        },
      ]),
    );

    renderPage();

    expect(await screen.findByRole('heading', { level: 2, name: 'Alpha' })).toBeInTheDocument();
    expect(screen.getByRole('heading', { level: 2, name: 'Beta' })).toBeInTheDocument();
    expect(screen.getByText(/From template/i)).toBeInTheDocument();
  });

  it('should render the empty state when no agents exist', async () => {
    vi.stubGlobal('fetch', makeFetch([]));

    renderPage();

    expect(await screen.findByText(/No agents yet/i)).toBeInTheDocument();
  });

  it('should render an error alert when the agents request fails', async () => {
    vi.stubGlobal('fetch', makeFetch(null, 500));

    renderPage();

    const alert = await screen.findByRole('alert');
    expect(alert).toHaveTextContent(/Could not load agents/i);
  });

  it('should navigate to /agents/new when the Add agent button is clicked in the empty state', async () => {
    vi.stubGlobal('fetch', makeFetch([]));

    renderPage();
    const user = userEvent.setup();

    const addButtons = await screen.findAllByRole('button', { name: /add agent/i });
    await user.click(addButtons[0]);

    expect(await screen.findByText('choose path')).toBeInTheDocument();
  });

  it('should navigate to /agents/new when the Add tile is clicked in the grid', async () => {
    vi.stubGlobal(
      'fetch',
      makeFetch([
        {
          id: '11111111-1111-4111-8111-111111111111',
          organizationId: 'org-1',
          ownerUserId: 'user-1',
          sourceTemplateId: null,
          slug: 'alpha',
          name: 'Alpha',
          role: 'Builder',
          roleDescription: '',
          expertise: '',
          workflow: '',
          style: '',
          outputFormat: '',
          isActive: true,
          createdAt: '2026-11-01T10:00:00+00:00',
          updatedAt: '2026-11-01T10:00:00+00:00',
        },
      ]),
    );

    renderPage();
    const user = userEvent.setup();

    await screen.findByRole('heading', { level: 2, name: 'Alpha' });
    const addButtons = screen.getAllByRole('button', { name: /add agent/i });
    const addTile = addButtons[addButtons.length - 1];
    await user.click(addTile);

    expect(await screen.findByText('choose path')).toBeInTheDocument();
  });
});
