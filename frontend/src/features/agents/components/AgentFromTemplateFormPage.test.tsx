import { QueryClient, QueryClientProvider } from '@tanstack/react-query';
import { render, screen } from '@testing-library/react';
import userEvent from '@testing-library/user-event';
import type { ReactNode } from 'react';
import { MemoryRouter, Route, Routes, useLocation } from 'react-router-dom';
import { afterEach, beforeEach, describe, expect, it, vi } from 'vitest';

import '@/i18n/i18n';
import { clearAuthToken, writeAuthToken } from '@/lib/authToken';

import type { Agent, AgentTemplate } from '../types';
import { AgentFromTemplateFormPage } from './AgentFromTemplateFormPage';

function mockResponse(body: unknown, status = 200): Response {
  return new Response(body == null ? null : JSON.stringify(body), {
    status,
    headers: { 'content-type': 'application/json' },
  });
}

const majaTemplate: AgentTemplate = {
  id: '11111111-1111-4111-8111-111111111111',
  slug: 'maja',
  name: 'Maja',
  role: 'Project Manager',
  roleDescription: 'Keeps the roadmap on track and unblocks the team.',
  expertise: 'Coordination, planning, status reporting.',
  workflow: 'Daily check-ins, briefings, async updates.',
  style: 'Calm and direct.',
  outputFormat: 'TL;DR first, bullets after.',
  createdAt: '2026-11-01T10:00:00+00:00',
  updatedAt: '2026-11-01T10:00:00+00:00',
};

const createdAgent: Agent = {
  id: '99999999-9999-4999-8999-999999999999',
  organizationId: '22222222-2222-4222-8222-222222222222',
  ownerUserId: '33333333-3333-4333-8333-333333333333',
  sourceTemplateId: majaTemplate.id,
  slug: 'maja',
  name: 'Maja',
  role: 'Project Manager',
  roleDescription: majaTemplate.roleDescription,
  expertise: majaTemplate.expertise,
  workflow: majaTemplate.workflow,
  style: majaTemplate.style,
  outputFormat: majaTemplate.outputFormat,
  isActive: true,
  color: '#6366f1',
  createdAt: '2026-11-15T15:00:00+00:00',
  updatedAt: '2026-11-15T15:00:00+00:00',
};

interface MakeFetchOptions {
  template?: AgentTemplate | null;
  templateStatus?: number;
  createResponse?: (body: unknown) => Response;
}

function makeFetch({
  template = majaTemplate,
  templateStatus = 200,
  createResponse,
}: MakeFetchOptions = {}) {
  return vi.fn(async (input: RequestInfo | URL, init?: RequestInit) => {
    const url = typeof input === 'string' ? input : input.toString();
    const method = (init?.method ?? 'GET').toUpperCase();
    if (url.startsWith('/api/agent-templates/')) {
      return mockResponse(template, templateStatus);
    }
    if (url === '/api/agents/from-template' && method === 'POST') {
      const body = init?.body ? JSON.parse(init.body as string) : {};
      if (createResponse) return createResponse(body);
      return mockResponse(createdAgent, 201);
    }
    return mockResponse({}, 404);
  });
}

function LocationCapture({ store }: { store: { current: ReturnType<typeof useLocation> | null } }) {
  const loc = useLocation();
  store.current = loc;
  return null;
}

function renderPage(
  initialPath = '/agents/new/template/maja',
  locationStore?: { current: ReturnType<typeof useLocation> | null },
) {
  const queryClient = new QueryClient({
    defaultOptions: { queries: { retry: false }, mutations: { retry: false } },
  });
  function Wrapper({ children }: { children: ReactNode }) {
    return (
      <QueryClientProvider client={queryClient}>
        <MemoryRouter initialEntries={[initialPath]}>
          {locationStore ? <LocationCapture store={locationStore} /> : null}
          <Routes>
            <Route path="/agents" element={<div>agents list fallback</div>} />
            <Route path="/agents/new/template" element={<div>template picker fallback</div>} />
            <Route path="/agents/new/template/:slug" element={children} />
            <Route path="/agents/:id/overview" element={<div>agent overview fallback</div>} />
          </Routes>
        </MemoryRouter>
      </QueryClientProvider>
    );
  }
  return render(<AgentFromTemplateFormPage />, { wrapper: Wrapper });
}

describe('AgentFromTemplateFormPage', () => {
  beforeEach(() => {
    writeAuthToken('test-token');
  });

  afterEach(() => {
    clearAuthToken();
    vi.unstubAllGlobals();
    vi.restoreAllMocks();
  });

  it('should prefill the form with the loaded template values', async () => {
    vi.stubGlobal('fetch', makeFetch());

    renderPage();

    expect(
      await screen.findByRole('heading', { level: 1, name: /create agent from maja/i }),
    ).toBeInTheDocument();
    expect(screen.getByLabelText('Name')).toHaveValue('Maja');
    expect(screen.getByLabelText('Role')).toHaveValue('Project Manager');
    expect(screen.getByLabelText('Slug')).toHaveValue('maja');
    expect(screen.getByLabelText('Role description')).toHaveValue(majaTemplate.roleDescription);
    expect(screen.getByLabelText('Expertise')).toHaveValue(majaTemplate.expertise);
    expect(screen.getByLabelText('Workflow')).toHaveValue(majaTemplate.workflow);
    expect(screen.getByLabelText('Style')).toHaveValue(majaTemplate.style);
    expect(screen.getByLabelText('Output format')).toHaveValue(majaTemplate.outputFormat);
  });

  it('should redirect to /agents/new/template when the template returns 404', async () => {
    vi.stubGlobal(
      'fetch',
      makeFetch({
        template: { message: 'not found' } as unknown as AgentTemplate,
        templateStatus: 404,
      }),
    );

    renderPage('/agents/new/template/unknown');

    expect(await screen.findByText(/template picker fallback/i)).toBeInTheDocument();
  });

  it('should show a load error alert when the template request fails with a non-404 status', async () => {
    vi.stubGlobal(
      'fetch',
      makeFetch({
        template: { message: 'boom' } as unknown as AgentTemplate,
        templateStatus: 500,
      }),
    );

    renderPage();

    expect(await screen.findByRole('alert')).toHaveTextContent(/could not load the template/i);
  });

  it('should POST the full payload and navigate to the agent overview on success', async () => {
    const fetchMock = makeFetch();
    vi.stubGlobal('fetch', fetchMock);
    const locationStore: { current: ReturnType<typeof useLocation> | null } = { current: null };

    renderPage('/agents/new/template/maja', locationStore);

    const user = userEvent.setup();
    await screen.findByLabelText('Name');
    await user.click(screen.getByRole('button', { name: 'Create agent' }));

    expect(await screen.findByText(/agent overview fallback/i)).toBeInTheDocument();
    expect(locationStore.current?.pathname).toBe(`/agents/${createdAgent.id}/overview`);

    const postCall = fetchMock.mock.calls.find(
      ([, init]) => (init as RequestInit)?.method === 'POST',
    );
    expect(postCall).toBeDefined();
    const body = JSON.parse((postCall![1] as RequestInit).body as string);
    expect(body).toEqual({
      templateSlug: 'maja',
      name: 'Maja',
      role: 'Project Manager',
      slug: 'maja',
      roleDescription: majaTemplate.roleDescription,
      expertise: majaTemplate.expertise,
      workflow: majaTemplate.workflow,
      style: majaTemplate.style,
      outputFormat: majaTemplate.outputFormat,
    });
  });

  it('should render an inline alert when the server returns 409 slug conflict', async () => {
    vi.stubGlobal(
      'fetch',
      makeFetch({
        createResponse: () =>
          mockResponse({ code: 'AGENT_SLUG_ALREADY_TAKEN', message: 'slug taken' }, 409),
      }),
    );

    renderPage();

    const user = userEvent.setup();
    await screen.findByLabelText('Name');
    await user.click(screen.getByRole('button', { name: 'Create agent' }));

    expect(await screen.findByText(/this slug is already taken/i)).toBeInTheDocument();
  });

  it('should render an inline alert when the server returns 409 agent limit reached', async () => {
    vi.stubGlobal(
      'fetch',
      makeFetch({
        createResponse: () => mockResponse({ code: 'AGENT_LIMIT_REACHED', message: 'limit' }, 409),
      }),
    );

    renderPage();

    const user = userEvent.setup();
    await screen.findByLabelText('Name');
    await user.click(screen.getByRole('button', { name: 'Create agent' }));

    expect(await screen.findByText(/reached the agent limit/i)).toBeInTheDocument();
  });
});
