import { QueryClient, QueryClientProvider } from '@tanstack/react-query';
import { renderHook, waitFor } from '@testing-library/react';
import type { ReactNode } from 'react';
import { afterEach, beforeEach, describe, expect, it, vi } from 'vitest';

import { clearAuthToken, writeAuthToken } from '@/lib/authToken';

import type { CreateThreadInput, SendMessageInput } from './chatApi';
import { useAgentConversations } from './useAgentConversations';
import { useAgentThreads } from './useAgentThreads';
import { useAttachThread } from './useAttachThread';
import { useCreateThread } from './useCreateThread';
import { useCurrentSession } from './useCurrentSession';
import { useMessages } from './useMessages';
import { useSendMessage } from './useSendMessage';
import { useThreads } from './useThreads';

function mockResponse(body: unknown, status = 200): Response {
  return new Response(body == null ? null : JSON.stringify(body), {
    status,
    headers: { 'content-type': 'application/json' },
  });
}

function createWrapper() {
  const queryClient = new QueryClient({
    defaultOptions: { queries: { retry: false }, mutations: { retry: false } },
  });
  const Wrapper = ({ children }: { children: ReactNode }) => (
    <QueryClientProvider client={queryClient}>{children}</QueryClientProvider>
  );
  return { queryClient, Wrapper };
}

const sessionId = '11111111-1111-4111-8111-111111111111';
const threadId = '22222222-2222-4222-8222-222222222222';
const messageId = '33333333-3333-4333-8333-333333333333';
const userId = '44444444-4444-4444-8444-444444444444';
const orgId = '55555555-5555-4555-8555-555555555555';
const engineId = '66666666-6666-4666-8666-666666666666';

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

const sampleThread = {
  id: threadId,
  sessionId,
  subject: 'Onboarding',
  parentThreadId: null,
  createdAt: '2026-11-16T10:05:00+00:00',
  updatedAt: '2026-11-16T10:05:00+00:00',
};

const agentUuid = '77777777-7777-4777-8777-777777777777';

const sampleAgentConversation = {
  agentId: agentUuid,
  agentName: 'Hefajstos',
  agentSlug: 'hefajstos',
  agentRole: 'Autonomous Builder',
  lastMessagePreview: 'Deploy jutro',
  lastMessageAt: '2026-11-18T08:15:00+00:00',
  threadCount: 2,
  isNew: false,
};

const sampleAgentThread = {
  threadId,
  subject: 'Onboarding',
  shortDescription: null,
  messageCount: 3,
  lastMessageAt: '2026-11-18T08:15:00+00:00',
  parentThreadId: null,
};

const sampleMessage = {
  id: messageId,
  sessionId,
  threadId,
  senderType: 'user' as const,
  senderId: userId,
  recipientType: 'agent' as const,
  recipientId: '77777777-7777-4777-8777-777777777777',
  subject: 'Hello',
  priority: 'P2' as const,
  content: 'Hi there',
  contentType: 'text' as const,
  metadata: {},
  replyToMessageId: null,
  engineId,
  engineType: 'cli_terminal' as const,
  classifiedBy: 'sender' as const,
  classificationConfidence: null,
  createdAt: '2026-11-16T10:10:00+00:00',
};

beforeEach(() => {
  writeAuthToken('test-token');
  vi.stubGlobal('fetch', vi.fn());
});

afterEach(() => {
  clearAuthToken();
  vi.unstubAllGlobals();
  vi.restoreAllMocks();
});

describe('useCurrentSession', () => {
  it('should request /api/chat/sessions/current and return the session', async () => {
    vi.mocked(fetch).mockResolvedValueOnce(mockResponse(sampleSession));

    const { Wrapper } = createWrapper();
    const { result } = renderHook(() => useCurrentSession(), { wrapper: Wrapper });

    await waitFor(() => expect(result.current.isSuccess).toBe(true));
    expect(result.current.data).toEqual(sampleSession);
    expect(vi.mocked(fetch).mock.calls[0]?.[0]).toBe('/api/chat/sessions/current');
  });

  it('should surface 409 no-organization as an error', async () => {
    vi.mocked(fetch).mockResolvedValueOnce(
      mockResponse({ message: 'Caller has no organization' }, 409),
    );

    const { Wrapper } = createWrapper();
    const { result } = renderHook(() => useCurrentSession(), { wrapper: Wrapper });

    await waitFor(() => expect(result.current.isError).toBe(true));
  });
});

describe('useThreads', () => {
  it('should request /api/chat/sessions/:id/threads and return the list', async () => {
    vi.mocked(fetch).mockResolvedValueOnce(mockResponse({ threads: [sampleThread] }));

    const { Wrapper } = createWrapper();
    const { result } = renderHook(() => useThreads(sessionId), { wrapper: Wrapper });

    await waitFor(() => expect(result.current.isSuccess).toBe(true));
    expect(result.current.data).toEqual({ threads: [sampleThread] });
    expect(vi.mocked(fetch).mock.calls[0]?.[0]).toBe(`/api/chat/sessions/${sessionId}/threads`);
  });

  it('should stay disabled when sessionId is null', () => {
    const { Wrapper } = createWrapper();
    const { result } = renderHook(() => useThreads(null), { wrapper: Wrapper });

    expect(result.current.isPending).toBe(true);
    expect(result.current.fetchStatus).toBe('idle');
    expect(vi.mocked(fetch)).not.toHaveBeenCalled();
  });
});

describe('useMessages', () => {
  it('should request /api/chat/threads/:id/messages without query when no pagination given', async () => {
    const payload = { messages: [sampleMessage], total: 1, limit: 50, offset: 0 };
    vi.mocked(fetch).mockResolvedValueOnce(mockResponse(payload));

    const { Wrapper } = createWrapper();
    const { result } = renderHook(() => useMessages({ threadId }), { wrapper: Wrapper });

    await waitFor(() => expect(result.current.isSuccess).toBe(true));
    expect(result.current.data).toEqual(payload);
    expect(vi.mocked(fetch).mock.calls[0]?.[0]).toBe(`/api/chat/threads/${threadId}/messages`);
  });

  it('should append limit and offset as query params when provided', async () => {
    const payload = { messages: [sampleMessage], total: 42, limit: 25, offset: 50 };
    vi.mocked(fetch).mockResolvedValueOnce(mockResponse(payload));

    const { Wrapper } = createWrapper();
    const { result } = renderHook(() => useMessages({ threadId, limit: 25, offset: 50 }), {
      wrapper: Wrapper,
    });

    await waitFor(() => expect(result.current.isSuccess).toBe(true));
    expect(vi.mocked(fetch).mock.calls[0]?.[0]).toBe(
      `/api/chat/threads/${threadId}/messages?limit=25&offset=50`,
    );
  });

  it('should stay disabled when threadId is null', () => {
    const { Wrapper } = createWrapper();
    const { result } = renderHook(() => useMessages({ threadId: null }), { wrapper: Wrapper });

    expect(result.current.isPending).toBe(true);
    expect(result.current.fetchStatus).toBe('idle');
    expect(vi.mocked(fetch)).not.toHaveBeenCalled();
  });
});

describe('useSendMessage', () => {
  function buildInput(): SendMessageInput {
    return {
      sessionId,
      threadId,
      senderType: 'user',
      senderId: userId,
      recipientType: 'agent',
      recipientId: '77777777-7777-4777-8777-777777777777',
      subject: 'Hello',
      priority: 'P2',
      content: 'Hi there',
      contentType: 'text',
      metadata: {},
      replyToMessageId: null,
      engineId,
      engineType: 'cli_terminal',
      classifiedBy: 'sender',
      classificationConfidence: null,
    };
  }

  it('should POST /api/chat/messages with the envelope payload and return the created message', async () => {
    vi.mocked(fetch).mockResolvedValueOnce(mockResponse(sampleMessage, 201));

    const { Wrapper } = createWrapper();
    const { result } = renderHook(() => useSendMessage(), { wrapper: Wrapper });
    const input = buildInput();
    result.current.mutate(input);

    await waitFor(() => expect(result.current.isSuccess).toBe(true));
    expect(result.current.data).toEqual(sampleMessage);
    expect(vi.mocked(fetch).mock.calls[0]?.[0]).toBe('/api/chat/messages');
    const init = vi.mocked(fetch).mock.calls[0]?.[1] as RequestInit;
    expect(init.method).toBe('POST');
    expect(JSON.parse(init.body as string)).toEqual(input);
  });

  it('should invalidate the thread messages query after a successful send', async () => {
    vi.mocked(fetch).mockResolvedValueOnce(mockResponse(sampleMessage, 201));

    const { queryClient, Wrapper } = createWrapper();
    const invalidate = vi.spyOn(queryClient, 'invalidateQueries');
    const { result } = renderHook(() => useSendMessage(), { wrapper: Wrapper });
    result.current.mutate(buildInput());

    await waitFor(() => expect(result.current.isSuccess).toBe(true));
    expect(invalidate).toHaveBeenCalledWith({
      queryKey: ['chat', 'thread', sampleMessage.threadId, 'messages'],
    });
  });

  it('should surface 400 thread-session mismatch as an error', async () => {
    vi.mocked(fetch).mockResolvedValueOnce(
      mockResponse({ message: 'Thread does not belong to session' }, 400),
    );

    const { Wrapper } = createWrapper();
    const { result } = renderHook(() => useSendMessage(), { wrapper: Wrapper });
    result.current.mutate(buildInput());

    await waitFor(() => expect(result.current.isError).toBe(true));
  });
});

describe('useAgentConversations', () => {
  it('should request /api/chat/conversations and return the list', async () => {
    vi.mocked(fetch).mockResolvedValueOnce(
      mockResponse({ conversations: [sampleAgentConversation] }),
    );

    const { Wrapper } = createWrapper();
    const { result } = renderHook(() => useAgentConversations(), { wrapper: Wrapper });

    await waitFor(() => expect(result.current.isSuccess).toBe(true));
    expect(result.current.data).toEqual({ conversations: [sampleAgentConversation] });
    expect(vi.mocked(fetch).mock.calls[0]?.[0]).toBe('/api/chat/conversations');
  });

  it('should surface 401 as an error', async () => {
    vi.mocked(fetch).mockResolvedValueOnce(mockResponse({ message: 'Unauthorized' }, 401));

    const { Wrapper } = createWrapper();
    const { result } = renderHook(() => useAgentConversations(), { wrapper: Wrapper });

    await waitFor(() => expect(result.current.isError).toBe(true));
  });
});

describe('useAgentThreads', () => {
  it('should request /api/chat/agents/:id/threads and return the list', async () => {
    vi.mocked(fetch).mockResolvedValueOnce(mockResponse({ threads: [sampleAgentThread] }));

    const { Wrapper } = createWrapper();
    const { result } = renderHook(() => useAgentThreads(agentUuid), { wrapper: Wrapper });

    await waitFor(() => expect(result.current.isSuccess).toBe(true));
    expect(result.current.data).toEqual({ threads: [sampleAgentThread] });
    expect(vi.mocked(fetch).mock.calls[0]?.[0]).toBe(`/api/chat/agents/${agentUuid}/threads`);
  });

  it('should stay disabled when agentId is null', () => {
    const { Wrapper } = createWrapper();
    const { result } = renderHook(() => useAgentThreads(null), { wrapper: Wrapper });

    expect(result.current.isPending).toBe(true);
    expect(result.current.fetchStatus).toBe('idle');
    expect(vi.mocked(fetch)).not.toHaveBeenCalled();
  });
});

describe('useCreateThread', () => {
  function buildInput(): CreateThreadInput {
    return { sessionId, subject: 'Deploy prod' };
  }

  it('should POST /api/chat/threads with the payload and return the created thread', async () => {
    vi.mocked(fetch).mockResolvedValueOnce(mockResponse(sampleThread, 201));

    const { Wrapper } = createWrapper();
    const { result } = renderHook(() => useCreateThread(), { wrapper: Wrapper });
    result.current.mutate(buildInput());

    await waitFor(() => expect(result.current.isSuccess).toBe(true));
    expect(result.current.data).toEqual(sampleThread);
    expect(vi.mocked(fetch).mock.calls[0]?.[0]).toBe('/api/chat/threads');
    const init = vi.mocked(fetch).mock.calls[0]?.[1] as RequestInit;
    expect(init.method).toBe('POST');
    expect(JSON.parse(init.body as string)).toEqual({
      sessionId,
      subject: 'Deploy prod',
      parentThreadId: null,
    });
  });

  it('should pass parentThreadId through when provided', async () => {
    vi.mocked(fetch).mockResolvedValueOnce(mockResponse(sampleThread, 201));

    const { Wrapper } = createWrapper();
    const { result } = renderHook(() => useCreateThread(), { wrapper: Wrapper });
    const parentThreadId = '88888888-8888-4888-8888-888888888888';
    result.current.mutate({ sessionId, subject: 'Child', parentThreadId });

    await waitFor(() => expect(result.current.isSuccess).toBe(true));
    const init = vi.mocked(fetch).mock.calls[0]?.[1] as RequestInit;
    expect(JSON.parse(init.body as string)).toEqual({
      sessionId,
      subject: 'Child',
      parentThreadId,
    });
  });

  it('should invalidate the threads and conversations queries after a successful create', async () => {
    vi.mocked(fetch).mockResolvedValueOnce(mockResponse(sampleThread, 201));

    const { queryClient, Wrapper } = createWrapper();
    const invalidate = vi.spyOn(queryClient, 'invalidateQueries');
    const { result } = renderHook(() => useCreateThread(), { wrapper: Wrapper });
    result.current.mutate(buildInput());

    await waitFor(() => expect(result.current.isSuccess).toBe(true));
    expect(invalidate).toHaveBeenCalledWith({
      queryKey: ['chat', 'session', sampleThread.sessionId, 'threads'],
    });
    expect(invalidate).toHaveBeenCalledWith({ queryKey: ['chat', 'conversations'] });
  });
});

describe('useAttachThread', () => {
  const parentThreadId = '88888888-8888-4888-8888-888888888888';
  const attachedThread = { ...sampleThread, parentThreadId };

  it('should POST /api/chat/threads/:id/attach with the parent id and return the thread', async () => {
    vi.mocked(fetch).mockResolvedValueOnce(mockResponse(attachedThread));

    const { Wrapper } = createWrapper();
    const { result } = renderHook(() => useAttachThread(), { wrapper: Wrapper });
    result.current.mutate({ threadId, parentThreadId });

    await waitFor(() => expect(result.current.isSuccess).toBe(true));
    expect(result.current.data).toEqual(attachedThread);
    expect(vi.mocked(fetch).mock.calls[0]?.[0]).toBe(`/api/chat/threads/${threadId}/attach`);
    const init = vi.mocked(fetch).mock.calls[0]?.[1] as RequestInit;
    expect(init.method).toBe('POST');
    expect(JSON.parse(init.body as string)).toEqual({ parentThreadId });
  });

  it('should invalidate the threads and per-agent-threads queries after a successful attach', async () => {
    vi.mocked(fetch).mockResolvedValueOnce(mockResponse(attachedThread));

    const { queryClient, Wrapper } = createWrapper();
    const invalidate = vi.spyOn(queryClient, 'invalidateQueries');
    const { result } = renderHook(() => useAttachThread(), { wrapper: Wrapper });
    result.current.mutate({ threadId, parentThreadId });

    await waitFor(() => expect(result.current.isSuccess).toBe(true));
    expect(invalidate).toHaveBeenCalledWith({
      queryKey: ['chat', 'session', attachedThread.sessionId, 'threads'],
    });
    expect(invalidate).toHaveBeenCalledWith({ queryKey: ['chat', 'agent'] });
  });
});
