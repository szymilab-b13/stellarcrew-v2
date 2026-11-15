import { QueryClient, QueryClientProvider } from '@tanstack/react-query';
import { render, screen } from '@testing-library/react';
import userEvent from '@testing-library/user-event';
import type { ReactNode } from 'react';
import { MemoryRouter, Route, Routes, useLocation } from 'react-router-dom';
import { afterEach, beforeEach, describe, expect, it, vi } from 'vitest';

import '@/i18n/i18n';
import { clearAuthToken, writeAuthToken } from '@/lib/authToken';

import type { Agent } from '../types';
import { AgentFromScratchFormPage } from './AgentFromScratchFormPage';

function mockResponse(body: unknown, status = 200): Response {
  return new Response(body == null ? null : JSON.stringify(body), {
    status,
    headers: { 'content-type': 'application/json' },
  });
}

const createdAgent: Agent = {
  id: '77777777-7777-4777-8777-777777777777',
  organizationId: '22222222-2222-4222-8222-222222222222',
  ownerUserId: '33333333-3333-4333-8333-333333333333',
  sourceTemplateId: null,
  slug: 'custom-one',
  name: 'Custom One',
  role: 'Research Lead',
  roleDescription: 'Keeps all research threads connected.',
  expertise: 'Discovery, interviews, synthesis.',
  workflow: 'Plan, interview, synthesize, report.',
  style: 'Curious and precise.',
  outputFormat: 'TL;DR + highlights + raw notes.',
  isActive: true,
  createdAt: '2026-11-15T15:30:00+00:00',
  updatedAt: '2026-11-15T15:30:00+00:00',
};

interface MakeFetchOptions {
  createResponse?: (body: unknown) => Response;
}

function makeFetch({ createResponse }: MakeFetchOptions = {}) {
  return vi.fn(async (input: RequestInfo | URL, init?: RequestInit) => {
    const url = typeof input === 'string' ? input : input.toString();
    const method = (init?.method ?? 'GET').toUpperCase();
    if (url === '/api/agents' && method === 'POST') {
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

function renderPage(locationStore?: { current: ReturnType<typeof useLocation> | null }) {
  const queryClient = new QueryClient({
    defaultOptions: { queries: { retry: false }, mutations: { retry: false } },
  });
  function Wrapper({ children }: { children: ReactNode }) {
    return (
      <QueryClientProvider client={queryClient}>
        <MemoryRouter initialEntries={['/agents/new/scratch']}>
          {locationStore ? <LocationCapture store={locationStore} /> : null}
          <Routes>
            <Route path="/agents" element={<div>agents list fallback</div>} />
            <Route path="/agents/new" element={<div>choose path fallback</div>} />
            <Route path="/agents/new/scratch" element={children} />
            <Route path="/agents/:id/overview" element={<div>agent overview fallback</div>} />
          </Routes>
        </MemoryRouter>
      </QueryClientProvider>
    );
  }
  return render(<AgentFromScratchFormPage />, { wrapper: Wrapper });
}

async function fillAllFields(user: ReturnType<typeof userEvent.setup>) {
  await user.type(screen.getByLabelText('Name'), 'Custom One');
  await user.type(screen.getByLabelText('Role'), 'Research Lead');
  await user.type(screen.getByLabelText('Slug'), 'custom-one');
  await user.type(
    screen.getByLabelText('Role description'),
    'Keeps all research threads connected.',
  );
  await user.type(screen.getByLabelText('Expertise'), 'Discovery, interviews, synthesis.');
  await user.type(screen.getByLabelText('Workflow'), 'Plan, interview, synthesize, report.');
  await user.type(screen.getByLabelText('Style'), 'Curious and precise.');
  await user.type(screen.getByLabelText('Output format'), 'TL;DR + highlights + raw notes.');
}

describe('AgentFromScratchFormPage', () => {
  beforeEach(() => {
    writeAuthToken('test-token');
  });

  afterEach(() => {
    clearAuthToken();
    vi.unstubAllGlobals();
    vi.restoreAllMocks();
  });

  it('should render the heading and an empty form with all eight fields blank', () => {
    vi.stubGlobal('fetch', makeFetch());

    renderPage();

    expect(
      screen.getByRole('heading', { level: 1, name: /create agent from scratch/i }),
    ).toBeInTheDocument();
    expect(screen.getByLabelText('Name')).toHaveValue('');
    expect(screen.getByLabelText('Role')).toHaveValue('');
    expect(screen.getByLabelText('Slug')).toHaveValue('');
    expect(screen.getByLabelText('Role description')).toHaveValue('');
    expect(screen.getByLabelText('Expertise')).toHaveValue('');
    expect(screen.getByLabelText('Workflow')).toHaveValue('');
    expect(screen.getByLabelText('Style')).toHaveValue('');
    expect(screen.getByLabelText('Output format')).toHaveValue('');
  });

  it('should POST the eight fields payload and navigate to the agent overview on success', async () => {
    const fetchMock = makeFetch();
    vi.stubGlobal('fetch', fetchMock);
    const locationStore: { current: ReturnType<typeof useLocation> | null } = { current: null };

    renderPage(locationStore);

    const user = userEvent.setup();
    await fillAllFields(user);
    await user.click(screen.getByRole('button', { name: 'Create agent' }));

    expect(await screen.findByText(/agent overview fallback/i)).toBeInTheDocument();
    expect(locationStore.current?.pathname).toBe(`/agents/${createdAgent.id}/overview`);

    const postCall = fetchMock.mock.calls.find(
      ([, init]) => (init as RequestInit)?.method === 'POST',
    );
    expect(postCall).toBeDefined();
    const body = JSON.parse((postCall![1] as RequestInit).body as string);
    expect(body).toEqual({
      name: 'Custom One',
      role: 'Research Lead',
      slug: 'custom-one',
      roleDescription: 'Keeps all research threads connected.',
      expertise: 'Discovery, interviews, synthesis.',
      workflow: 'Plan, interview, synthesize, report.',
      style: 'Curious and precise.',
      outputFormat: 'TL;DR + highlights + raw notes.',
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
    await fillAllFields(user);
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
    await fillAllFields(user);
    await user.click(screen.getByRole('button', { name: 'Create agent' }));

    expect(await screen.findByText(/reached the agent limit/i)).toBeInTheDocument();
  });

  it('should render the back link to the choose-path dialog', () => {
    vi.stubGlobal('fetch', makeFetch());

    renderPage();

    const back = screen.getByRole('link', { name: /back to agent types/i });
    expect(back).toHaveAttribute('href', '/agents/new');
  });
});
