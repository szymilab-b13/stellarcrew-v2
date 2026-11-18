import { QueryClient, QueryClientProvider } from '@tanstack/react-query';
import { render, screen } from '@testing-library/react';
import userEvent from '@testing-library/user-event';
import type { ReactNode } from 'react';
import { MemoryRouter } from 'react-router-dom';
import { afterEach, beforeEach, describe, expect, it, vi } from 'vitest';

import '@/i18n/i18n';
import { clearAuthToken, writeAuthToken } from '@/lib/authToken';

import { AgentsSidebar } from './AgentsSidebar';

const agentAlphaId = 'aaaaaaaa-aaaa-4aaa-8aaa-aaaaaaaaaaaa';
const agentBetaId = 'bbbbbbbb-bbbb-4bbb-8bbb-bbbbbbbbbbbb';
const agentGammaId = 'cccccccc-cccc-4ccc-8ccc-cccccccccccc';

const conversationAlpha = {
  agentId: agentAlphaId,
  agentName: 'Hefajstos Builder',
  agentSlug: 'hefajstos',
  agentRole: 'Autonomous Builder',
  agentColor: '#06b6d4',
  lastMessagePreview: 'Pushed BL-CAGU-007 frontend hooks to feat/chat-foundation',
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

const conversationGammaNoThreads = {
  agentId: agentGammaId,
  agentName: 'Maja PM',
  agentSlug: 'maja',
  agentRole: 'Project Manager',
  agentColor: '#8b5cf6',
  lastMessagePreview: null,
  lastMessageAt: null,
  threadCount: 0,
  isNew: true,
};

function mockResponse(body: unknown, status = 200): Response {
  return new Response(body == null ? null : JSON.stringify(body), {
    status,
    headers: { 'content-type': 'application/json' },
  });
}

function installFetch(handler: () => Response | Promise<Response>) {
  const fetchMock = vi.fn(async (input: RequestInfo | URL) => {
    const url = typeof input === 'string' ? input : input.toString();
    if (url === '/api/chat/conversations') {
      return handler();
    }
    return mockResponse({}, 404);
  });
  vi.stubGlobal('fetch', fetchMock);
  return fetchMock;
}

function renderSidebar(selectedAgentId: string | null = null) {
  const onSelect = vi.fn();
  const onAddConversation = vi.fn();
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
    <AgentsSidebar
      selectedAgentId={selectedAgentId}
      onSelect={onSelect}
      onAddConversation={onAddConversation}
    />,
    { wrapper: Wrapper },
  );
  return { ...utils, onSelect, onAddConversation };
}

beforeEach(() => {
  writeAuthToken('test-token');
});

afterEach(() => {
  clearAuthToken();
  vi.unstubAllGlobals();
  vi.restoreAllMocks();
});

describe('AgentsSidebar', () => {
  it('shows a loading state while conversations are fetched', () => {
    installFetch(() => new Promise<Response>(() => undefined));

    renderSidebar();

    expect(screen.getByText('Loading agents…')).toBeInTheDocument();
  });

  it('shows an error alert when the request fails', async () => {
    installFetch(() => mockResponse({ error: 'boom' }, 500));

    renderSidebar();

    expect(await screen.findByRole('alert')).toHaveTextContent('Could not load agents.');
  });

  it('renders an empty state with a create-agent link when there are no agents', async () => {
    installFetch(() => mockResponse({ conversations: [] }));

    renderSidebar();

    expect(await screen.findByText('No agents yet')).toBeInTheDocument();
    const cta = screen.getByRole('link', { name: 'Create agent' });
    expect(cta).toHaveAttribute('href', '/agents/new');
  });

  it('renders one row per direct message with name, role and preview', async () => {
    installFetch(() => mockResponse({ conversations: [conversationAlpha, conversationBeta] }));

    renderSidebar();

    expect(await screen.findByText('Hefajstos Builder')).toBeInTheDocument();
    expect(screen.getByText('Autonomous Builder')).toBeInTheDocument();
    expect(
      screen.getByText(/Pushed BL-CAGU-007 frontend hooks to feat\/chat-foundation/),
    ).toBeInTheDocument();
    expect(screen.getByText('Luna Designer')).toBeInTheDocument();
    expect(screen.getByText('UI/UX Specialist')).toBeInTheDocument();
  });

  it('hides agents that have no threads from the direct message list', async () => {
    installFetch(() =>
      mockResponse({ conversations: [conversationAlpha, conversationGammaNoThreads] }),
    );

    renderSidebar();

    expect(await screen.findByText('Hefajstos Builder')).toBeInTheDocument();
    expect(screen.queryByText('Maja PM')).not.toBeInTheDocument();
    expect(screen.queryByText('No conversations yet')).not.toBeInTheDocument();
  });

  it('shows a direct message empty state when every agent has zero threads', async () => {
    installFetch(() => mockResponse({ conversations: [conversationGammaNoThreads] }));

    renderSidebar();

    expect(await screen.findByText('No conversations yet')).toBeInTheDocument();
    expect(screen.getByText('Click + to start a conversation with an agent.')).toBeInTheDocument();
    expect(screen.queryByText('Maja PM')).not.toBeInTheDocument();
  });

  it('marks the selected agent row with aria-pressed=true', async () => {
    installFetch(() => mockResponse({ conversations: [conversationAlpha, conversationBeta] }));

    renderSidebar(agentAlphaId);

    const hefajstosRow = await screen.findByRole('button', { name: /Hefajstos Builder/ });
    const lunaRow = screen.getByRole('button', { name: /Luna Designer/ });
    expect(hefajstosRow).toHaveAttribute('aria-pressed', 'true');
    expect(lunaRow).toHaveAttribute('aria-pressed', 'false');
  });

  it('calls onSelect with the agent id when a row is clicked', async () => {
    installFetch(() => mockResponse({ conversations: [conversationAlpha, conversationBeta] }));

    const { onSelect } = renderSidebar();

    const lunaRow = await screen.findByRole('button', { name: /Luna Designer/ });
    await userEvent.click(lunaRow);

    expect(onSelect).toHaveBeenCalledWith(agentBetaId);
  });

  it('renders the add-conversation button and calls onAddConversation when clicked', async () => {
    installFetch(() => mockResponse({ conversations: [conversationAlpha] }));

    const { onAddConversation } = renderSidebar();

    const addButton = await screen.findByRole('button', { name: 'Add conversation' });
    await userEvent.click(addButton);

    expect(onAddConversation).toHaveBeenCalledTimes(1);
  });

  it('uses the per-agent color on the avatar background', async () => {
    installFetch(() => mockResponse({ conversations: [conversationAlpha] }));

    renderSidebar();

    const row = await screen.findByRole('button', { name: /Hefajstos Builder/ });
    const avatar = row.querySelector<HTMLElement>('[style*="background"]');
    expect(avatar?.style.backgroundColor).toBe('rgba(6, 182, 212, 0.2)');
    expect(avatar?.style.color).toBe('rgb(6, 182, 212)');
  });
});
