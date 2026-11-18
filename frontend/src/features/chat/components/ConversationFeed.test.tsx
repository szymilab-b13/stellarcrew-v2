import { QueryClient, QueryClientProvider } from '@tanstack/react-query';
import { render, screen, waitFor } from '@testing-library/react';
import userEvent from '@testing-library/user-event';
import type { ReactNode } from 'react';
import { afterEach, beforeEach, describe, expect, it, vi } from 'vitest';

import '@/i18n/i18n';
import { clearAuthToken, writeAuthToken } from '@/lib/authToken';

import { ConversationFeed } from './ConversationFeed';

const agentId = 'aaaaaaaa-aaaa-4aaa-8aaa-aaaaaaaaaaaa';
const threadDeployId = 'd0000001-0000-7000-8000-000000000001';
const threadStandupId = 'd0000002-0000-7000-8000-000000000002';

const conversation = {
  agentId,
  agentName: 'Hefajstos',
  agentSlug: 'hefajstos',
  agentRole: 'Autonomous Builder',
  agentColor: '#06b6d4',
  lastMessagePreview: 'Pushed BL-CAGU-009',
  lastMessageAt: '2026-11-18T07:30:00+00:00',
  threadCount: 2,
  isNew: false,
};

const threadDeploy = {
  threadId: threadDeployId,
  subject: 'Deploy CHAT-FOUNDATION',
  shortDescription: null,
  messageCount: 2,
  lastMessageAt: '2026-11-18T11:10:00+00:00',
  parentThreadId: null,
};

const threadStandup = {
  threadId: threadStandupId,
  subject: 'Daily standup',
  shortDescription: null,
  messageCount: 2,
  lastMessageAt: '2026-11-18T11:02:00+00:00',
  parentThreadId: null,
};

const messageUserDeploy = {
  id: 'm0000001-0000-7000-8000-000000000001',
  sessionId: 'ssssssss-ssss-4sss-8sss-ssssssssssss',
  threadId: threadDeployId,
  senderType: 'user',
  senderId: 'user-id',
  recipientType: 'agent',
  recipientId: agentId,
  subject: 'Deploy CHAT-FOUNDATION',
  priority: 'P2',
  content: 'Odpal deploy.',
  contentType: 'text',
  metadata: {},
  replyToMessageId: null,
  engineId: agentId,
  engineType: 'cli_terminal',
  classifiedBy: 'sender',
  classificationConfidence: null,
  createdAt: '2026-11-18T10:40:00+00:00',
};

const messageAgentDeploy = {
  ...messageUserDeploy,
  id: 'm0000002-0000-7000-8000-000000000002',
  senderType: 'agent',
  senderId: agentId,
  recipientType: 'user',
  recipientId: 'user-id',
  content: 'Deploy odpalony.',
  createdAt: '2026-11-18T11:10:00+00:00',
};

const messageUserStandup = {
  ...messageUserDeploy,
  id: 'm0000003-0000-7000-8000-000000000003',
  threadId: threadStandupId,
  subject: 'Daily standup',
  content: 'Co dziś robimy?',
  createdAt: '2026-11-18T09:00:00+00:00',
};

function mockResponse(body: unknown, status = 200): Response {
  return new Response(body == null ? null : JSON.stringify(body), {
    status,
    headers: { 'content-type': 'application/json' },
  });
}

function installFetch() {
  const fetchMock = vi.fn(async (input: RequestInfo | URL) => {
    const url = typeof input === 'string' ? input : input.toString();
    if (url === `/api/chat/agents/${agentId}/threads`) {
      return mockResponse({ threads: [threadDeploy, threadStandup] });
    }
    if (url.startsWith(`/api/chat/threads/${threadDeployId}/messages`)) {
      return mockResponse({
        messages: [messageUserDeploy, messageAgentDeploy],
        total: 2,
        limit: 50,
        offset: 0,
      });
    }
    if (url.startsWith(`/api/chat/threads/${threadStandupId}/messages`)) {
      return mockResponse({
        messages: [messageUserStandup],
        total: 1,
        limit: 50,
        offset: 0,
      });
    }
    return mockResponse({}, 404);
  });
  vi.stubGlobal('fetch', fetchMock);
}

function renderFeed() {
  const queryClient = new QueryClient({
    defaultOptions: { queries: { retry: false }, mutations: { retry: false } },
  });
  function Wrapper({ children }: { children: ReactNode }) {
    return <QueryClientProvider client={queryClient}>{children}</QueryClientProvider>;
  }
  return render(
    <ConversationFeed
      conversation={conversation}
      sessionId="ssssssss-ssss-4sss-8sss-ssssssssssss"
      senderId="user-id"
    />,
    { wrapper: Wrapper },
  );
}

beforeEach(() => {
  writeAuthToken('test-token');
  window.localStorage.clear();
});

afterEach(() => {
  clearAuthToken();
  vi.unstubAllGlobals();
  vi.restoreAllMocks();
  window.localStorage.clear();
});

describe('ConversationFeed', () => {
  it('renders the chat header and all messages from every thread merged chronologically', async () => {
    installFetch();

    renderFeed();

    expect(await screen.findByText('Hefajstos')).toBeInTheDocument();
    expect(await screen.findByText('Odpal deploy.')).toBeInTheDocument();
    expect(await screen.findByText('Co dziś robimy?')).toBeInTheDocument();
    expect(await screen.findByText('Deploy odpalony.')).toBeInTheDocument();

    const bubbles = screen.getAllByRole('article');
    expect(bubbles[0]).toHaveTextContent('Co dziś robimy?');
    expect(bubbles[1]).toHaveTextContent('Odpal deploy.');
    expect(bubbles[2]).toHaveTextContent('Deploy odpalony.');
  });

  it('does not render thread tabs or chapter dividers', async () => {
    installFetch();

    renderFeed();

    await screen.findByText('Odpal deploy.');
    expect(screen.queryByRole('tablist')).toBeNull();
    expect(screen.queryByText('Deploy CHAT-FOUNDATION')).toBeNull();
    expect(screen.queryByText('Daily standup')).toBeNull();
  });

  it('opens the settings modal when the header gear button is clicked', async () => {
    installFetch();

    renderFeed();

    await screen.findByText('Hefajstos');
    const settingsButton = screen.getByRole('button', { name: 'Open settings' });
    await userEvent.click(settingsButton);

    expect(await screen.findByRole('dialog')).toBeInTheDocument();
    expect(screen.getByText('Priority')).toBeInTheDocument();
    expect(screen.getByText('Engine type')).toBeInTheDocument();
  });

  it('shows an empty state when the agent has no threads yet', async () => {
    vi.stubGlobal(
      'fetch',
      vi.fn(async () => mockResponse({ threads: [] })),
    );

    renderFeed();

    await waitFor(() => {
      expect(screen.getByText('No conversations yet')).toBeInTheDocument();
    });
  });
});
