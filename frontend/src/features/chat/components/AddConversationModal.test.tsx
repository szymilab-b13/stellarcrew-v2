import { QueryClient, QueryClientProvider } from '@tanstack/react-query';
import { render, screen, waitFor } from '@testing-library/react';
import userEvent from '@testing-library/user-event';
import type { ReactNode } from 'react';
import { MemoryRouter } from 'react-router-dom';
import { afterEach, beforeEach, describe, expect, it, vi } from 'vitest';

import '@/i18n/i18n';
import { clearAuthToken, writeAuthToken } from '@/lib/authToken';

import { AddConversationModal } from './AddConversationModal';

const sessionId = '11111111-1111-4111-8111-111111111111';

const hefajstos = {
  id: 'aaaaaaaa-aaaa-4aaa-8aaa-aaaaaaaaaaaa',
  organizationId: 'org-id',
  ownerUserId: 'user-id',
  sourceTemplateId: null,
  slug: 'hefajstos',
  name: 'Hefajstos',
  role: 'Autonomous Builder',
  roleDescription: 'desc',
  expertise: 'expertise',
  workflow: 'workflow',
  style: 'style',
  outputFormat: 'format',
  isActive: true,
  color: '#06b6d4',
  createdAt: '2026-11-18T08:00:00+00:00',
  updatedAt: '2026-11-18T08:00:00+00:00',
};

const maja = {
  ...hefajstos,
  id: 'bbbbbbbb-bbbb-4bbb-8bbb-bbbbbbbbbbbb',
  slug: 'maja',
  name: 'Maja',
  role: 'Project Manager',
  color: '#8b5cf6',
};

function mockResponse(body: unknown, status = 200): Response {
  return new Response(body == null ? null : JSON.stringify(body), {
    status,
    headers: { 'content-type': 'application/json' },
  });
}

interface FetchScript {
  agents?: () => Response | Promise<Response>;
  createThread?: (body: string | null) => Response | Promise<Response>;
}

function installFetch(script: FetchScript) {
  const fetchMock = vi.fn(async (input: RequestInfo | URL, init?: RequestInit) => {
    const url = typeof input === 'string' ? input : input.toString();
    if (url === '/api/agents') {
      return script.agents?.() ?? mockResponse([hefajstos, maja]);
    }
    if (url === '/api/chat/threads') {
      const body = typeof init?.body === 'string' ? init.body : null;
      return (
        script.createThread?.(body) ??
        mockResponse(
          {
            id: 'tttttttt-tttt-4ttt-8ttt-tttttttttttt',
            sessionId,
            subject: 'Chat with Hefajstos',
            parentThreadId: null,
            createdAt: '2026-11-18T12:00:00+00:00',
            updatedAt: '2026-11-18T12:00:00+00:00',
          },
          201,
        )
      );
    }
    return mockResponse({}, 404);
  });
  vi.stubGlobal('fetch', fetchMock);
  return fetchMock;
}

function renderModal(props?: Partial<Parameters<typeof AddConversationModal>[0]>) {
  const onClose = vi.fn();
  const onCreated = vi.fn();
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
  const utils = render(
    <AddConversationModal
      sessionId={sessionId}
      onClose={onClose}
      onCreated={onCreated}
      {...props}
    />,
    { wrapper: Wrapper },
  );
  return { ...utils, onClose, onCreated };
}

beforeEach(() => {
  writeAuthToken('test-token');
});

afterEach(() => {
  clearAuthToken();
  vi.unstubAllGlobals();
  vi.restoreAllMocks();
});

describe('AddConversationModal', () => {
  it('renders the search input and the list of agents', async () => {
    installFetch({});

    renderModal();

    expect(await screen.findByText('Hefajstos')).toBeInTheDocument();
    expect(screen.getByText('Maja')).toBeInTheDocument();
    expect(screen.getByPlaceholderText('Search agents by name or role…')).toBeInTheDocument();
  });

  it('filters agents by the search term', async () => {
    installFetch({});

    renderModal();

    await screen.findByText('Hefajstos');
    const search = screen.getByPlaceholderText('Search agents by name or role…');
    await userEvent.type(search, 'maja');

    expect(screen.queryByText('Hefajstos')).not.toBeInTheDocument();
    expect(screen.getByText('Maja')).toBeInTheDocument();
  });

  it('shows a filtered empty state when no agent matches the search', async () => {
    installFetch({});

    renderModal();

    await screen.findByText('Hefajstos');
    const search = screen.getByPlaceholderText('Search agents by name or role…');
    await userEvent.type(search, 'zzzz');

    expect(screen.getByText('No agents match your search.')).toBeInTheDocument();
  });

  it('shows the agents empty state when the account has no agents', async () => {
    installFetch({ agents: () => mockResponse([]) });

    renderModal();

    expect(await screen.findByText("You don't have any agents yet.")).toBeInTheDocument();
  });

  it('shows a load error when the agents request fails', async () => {
    installFetch({ agents: () => mockResponse({ error: 'boom' }, 500) });

    renderModal();

    expect(await screen.findByRole('alert')).toHaveTextContent('Could not load agents.');
  });

  it('creates a thread with a default subject and calls onCreated with the agent id', async () => {
    const seenBodies: string[] = [];
    installFetch({
      createThread: (body) => {
        if (body !== null) {
          seenBodies.push(body);
        }
        return mockResponse(
          {
            id: 'tttttttt-tttt-4ttt-8ttt-tttttttttttt',
            sessionId,
            subject: 'Chat with Hefajstos',
            parentThreadId: null,
            createdAt: '2026-11-18T12:00:00+00:00',
            updatedAt: '2026-11-18T12:00:00+00:00',
          },
          201,
        );
      },
    });

    const { onCreated } = renderModal();

    const row = await screen.findByRole('button', { name: /Hefajstos/ });
    await userEvent.click(row);

    await waitFor(() => expect(onCreated).toHaveBeenCalledWith(hefajstos.id));
    expect(seenBodies).toHaveLength(1);
    const payload = JSON.parse(seenBodies[0]);
    expect(payload.sessionId).toBe(sessionId);
    expect(payload.subject).toBe('Chat with Hefajstos');
  });

  it('shows a create error and does not call onCreated when the request fails', async () => {
    installFetch({
      createThread: () => mockResponse({ error: 'boom' }, 500),
    });

    const { onCreated } = renderModal();

    const row = await screen.findByRole('button', { name: /Hefajstos/ });
    await userEvent.click(row);

    expect(
      await screen.findByText('Could not start the conversation. Try again.'),
    ).toBeInTheDocument();
    expect(onCreated).not.toHaveBeenCalled();
  });

  it('calls onClose when the close button is clicked', async () => {
    installFetch({});

    const { onClose } = renderModal();

    const closeButton = await screen.findByRole('button', { name: 'Close' });
    await userEvent.click(closeButton);

    expect(onClose).toHaveBeenCalledTimes(1);
  });

  it('calls onClose when Escape key is pressed', async () => {
    installFetch({});

    const { onClose } = renderModal();

    await screen.findByText('Hefajstos');
    await userEvent.keyboard('{Escape}');

    expect(onClose).toHaveBeenCalledTimes(1);
  });
});
