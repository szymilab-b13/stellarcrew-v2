import { QueryClient, QueryClientProvider } from '@tanstack/react-query';
import { render, screen } from '@testing-library/react';
import type { ReactNode } from 'react';
import { MemoryRouter, Route, Routes } from 'react-router-dom';
import { afterEach, beforeEach, describe, expect, it, vi } from 'vitest';

import '@/i18n/i18n';
import { clearAuthToken, writeAuthToken } from '@/lib/authToken';

import type { Agent, AgentTemplate } from '../types';
import { AgentOverviewPage } from './AgentOverviewPage';

function mockResponse(body: unknown, status = 200): Response {
  return new Response(body == null ? null : JSON.stringify(body), {
    status,
    headers: { 'content-type': 'application/json' },
  });
}

const templateMaja: AgentTemplate = {
  id: '11111111-1111-4111-8111-111111111111',
  slug: 'maja',
  name: 'Maja',
  role: 'Project Manager',
  roleDescription: 'Keeps the roadmap on track.',
  expertise: 'Coordination, planning, status reporting.',
  workflow: 'Daily check-ins, briefings, async updates.',
  style: 'Calm and direct.',
  outputFormat: 'TL;DR first, bullets after.',
  createdAt: '2026-11-01T10:00:00+00:00',
  updatedAt: '2026-11-01T10:00:00+00:00',
};

const agentFromTemplate: Agent = {
  id: '99999999-9999-4999-8999-999999999999',
  organizationId: '22222222-2222-4222-8222-222222222222',
  ownerUserId: '33333333-3333-4333-8333-333333333333',
  sourceTemplateId: templateMaja.id,
  slug: 'maja',
  name: 'Maja',
  role: 'Project Manager',
  roleDescription: 'Keeps the Alpha roadmap on track and unblocks the team.',
  expertise: 'Coordination of Alpha squad, planning, async status.',
  workflow: 'Morning briefing, midday sync, EOD summary.',
  style: 'Calm and direct, no filler.',
  outputFormat: 'TL;DR first, numbered bullets after.',
  isActive: true,
  color: '#6366f1',
  createdAt: '2026-11-15T15:00:00+00:00',
  updatedAt: '2026-11-15T15:30:00+00:00',
};

const agentScratch: Agent = {
  ...agentFromTemplate,
  id: 'aaaaaaaa-aaaa-4aaa-8aaa-aaaaaaaaaaaa',
  sourceTemplateId: null,
  slug: 'helga',
  name: 'Helga',
  role: 'QA Lead',
};

interface MakeFetchOptions {
  agent?: Agent | null;
  agentStatus?: number;
  template?: AgentTemplate | null;
  templateStatus?: number;
}

function makeFetch({
  agent = agentFromTemplate,
  agentStatus = 200,
  template = templateMaja,
  templateStatus = 200,
}: MakeFetchOptions = {}) {
  return vi.fn(async (input: RequestInfo | URL) => {
    const url = typeof input === 'string' ? input : input.toString();
    if (url.startsWith('/api/agents/') && !url.includes('from-template')) {
      return mockResponse(agent, agentStatus);
    }
    if (url.startsWith('/api/agent-templates/')) {
      return mockResponse(template, templateStatus);
    }
    return mockResponse({}, 404);
  });
}

function renderPage(initialPath = `/agents/${agentFromTemplate.id}/overview`) {
  const queryClient = new QueryClient({
    defaultOptions: { queries: { retry: false }, mutations: { retry: false } },
  });
  function Wrapper({ children }: { children: ReactNode }) {
    return (
      <QueryClientProvider client={queryClient}>
        <MemoryRouter initialEntries={[initialPath]}>
          <Routes>
            <Route path="/agents" element={<div>agents list fallback</div>} />
            <Route path="/agents/:id/overview" element={children} />
            <Route path="/agents/:id/settings" element={<div>agent settings fallback</div>} />
            <Route
              path="/agents/new/template/:slug"
              element={<div>template picker fallback</div>}
            />
          </Routes>
        </MemoryRouter>
      </QueryClientProvider>
    );
  }
  return render(<AgentOverviewPage />, { wrapper: Wrapper });
}

describe('AgentOverviewPage', () => {
  beforeEach(() => {
    writeAuthToken('test-token');
  });

  afterEach(() => {
    clearAuthToken();
    vi.unstubAllGlobals();
    vi.restoreAllMocks();
  });

  it('should render the agent name, role and five read-only sections', async () => {
    vi.stubGlobal('fetch', makeFetch());

    renderPage();

    expect(await screen.findByRole('heading', { level: 1, name: 'Maja' })).toBeInTheDocument();
    expect(screen.getByText('Project Manager')).toBeInTheDocument();
    expect(screen.getByText(agentFromTemplate.roleDescription)).toBeInTheDocument();
    expect(screen.getByText(agentFromTemplate.expertise)).toBeInTheDocument();
    expect(screen.getByText(agentFromTemplate.workflow)).toBeInTheDocument();
    expect(screen.getByText(agentFromTemplate.style)).toBeInTheDocument();
    expect(screen.getByText(agentFromTemplate.outputFormat)).toBeInTheDocument();
  });

  it('should redirect to /agents when the agent returns 404', async () => {
    vi.stubGlobal(
      'fetch',
      makeFetch({
        agent: { message: 'not found' } as unknown as Agent,
        agentStatus: 404,
      }),
    );

    renderPage('/agents/missing-id/overview');

    expect(await screen.findByText(/agents list fallback/i)).toBeInTheDocument();
  });

  it('should render a forbidden alert when the agent returns 403', async () => {
    vi.stubGlobal(
      'fetch',
      makeFetch({
        agent: { message: 'forbidden' } as unknown as Agent,
        agentStatus: 403,
      }),
    );

    renderPage();

    expect(await screen.findByRole('alert')).toHaveTextContent(/you cannot view this agent/i);
  });

  it('should render a generic load error alert when the agent request fails non-404/403', async () => {
    vi.stubGlobal(
      'fetch',
      makeFetch({
        agent: { message: 'boom' } as unknown as Agent,
        agentStatus: 500,
      }),
    );

    renderPage();

    expect(await screen.findByRole('alert')).toHaveTextContent(/could not load the agent/i);
  });

  it('should render a source template badge with a link to the template picker when sourceTemplateId is present', async () => {
    vi.stubGlobal('fetch', makeFetch());

    renderPage();

    await screen.findByRole('heading', { level: 1, name: 'Maja' });

    const link = await screen.findByRole('link', { name: /maja template/i });
    expect(link).toHaveAttribute('href', '/agents/new/template/maja');
  });

  it('should omit the source template badge when sourceTemplateId is null', async () => {
    vi.stubGlobal('fetch', makeFetch({ agent: agentScratch }));

    renderPage(`/agents/${agentScratch.id}/overview`);

    await screen.findByRole('heading', { level: 1, name: 'Helga' });
    expect(screen.queryByRole('link', { name: /template/i })).not.toBeInTheDocument();
  });

  it('should render a settings link pointing at /agents/:id/settings', async () => {
    vi.stubGlobal('fetch', makeFetch());

    renderPage();

    const settingsLink = await screen.findByRole('link', { name: /open settings/i });
    expect(settingsLink).toHaveAttribute('href', `/agents/${agentFromTemplate.id}/settings`);
  });

  it('should render the activity stub section with the coming-soon copy', async () => {
    vi.stubGlobal('fetch', makeFetch());

    renderPage();

    await screen.findByRole('heading', { level: 1, name: 'Maja' });
    expect(screen.getByRole('region', { name: /recent activity/i })).toBeInTheDocument();
    expect(screen.getByText(/coming after the chat milestone/i)).toBeInTheDocument();
  });
});
