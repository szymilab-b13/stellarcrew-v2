import { QueryClient, QueryClientProvider } from '@tanstack/react-query';
import { render, screen } from '@testing-library/react';
import userEvent from '@testing-library/user-event';
import type { ReactNode } from 'react';
import { MemoryRouter } from 'react-router-dom';
import { afterEach, beforeEach, describe, expect, it, vi } from 'vitest';

import '@/i18n/i18n';
import { clearAuthToken, writeAuthToken } from '@/lib/authToken';

import { ChatPage } from './ChatPage';

const sessionId = '11111111-1111-4111-8111-111111111111';
const userId = '44444444-4444-4444-8444-444444444444';
const orgId = '55555555-5555-4555-8555-555555555555';
const agentAlphaId = 'aaaaaaaa-aaaa-4aaa-8aaa-aaaaaaaaaaaa';
const agentBetaId = 'bbbbbbbb-bbbb-4bbb-8bbb-bbbbbbbbbbbb';

const sampleSession = {
  id: sessionId,
  organizationId: orgId,
  ownerUserId: userId,
  label: 'Chat',
  startedAt: '2026-11-16T10:00:00+00:00',
  endedAt: null,
  createdAt: '2026-11-16T10:00:00+00:00',
  updatedAt: '2026-11-16T10:00:00+00:00',
};

const conversationAlpha = {
  agentId: agentAlphaId,
  agentName: 'Hefajstos Builder',
  agentSlug: 'hefajstos',
  agentRole: 'Autonomous Builder',
  agentColor: '#06b6d4',
  lastMessagePreview: 'Pushed BL-CAGU-007',
  lastMessageAt: '2026-11-18T07:30:00+00:00',
  threadCount: 3,
  isNew: false,
};

const conversationBeta = {
  agentId: agentBetaId,
  agentName: 'Luna Designer',
  agentSlug: 'luna',
  agentRole: 'UI/UX Specialist',
  agentColor: '#d946ef',
  lastMessagePreview: 'Finished chat mockups.',
  lastMessageAt: '2026-11-18T08:00:00+00:00',
  threadCount: 1,
  isNew: false,
};

function mockResponse(body: unknown, status = 200): Response {
  return new Response(body == null ? null : JSON.stringify(body), {
    status,
    headers: { 'content-type': 'application/json' },
  });
}

interface FetchScript {
  session?: () => Response | Promise<Response>;
  conversations?: () => Response | Promise<Response>;
  agentThreads?: () => Response | Promise<Response>;
  threadMessages?: () => Response | Promise<Response>;
}

function installFetch(script: FetchScript) {
  const fetchMock = vi.fn(async (input: RequestInfo | URL) => {
    const url = typeof input === 'string' ? input : input.toString();
    if (url === '/api/chat/sessions/current') {
      return script.session?.() ?? mockResponse(sampleSession);
    }
    if (url === '/api/chat/conversations') {
      return script.conversations?.() ?? mockResponse({ conversations: [] });
    }
    if (url.startsWith('/api/chat/agents/') && url.endsWith('/threads')) {
      return script.agentThreads?.() ?? mockResponse({ threads: [] });
    }
    if (url.startsWith('/api/chat/threads/') && url.includes('/messages')) {
      return (
        script.threadMessages?.() ?? mockResponse({ messages: [], total: 0, limit: 50, offset: 0 })
      );
    }
    return mockResponse({}, 404);
  });
  vi.stubGlobal('fetch', fetchMock);
  return fetchMock;
}

function renderPage() {
  const queryClient = new QueryClient({
    defaultOptions: { queries: { retry: false }, mutations: { retry: false } },
  });
  function Wrapper({ children }: { children: ReactNode }) {
    return (
      <QueryClientProvider client={queryClient}>
        <MemoryRouter initialEntries={['/chat']}>{children}</MemoryRouter>
      </QueryClientProvider>
    );
  }
  return render(<ChatPage />, { wrapper: Wrapper });
}

beforeEach(() => {
  writeAuthToken('test-token');
});

afterEach(() => {
  clearAuthToken();
  vi.unstubAllGlobals();
  vi.restoreAllMocks();
});

describe('ChatPage', () => {
  it('shows a session-loading message before the session resolves', () => {
    installFetch({ session: () => new Promise<Response>(() => undefined) });

    renderPage();

    expect(screen.getByText('Loading your chat session…')).toBeInTheDocument();
  });

  it('shows a generic session error on non-409 failure', async () => {
    installFetch({ session: () => mockResponse({ error: 'boom' }, 500) });

    renderPage();

    expect(await screen.findByRole('alert')).toHaveTextContent('Could not load your chat session.');
  });

  it('shows a missing-organization message on 409', async () => {
    installFetch({ session: () => mockResponse({ error: 'no-org' }, 409) });

    renderPage();

    expect(await screen.findByRole('alert')).toHaveTextContent(
      'Create an organization to start chatting.',
    );
  });

  it('renders the agents sidebar empty state and the select-agent placeholder', async () => {
    installFetch({ conversations: () => mockResponse({ conversations: [] }) });

    renderPage();

    expect(await screen.findByText('No agents yet')).toBeInTheDocument();
    expect(screen.getByText('Select an agent')).toBeInTheDocument();
  });

  it('renders the agents list and the placeholder main panel until an agent is selected', async () => {
    installFetch({
      conversations: () => mockResponse({ conversations: [conversationAlpha, conversationBeta] }),
    });

    renderPage();

    expect(await screen.findByText('Hefajstos Builder')).toBeInTheDocument();
    expect(screen.getByText('Luna Designer')).toBeInTheDocument();
    expect(screen.getByText('Select an agent')).toBeInTheDocument();
  });

  it('renders the conversation feed when an agent is selected', async () => {
    installFetch({
      conversations: () => mockResponse({ conversations: [conversationAlpha, conversationBeta] }),
      agentThreads: () => mockResponse({ threads: [] }),
    });

    renderPage();

    const row = await screen.findByRole('button', { name: /Hefajstos Builder/ });
    await userEvent.click(row);

    expect(screen.getAllByText('Hefajstos Builder').length).toBeGreaterThan(0);
    expect(screen.getAllByText('Autonomous Builder').length).toBeGreaterThan(0);
    expect(row).toHaveAttribute('aria-pressed', 'true');
  });

  it('opens the add-conversation modal when the plus button is clicked', async () => {
    installFetch({
      conversations: () => mockResponse({ conversations: [conversationAlpha, conversationBeta] }),
    });

    renderPage();

    const addButton = await screen.findByRole('button', { name: 'Add conversation' });
    await userEvent.click(addButton);

    expect(await screen.findByRole('dialog', { name: 'Start a conversation' })).toBeInTheDocument();
  });
});
