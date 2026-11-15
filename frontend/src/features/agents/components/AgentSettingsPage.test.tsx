import { QueryClient, QueryClientProvider } from '@tanstack/react-query';
import { render, screen, within } from '@testing-library/react';
import userEvent from '@testing-library/user-event';
import type { ReactNode } from 'react';
import { MemoryRouter, Route, Routes, useLocation } from 'react-router-dom';
import { afterEach, beforeEach, describe, expect, it, vi } from 'vitest';

import '@/i18n/i18n';
import { clearAuthToken, writeAuthToken } from '@/lib/authToken';

import type { Agent } from '../types';
import { AgentSettingsPage } from './AgentSettingsPage';

function mockResponse(body: unknown, status = 200): Response {
  return new Response(body == null ? null : JSON.stringify(body), {
    status,
    headers: { 'content-type': 'application/json' },
  });
}

const agent: Agent = {
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
  agentBody?: unknown;
  agentStatus?: number;
  updateResponse?: (body: unknown) => Response;
  deleteResponse?: () => Response;
}

function makeFetch({
  agentBody = agent,
  agentStatus = 200,
  updateResponse,
  deleteResponse,
}: MakeFetchOptions = {}) {
  return vi.fn(async (input: RequestInfo | URL, init?: RequestInit) => {
    const url = typeof input === 'string' ? input : input.toString();
    const method = (init?.method ?? 'GET').toUpperCase();
    if (url === `/api/agents/${agent.id}`) {
      if (method === 'PUT') {
        const body = init?.body ? JSON.parse(init.body as string) : {};
        if (updateResponse) return updateResponse(body);
        return mockResponse({ ...agent, ...body });
      }
      if (method === 'DELETE') {
        if (deleteResponse) return deleteResponse();
        return new Response(null, { status: 204 });
      }
      return mockResponse(agentBody, agentStatus);
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
        <MemoryRouter initialEntries={[`/agents/${agent.id}/settings`]}>
          {locationStore ? <LocationCapture store={locationStore} /> : null}
          <Routes>
            <Route path="/agents" element={<div>agents list fallback</div>} />
            <Route path="/agents/:id/settings" element={children} />
            <Route path="/agents/:id/overview" element={<div>agent overview fallback</div>} />
          </Routes>
        </MemoryRouter>
      </QueryClientProvider>
    );
  }
  return render(<AgentSettingsPage />, { wrapper: Wrapper });
}

describe('AgentSettingsPage', () => {
  beforeEach(() => {
    writeAuthToken('test-token');
  });

  afterEach(() => {
    clearAuthToken();
    vi.unstubAllGlobals();
    vi.restoreAllMocks();
  });

  it('should prefill the form with the loaded agent values', async () => {
    vi.stubGlobal('fetch', makeFetch());

    renderPage();

    const nameInput = await screen.findByLabelText('Name');
    expect(nameInput).toHaveValue('Custom One');
    expect(screen.getByLabelText('Role')).toHaveValue('Research Lead');
    expect(screen.getByLabelText('Slug')).toHaveValue('custom-one');
    expect(screen.getByLabelText('Role description')).toHaveValue(
      'Keeps all research threads connected.',
    );
    expect(screen.getByLabelText('Expertise')).toHaveValue('Discovery, interviews, synthesis.');
    expect(screen.getByLabelText('Workflow')).toHaveValue('Plan, interview, synthesize, report.');
    expect(screen.getByLabelText('Style')).toHaveValue('Curious and precise.');
    expect(screen.getByLabelText('Output format')).toHaveValue('TL;DR + highlights + raw notes.');
  });

  it('should render the slug input as readOnly', async () => {
    vi.stubGlobal('fetch', makeFetch());

    renderPage();

    const slugInput = await screen.findByLabelText('Slug');
    expect(slugInput).toHaveAttribute('readonly');
  });

  it('should PUT the seven editable fields and show a saved status banner', async () => {
    const fetchMock = makeFetch();
    vi.stubGlobal('fetch', fetchMock);

    renderPage();

    const user = userEvent.setup();
    const nameInput = await screen.findByLabelText('Name');
    await user.clear(nameInput);
    await user.type(nameInput, 'Renamed Agent');
    await user.click(screen.getByRole('button', { name: 'Save changes' }));

    expect(await screen.findByRole('status')).toHaveTextContent('Changes saved.');

    const putCall = fetchMock.mock.calls.find(
      ([, init]) => (init as RequestInit)?.method === 'PUT',
    );
    expect(putCall).toBeDefined();
    const body = JSON.parse((putCall![1] as RequestInit).body as string);
    expect(body).toEqual({
      name: 'Renamed Agent',
      role: 'Research Lead',
      roleDescription: 'Keeps all research threads connected.',
      expertise: 'Discovery, interviews, synthesis.',
      workflow: 'Plan, interview, synthesize, report.',
      style: 'Curious and precise.',
      outputFormat: 'TL;DR + highlights + raw notes.',
    });
    expect(body).not.toHaveProperty('slug');
  });

  it('should render an inline alert when the server returns 409 slug conflict on save', async () => {
    vi.stubGlobal(
      'fetch',
      makeFetch({
        updateResponse: () =>
          mockResponse({ code: 'AGENT_SLUG_ALREADY_TAKEN', message: 'slug taken' }, 409),
      }),
    );

    renderPage();

    const user = userEvent.setup();
    const nameInput = await screen.findByLabelText('Name');
    await user.clear(nameInput);
    await user.type(nameInput, 'Renamed Agent');
    await user.click(screen.getByRole('button', { name: 'Save changes' }));

    const alerts = await screen.findAllByRole('alert');
    expect(alerts.some((el) => el.textContent?.includes('already taken'))).toBe(true);
  });

  it('should open the delete confirm dialog when Danger Zone delete is clicked', async () => {
    vi.stubGlobal('fetch', makeFetch());

    renderPage();

    const user = userEvent.setup();
    await screen.findByLabelText('Name');
    await user.click(screen.getByRole('button', { name: 'Delete agent' }));

    expect(await screen.findByRole('dialog')).toBeInTheDocument();
    expect(screen.getByRole('heading', { name: 'Delete agent' })).toBeInTheDocument();
  });

  it('should keep confirm disabled until acknowledge checkbox is checked', async () => {
    vi.stubGlobal('fetch', makeFetch());

    renderPage();

    const user = userEvent.setup();
    await screen.findByLabelText('Name');
    await user.click(screen.getByRole('button', { name: 'Delete agent' }));

    const dialog = await screen.findByRole('dialog');
    const confirmButton = within(dialog).getByRole('button', { name: 'Delete agent' });
    expect(confirmButton).toBeDisabled();

    const checkbox = within(dialog).getByRole('checkbox');
    await user.click(checkbox);
    expect(confirmButton).toBeEnabled();
  });

  it('should DELETE the agent and redirect to agents list with deletedAgentName state when confirmed', async () => {
    const fetchMock = makeFetch();
    vi.stubGlobal('fetch', fetchMock);

    const locationStore: { current: ReturnType<typeof useLocation> | null } = { current: null };
    renderPage(locationStore);

    const user = userEvent.setup();
    await screen.findByLabelText('Name');
    await user.click(screen.getByRole('button', { name: 'Delete agent' }));

    const dialog = await screen.findByRole('dialog');
    await user.click(within(dialog).getByRole('checkbox'));
    await user.click(within(dialog).getByRole('button', { name: 'Delete agent' }));

    expect(await screen.findByText('agents list fallback')).toBeInTheDocument();
    expect(locationStore.current?.pathname).toBe('/agents');
    expect(
      (locationStore.current?.state as { deletedAgentName?: string } | null)?.deletedAgentName,
    ).toBe('Custom One');

    const deleteCall = fetchMock.mock.calls.find(
      ([, init]) => (init as RequestInit)?.method === 'DELETE',
    );
    expect(deleteCall).toBeDefined();
    expect(deleteCall![0]).toBe(`/api/agents/${agent.id}`);
  });

  it('should redirect to /agents when the agent returns 404', async () => {
    vi.stubGlobal('fetch', makeFetch({ agentBody: { error: 'not_found' }, agentStatus: 404 }));

    renderPage();

    expect(await screen.findByText('agents list fallback')).toBeInTheDocument();
  });

  it('should render a forbidden alert when the agent returns 403', async () => {
    vi.stubGlobal('fetch', makeFetch({ agentBody: { error: 'forbidden' }, agentStatus: 403 }));

    renderPage();

    const alert = await screen.findByRole('alert');
    expect(alert).toHaveTextContent(/cannot edit this agent/i);
  });
});
