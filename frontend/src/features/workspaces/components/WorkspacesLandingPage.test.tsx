import { QueryClient, QueryClientProvider } from '@tanstack/react-query';
import { render, screen } from '@testing-library/react';
import userEvent from '@testing-library/user-event';
import type { ReactNode } from 'react';
import { afterEach, beforeEach, describe, expect, it, vi } from 'vitest';

import { writeAuthToken, clearAuthToken } from '@/lib/authToken';

import { WorkspacesLandingPage } from './WorkspacesLandingPage';
import type { Workspace } from '../types';

interface MockResponseInit {
  status?: number;
  body?: unknown;
}

function mockResponse({ status = 200, body = null }: MockResponseInit = {}): Response {
  return new Response(body == null ? null : JSON.stringify(body), {
    status,
    headers: { 'content-type': 'application/json' },
  });
}

const sampleWorkspaces: Workspace[] = [
  {
    id: '11111111-1111-1111-1111-111111111111',
    name: 'Default',
    slug: 'default',
    iconColor: 'purple',
    iconInitials: 'DE',
    ownerId: '00000000-0000-0000-0000-000000000001',
  },
  {
    id: '22222222-2222-2222-2222-222222222222',
    name: 'TalentBridge',
    slug: 'talentbridge',
    iconColor: 'blue',
    iconInitials: 'TA',
    ownerId: '00000000-0000-0000-0000-000000000001',
  },
];

function renderLanding() {
  const queryClient = new QueryClient({
    defaultOptions: { queries: { retry: false }, mutations: { retry: false } },
  });

  function Wrapper({ children }: { children: ReactNode }) {
    return <QueryClientProvider client={queryClient}>{children}</QueryClientProvider>;
  }

  return render(<WorkspacesLandingPage />, { wrapper: Wrapper });
}

describe('WorkspacesLandingPage', () => {
  beforeEach(() => {
    writeAuthToken('test-token');
    vi.stubGlobal('fetch', vi.fn());
  });

  afterEach(() => {
    clearAuthToken();
    vi.unstubAllGlobals();
    vi.restoreAllMocks();
  });

  it('should render fetched workspaces as tiles plus the add tile', async () => {
    vi.mocked(fetch).mockResolvedValueOnce(mockResponse({ body: sampleWorkspaces }));

    renderLanding();

    expect(
      await screen.findByRole('heading', { name: /workspaces/i, level: 1 }),
    ).toBeInTheDocument();
    expect(await screen.findByRole('article', { name: /workspace default/i })).toBeInTheDocument();
    expect(screen.getByRole('article', { name: /workspace talentbridge/i })).toBeInTheDocument();
    expect(screen.getByRole('button', { name: /add workspace/i })).toBeInTheDocument();
  });

  it('should show an error message when the list request fails', async () => {
    vi.mocked(fetch).mockResolvedValueOnce(mockResponse({ status: 500, body: {} }));

    renderLanding();

    expect(await screen.findByText(/could not load workspaces/i)).toBeInTheDocument();
  });

  it('should open the create dialog and POST the form to the API', async () => {
    vi.mocked(fetch)
      .mockResolvedValueOnce(mockResponse({ body: [] }))
      .mockResolvedValueOnce(
        mockResponse({
          status: 201,
          body: {
            id: '33333333-3333-3333-3333-333333333333',
            name: 'Sport',
            slug: 'sport',
            iconColor: 'emerald',
            iconInitials: 'SP',
            ownerId: '00000000-0000-0000-0000-000000000001',
          },
        }),
      )
      .mockResolvedValueOnce(
        mockResponse({
          body: [
            {
              id: '33333333-3333-3333-3333-333333333333',
              name: 'Sport',
              slug: 'sport',
              iconColor: 'emerald',
              iconInitials: 'SP',
              ownerId: '00000000-0000-0000-0000-000000000001',
            },
          ],
        }),
      );

    renderLanding();
    const user = userEvent.setup();

    const addButton = await screen.findByRole('button', { name: /add workspace/i });
    await user.click(addButton);

    const nameInput = await screen.findByLabelText(/^name$/i);
    await user.type(nameInput, 'Sport');

    await user.click(screen.getByRole('button', { name: /^create$/i }));

    expect(await screen.findByRole('article', { name: /workspace sport/i })).toBeInTheDocument();

    const createCall = vi.mocked(fetch).mock.calls[1];
    expect(String(createCall[0])).toContain('/api/workspaces');
    const init = createCall[1] as RequestInit;
    expect(init.method).toBe('POST');
    expect(JSON.parse(String(init.body))).toMatchObject({
      name: 'Sport',
      iconInitials: 'SP',
      iconColor: 'purple',
    });
  });

  it('should DELETE the workspace after confirmation', async () => {
    vi.mocked(fetch)
      .mockResolvedValueOnce(mockResponse({ body: sampleWorkspaces }))
      .mockResolvedValueOnce(mockResponse({ status: 204 }))
      .mockResolvedValueOnce(mockResponse({ body: [sampleWorkspaces[1]] }));

    renderLanding();
    const user = userEvent.setup();

    await screen.findByRole('article', { name: /workspace default/i });
    await user.click(screen.getByRole('button', { name: /delete workspace default/i }));
    await user.click(await screen.findByRole('button', { name: /^delete$/i }));

    const deleteCall = vi.mocked(fetch).mock.calls[1];
    expect(String(deleteCall[0])).toContain(`/api/workspaces/${sampleWorkspaces[0].id}`);
    expect((deleteCall[1] as RequestInit).method).toBe('DELETE');
  });
});
