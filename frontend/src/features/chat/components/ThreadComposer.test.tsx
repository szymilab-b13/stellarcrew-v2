import { QueryClient, QueryClientProvider } from '@tanstack/react-query';
import { render, screen, waitFor } from '@testing-library/react';
import userEvent from '@testing-library/user-event';
import type { ReactNode } from 'react';
import { afterEach, beforeEach, describe, expect, it, vi } from 'vitest';

import '@/i18n/i18n';
import { clearAuthToken, writeAuthToken } from '@/lib/authToken';

import { ThreadComposer } from './ThreadComposer';

const sessionId = 'ssssssss-ssss-4sss-8sss-ssssssssssss';
const agentId = 'aaaaaaaa-aaaa-4aaa-8aaa-aaaaaaaaaaaa';
const senderId = 'user-id-1';
const existingThreadId = 'tttttttt-tttt-4ttt-8ttt-tttttttttttt';

function mockResponse(body: unknown, status = 200): Response {
  return new Response(body == null ? null : JSON.stringify(body), {
    status,
    headers: { 'content-type': 'application/json' },
  });
}

interface FetchScript {
  createThread?: (body: string | null) => Response | Promise<Response>;
  sendMessage?: (body: string | null) => Response | Promise<Response>;
}

function installFetch(script: FetchScript = {}) {
  const fetchMock = vi.fn(async (input: RequestInfo | URL, init?: RequestInit) => {
    const url = typeof input === 'string' ? input : input.toString();
    const body = typeof init?.body === 'string' ? init.body : null;
    if (url === '/api/chat/threads') {
      return (
        script.createThread?.(body) ??
        mockResponse(
          {
            id: 'c0000001-0000-4000-8000-000000000001',
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
    if (url === '/api/chat/messages') {
      return (
        script.sendMessage?.(body) ??
        mockResponse(
          {
            id: 'm0000001-0000-4000-8000-000000000001',
            sessionId,
            threadId: existingThreadId,
            senderType: 'user',
            senderId,
            recipientType: 'agent',
            recipientId: agentId,
            subject: 'Chat with Hefajstos',
            priority: 'P2',
            content: 'Hello',
            contentType: 'text',
            metadata: {},
            replyToMessageId: null,
            engineId: agentId,
            engineType: 'cli_terminal',
            classifiedBy: 'sender',
            classificationConfidence: null,
            createdAt: '2026-11-18T12:00:01+00:00',
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

function renderComposer(threadId: string | null = existingThreadId) {
  const onThreadCreated = vi.fn();
  const queryClient = new QueryClient({
    defaultOptions: { queries: { retry: false }, mutations: { retry: false } },
  });
  function Wrapper({ children }: { children: ReactNode }) {
    return <QueryClientProvider client={queryClient}>{children}</QueryClientProvider>;
  }
  const utils = render(
    <ThreadComposer
      sessionId={sessionId}
      agentId={agentId}
      senderId={senderId}
      threadId={threadId}
      defaultSubject="Chat with Hefajstos"
      onThreadCreated={onThreadCreated}
    />,
    { wrapper: Wrapper },
  );
  return { ...utils, onThreadCreated };
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

describe('ThreadComposer', () => {
  it('disables the send button when content is empty', () => {
    installFetch();
    renderComposer();

    const sendButton = screen.getByRole('button', { name: 'Send' });
    expect(sendButton).toBeDisabled();
  });

  it('sends a message to the existing thread using stored defaults', async () => {
    const seen: string[] = [];
    installFetch({
      sendMessage: (body) => {
        if (body) {
          seen.push(body);
        }
        return mockResponse(
          {
            id: 'm0000001-0000-4000-8000-000000000001',
            sessionId,
            threadId: existingThreadId,
            senderType: 'user',
            senderId,
            recipientType: 'agent',
            recipientId: agentId,
            subject: 'Chat with Hefajstos',
            priority: 'P2',
            content: 'Hello',
            contentType: 'text',
            metadata: {},
            replyToMessageId: null,
            engineId: agentId,
            engineType: 'cli_terminal',
            classifiedBy: 'sender',
            classificationConfidence: null,
            createdAt: '2026-11-18T12:00:01+00:00',
          },
          201,
        );
      },
    });

    renderComposer(existingThreadId);

    const textarea = screen.getByPlaceholderText('Write a message…');
    await userEvent.type(textarea, 'Hello');
    await userEvent.click(screen.getByRole('button', { name: 'Send' }));

    await waitFor(() => expect(seen).toHaveLength(1));
    const payload = JSON.parse(seen[0]);
    expect(payload.threadId).toBe(existingThreadId);
    expect(payload.content).toBe('Hello');
    expect(payload.priority).toBe('P2');
    expect(payload.engineType).toBe('cli_terminal');
    expect(payload.engineId).toBe(agentId);
  });

  it('creates a new thread first when threadId prop is null', async () => {
    const createCalls: string[] = [];
    const sendCalls: string[] = [];
    installFetch({
      createThread: (body) => {
        if (body) {
          createCalls.push(body);
        }
        return mockResponse(
          {
            id: 'c0000001-0000-4000-8000-000000000001',
            sessionId,
            subject: 'Chat with Hefajstos',
            parentThreadId: null,
            createdAt: '2026-11-18T12:00:00+00:00',
            updatedAt: '2026-11-18T12:00:00+00:00',
          },
          201,
        );
      },
      sendMessage: (body) => {
        if (body) {
          sendCalls.push(body);
        }
        return mockResponse(
          {
            id: 'm0000001-0000-4000-8000-000000000001',
            sessionId,
            threadId: 'c0000001-0000-4000-8000-000000000001',
            senderType: 'user',
            senderId,
            recipientType: 'agent',
            recipientId: agentId,
            subject: 'Chat with Hefajstos',
            priority: 'P2',
            content: 'First message',
            contentType: 'text',
            metadata: {},
            replyToMessageId: null,
            engineId: agentId,
            engineType: 'cli_terminal',
            classifiedBy: 'sender',
            classificationConfidence: null,
            createdAt: '2026-11-18T12:00:01+00:00',
          },
          201,
        );
      },
    });

    const { onThreadCreated } = renderComposer(null);

    const textarea = screen.getByPlaceholderText('Write a message…');
    await userEvent.type(textarea, 'First message');
    await userEvent.click(screen.getByRole('button', { name: 'Send' }));

    await waitFor(() => expect(sendCalls).toHaveLength(1));
    expect(createCalls).toHaveLength(1);
    const sendPayload = JSON.parse(sendCalls[0]);
    expect(sendPayload.threadId).toBe('c0000001-0000-4000-8000-000000000001');
    expect(onThreadCreated).toHaveBeenCalledWith('c0000001-0000-4000-8000-000000000001');
  });

  it('shows an inline error when the send request fails', async () => {
    installFetch({
      sendMessage: () => mockResponse({ error: 'boom' }, 500),
    });

    renderComposer(existingThreadId);

    const textarea = screen.getByPlaceholderText('Write a message…');
    await userEvent.type(textarea, 'Hello');
    await userEvent.click(screen.getByRole('button', { name: 'Send' }));

    expect(await screen.findByText('Could not send the message. Try again.')).toBeInTheDocument();
  });

  it('uses the stored engine type and priority from localStorage', async () => {
    window.localStorage.setItem(
      `chat.agent.defaults.${agentId}`,
      JSON.stringify({ engineType: 'webhook', engineId: 'custom-engine', priority: 'P0' }),
    );
    const seen: string[] = [];
    installFetch({
      sendMessage: (body) => {
        if (body) {
          seen.push(body);
        }
        return mockResponse(
          {
            id: 'm0000001-0000-4000-8000-000000000001',
            sessionId,
            threadId: existingThreadId,
            senderType: 'user',
            senderId,
            recipientType: 'agent',
            recipientId: agentId,
            subject: 'Chat with Hefajstos',
            priority: 'P0',
            content: 'Hello',
            contentType: 'text',
            metadata: {},
            replyToMessageId: null,
            engineId: 'custom-engine',
            engineType: 'webhook',
            classifiedBy: 'sender',
            classificationConfidence: null,
            createdAt: '2026-11-18T12:00:01+00:00',
          },
          201,
        );
      },
    });

    renderComposer(existingThreadId);

    const textarea = screen.getByPlaceholderText('Write a message…');
    await userEvent.type(textarea, 'Hello');
    await userEvent.click(screen.getByRole('button', { name: 'Send' }));

    await waitFor(() => expect(seen).toHaveLength(1));
    const payload = JSON.parse(seen[0]);
    expect(payload.engineType).toBe('webhook');
    expect(payload.engineId).toBe('custom-engine');
    expect(payload.priority).toBe('P0');
  });
});
