import { QueryClient, QueryClientProvider } from '@tanstack/react-query';
import { render, screen } from '@testing-library/react';
import type { ReactNode } from 'react';
import { afterEach, beforeEach, describe, expect, it, vi } from 'vitest';

import '@/i18n/i18n';
import { clearAuthToken, writeAuthToken } from '@/lib/authToken';

import type { ChatMessage } from '../types';
import { MessageList } from './MessageList';

const threadId = '11111111-1111-4111-8111-111111111111';

function makeMessage(overrides?: Partial<ChatMessage>): ChatMessage {
  return {
    id: overrides?.id ?? '22222222-2222-4222-8222-222222222222',
    sessionId: '33333333-3333-4333-8333-333333333333',
    threadId,
    senderType: 'agent',
    senderId: '44444444-4444-4444-8444-444444444444',
    recipientType: 'user',
    recipientId: '55555555-5555-4555-8555-555555555555',
    subject: 'Welcome',
    priority: 'P2',
    content: 'Hi from agent',
    contentType: 'text',
    metadata: {},
    replyToMessageId: null,
    engineId: '66666666-6666-4666-8666-666666666666',
    engineType: 'cli_terminal',
    classifiedBy: 'sender',
    classificationConfidence: null,
    createdAt: '2026-11-16T10:30:00+00:00',
    ...overrides,
  };
}

function mockResponse(body: unknown, status = 200): Response {
  return new Response(body == null ? null : JSON.stringify(body), {
    status,
    headers: { 'content-type': 'application/json' },
  });
}

function installFetch(handler: () => Response | Promise<Response>) {
  const fetchMock = vi.fn(async (input: RequestInfo | URL) => {
    const url = typeof input === 'string' ? input : input.toString();
    if (url === `/api/chat/threads/${threadId}/messages`) {
      return handler();
    }
    return mockResponse({}, 404);
  });
  vi.stubGlobal('fetch', fetchMock);
  return fetchMock;
}

function renderList() {
  const queryClient = new QueryClient({
    defaultOptions: { queries: { retry: false }, mutations: { retry: false } },
  });
  function Wrapper({ children }: { children: ReactNode }) {
    return <QueryClientProvider client={queryClient}>{children}</QueryClientProvider>;
  }
  return render(<MessageList threadId={threadId} />, { wrapper: Wrapper });
}

beforeEach(() => {
  writeAuthToken('test-token');
});

afterEach(() => {
  clearAuthToken();
  vi.unstubAllGlobals();
  vi.restoreAllMocks();
});

describe('MessageList', () => {
  it('should show a loading message while messages resolve', () => {
    installFetch(() => new Promise<Response>(() => undefined));

    renderList();

    expect(screen.getByText('Loading messages…')).toBeInTheDocument();
  });

  it('should show an error alert when the request fails', async () => {
    installFetch(() => mockResponse({ error: 'boom' }, 500));

    renderList();

    expect(await screen.findByRole('alert')).toHaveTextContent('Could not load messages.');
  });

  it('should show an empty-state message when the thread has no messages', async () => {
    installFetch(() => mockResponse({ messages: [], total: 0, limit: 50, offset: 0 }));

    renderList();

    expect(await screen.findByText('No messages yet')).toBeInTheDocument();
  });

  it('should render a bubble per message when the thread has messages', async () => {
    const first = makeMessage({
      id: 'aaaaaaaa-aaaa-4aaa-8aaa-aaaaaaaaaaaa',
      content: 'First bubble body',
    });
    const second = makeMessage({
      id: 'bbbbbbbb-bbbb-4bbb-8bbb-bbbbbbbbbbbb',
      content: 'Second bubble body',
      senderType: 'user',
    });
    installFetch(() => mockResponse({ messages: [first, second], total: 2, limit: 50, offset: 0 }));

    renderList();

    expect(await screen.findByText('First bubble body')).toBeInTheDocument();
    expect(screen.getByText('Second bubble body')).toBeInTheDocument();
    expect(screen.getByRole('list', { name: 'Messages in thread' })).toBeInTheDocument();
  });
});
