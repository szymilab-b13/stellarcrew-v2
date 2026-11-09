import { QueryClient, QueryClientProvider } from '@tanstack/react-query';
import { render, screen, within } from '@testing-library/react';
import userEvent from '@testing-library/user-event';
import type { ReactNode } from 'react';
import { MemoryRouter, Route, Routes, useLocation } from 'react-router-dom';
import { afterEach, beforeEach, describe, expect, it, vi } from 'vitest';

import '@/i18n/i18n';
import { clearAuthToken, writeAuthToken } from '@/lib/authToken';

import { ProjectSettingsPage } from './ProjectSettingsPage';

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
  updateResponse?: (body: unknown) => Response;
  deleteResponse?: () => Response;
}

function makeFetch({
  workspaces = [workspace],
  project: projectBody = project,
  projectStatus = 200,
  updateResponse,
  deleteResponse,
}: MakeFetchOptions = {}) {
  return vi.fn(async (input: RequestInfo | URL, init?: RequestInit) => {
    const url = typeof input === 'string' ? input : input.toString();
    const method = (init?.method ?? 'GET').toUpperCase();
    if (url === '/api/workspaces') {
      return mockResponse(workspaces);
    }
    if (url.startsWith('/api/projects/')) {
      if (method === 'PUT') {
        const body = init?.body ? JSON.parse(init.body as string) : {};
        if (updateResponse) return updateResponse(body);
        return mockResponse({ ...project, ...body });
      }
      if (method === 'DELETE') {
        if (deleteResponse) return deleteResponse();
        return new Response(null, { status: 204 });
      }
      return mockResponse(projectBody, projectStatus);
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
  initialPath = `/workspace/ws-1/project/${project.id}/settings`,
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
            <Route path="/workspace/:wsId/project/:projId/settings" element={children} />
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
  return render(<ProjectSettingsPage />, { wrapper: Wrapper });
}

describe('ProjectSettingsPage', () => {
  beforeEach(() => {
    writeAuthToken('test-token');
  });

  afterEach(() => {
    clearAuthToken();
    vi.unstubAllGlobals();
    vi.restoreAllMocks();
  });

  it('should prefill the form with the loaded project values', async () => {
    vi.stubGlobal('fetch', makeFetch());

    renderPage();

    const nameInput = await screen.findByLabelText('Name');
    expect(nameInput).toHaveValue('Talent Bridge');
    expect(screen.getByLabelText('Slug')).toHaveValue('talent-bridge');
    expect(screen.getByLabelText('Description')).toHaveValue('HR integration layer.');
  });

  it('should PUT the updated values and show a saved status banner', async () => {
    const fetchMock = makeFetch();
    vi.stubGlobal('fetch', fetchMock);

    renderPage();

    const user = userEvent.setup();
    const nameInput = await screen.findByLabelText('Name');
    await user.clear(nameInput);
    await user.type(nameInput, 'Renamed Project');
    await user.click(screen.getByRole('button', { name: 'Save changes' }));

    expect(await screen.findByRole('status')).toHaveTextContent('Changes saved.');

    const putCall = fetchMock.mock.calls.find(
      ([, init]) => (init as RequestInit)?.method === 'PUT',
    );
    expect(putCall).toBeDefined();
    const body = JSON.parse((putCall![1] as RequestInit).body as string);
    expect(body).toEqual({
      name: 'Renamed Project',
      slug: 'talent-bridge',
      description: 'HR integration layer.',
    });
  });

  it('should render an inline alert when the server returns 409 slug conflict', async () => {
    vi.stubGlobal(
      'fetch',
      makeFetch({
        updateResponse: () => mockResponse({ error: 'slug_conflict', message: 'slug exists' }, 409),
      }),
    );

    renderPage();

    const user = userEvent.setup();
    const slugInput = await screen.findByLabelText('Slug');
    await user.clear(slugInput);
    await user.type(slugInput, 'existing-slug');
    await user.click(screen.getByRole('button', { name: 'Save changes' }));

    const alerts = await screen.findAllByRole('alert');
    expect(
      alerts.some((el) => el.textContent?.includes('A project with this slug already exists')),
    ).toBe(true);
  });

  it('should show inline validation error when name is blank', async () => {
    vi.stubGlobal('fetch', makeFetch());

    renderPage();

    const user = userEvent.setup();
    const nameInput = await screen.findByLabelText('Name');
    await user.clear(nameInput);
    await user.click(screen.getByRole('button', { name: 'Save changes' }));

    const alerts = await screen.findAllByRole('alert');
    expect(alerts.some((el) => el.textContent?.includes('Name is required'))).toBe(true);
  });

  it('should open the delete confirm dialog when Danger Zone delete is clicked', async () => {
    vi.stubGlobal('fetch', makeFetch());

    renderPage();

    const user = userEvent.setup();
    await screen.findByLabelText('Name');
    await user.click(screen.getByRole('button', { name: 'Delete project' }));

    expect(await screen.findByRole('dialog')).toBeInTheDocument();
    expect(screen.getByRole('heading', { name: 'Delete project' })).toBeInTheDocument();
  });

  it('should keep confirm disabled until acknowledge checkbox is checked', async () => {
    vi.stubGlobal('fetch', makeFetch());

    renderPage();

    const user = userEvent.setup();
    await screen.findByLabelText('Name');
    await user.click(screen.getByRole('button', { name: 'Delete project' }));

    const dialog = await screen.findByRole('dialog');
    const confirmButton = within(dialog).getByRole('button', { name: 'Delete project' });
    expect(confirmButton).toBeDisabled();

    const checkbox = within(dialog).getByRole('checkbox');
    await user.click(checkbox);
    expect(confirmButton).toBeEnabled();
  });

  it('should DELETE the project and redirect to projects list with nav state when confirmed', async () => {
    const fetchMock = makeFetch();
    vi.stubGlobal('fetch', fetchMock);

    const locationStore: { current: ReturnType<typeof useLocation> | null } = { current: null };
    renderPage(undefined, locationStore);

    const user = userEvent.setup();
    await screen.findByLabelText('Name');
    await user.click(screen.getByRole('button', { name: 'Delete project' }));

    const dialog = await screen.findByRole('dialog');
    await user.click(within(dialog).getByRole('checkbox'));
    await user.click(within(dialog).getByRole('button', { name: 'Delete project' }));

    expect(await screen.findByText('workspace projects fallback')).toBeInTheDocument();
    expect(locationStore.current?.pathname).toBe('/workspace/ws-1/projects');
    expect(
      (locationStore.current?.state as { deletedProjectName?: string } | null)?.deletedProjectName,
    ).toBe('Talent Bridge');

    const deleteCall = fetchMock.mock.calls.find(
      ([, init]) => (init as RequestInit)?.method === 'DELETE',
    );
    expect(deleteCall).toBeDefined();
    expect(deleteCall![0]).toBe(`/api/projects/${project.id}`);
  });

  it('should redirect to the workspace projects page when the project is 404', async () => {
    vi.stubGlobal('fetch', makeFetch({ project: { error: 'not_found' }, projectStatus: 404 }));

    renderPage();

    expect(await screen.findByText('workspace projects fallback')).toBeInTheDocument();
  });

  it('should redirect to /workspaces when the workspace is not in the loaded list', async () => {
    vi.stubGlobal('fetch', makeFetch({ workspaces: [] }));

    renderPage();

    expect(await screen.findByText('workspaces fallback')).toBeInTheDocument();
  });
});
