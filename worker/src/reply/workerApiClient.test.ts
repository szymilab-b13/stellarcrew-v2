import { describe, expect, it, vi } from 'vitest';

import type { TokenManager } from '../auth/tokenManager.js';
import { createWorkerApiClient, WorkerApiError } from './workerApiClient.js';

const WORKER_ID = '01976b7a-9f12-7a5e-b7e2-5c9d2b8a0f00';
const THREAD_ID = '01976b7a-1a9d-7e3a-9b7e-27f28cc8d5f0';
const AGENT_ID = '01976b7a-ab72-7f5e-8a9c-02dc3f4e9c10';
const USER_MESSAGE_ID = '01976b7a-ba12-7a6b-9f3b-40ab82c9d100';

function jsonResponse(status: number, body: unknown): Response {
  return new Response(JSON.stringify(body), {
    status,
    headers: { 'Content-Type': 'application/json' },
  });
}

function makeTokenManager(access = 'access-token'): {
  readonly manager: TokenManager;
  readonly calls: { get: number; force: number };
} {
  const calls = { get: 0, force: 0 };
  let current = access;
  return {
    manager: {
      getAccessToken: async () => {
        calls.get += 1;
        return current;
      },
      forceRefresh: async () => {
        calls.force += 1;
        current = 'refreshed-token';
        return current;
      },
    },
    calls,
  };
}

describe('WorkerApiClient.getOrCreateSession', () => {
  it('attaches Authorization bearer header on GET', async () => {
    const fetchFn = vi.fn().mockResolvedValue(
      jsonResponse(200, {
        threadId: THREAD_ID,
        workerId: WORKER_ID,
        claudeSessionId: 'session-abc',
        lastMessageAt: null,
      }),
    );
    const tm = makeTokenManager('jwt-xyz');
    const client = createWorkerApiClient({
      baseUrl: 'http://api.test/',
      tokenManager: tm.manager,
      fetchFn: fetchFn as unknown as typeof fetch,
    });

    const session = await client.getOrCreateSession(THREAD_ID);

    expect(session.claudeSessionId).toBe('session-abc');
    const [url, init] = fetchFn.mock.calls[0] as [string, RequestInit];
    expect(url).toBe(`http://api.test/api/workers/me/threads/${THREAD_ID}/session`);
    expect(init.method).toBe('GET');
    expect(init.headers).toMatchObject({
      Authorization: 'Bearer jwt-xyz',
      Accept: 'application/json',
    });
  });

  it('throws WorkerApiError when API responds non-2xx (non-401)', async () => {
    const fetchFn = vi
      .fn()
      .mockResolvedValue(new Response('{"title":"Thread not found"}', { status: 404 }));
    const tm = makeTokenManager();
    const client = createWorkerApiClient({
      baseUrl: 'http://api.test',
      tokenManager: tm.manager,
      fetchFn: fetchFn as unknown as typeof fetch,
    });

    await expect(client.getOrCreateSession(THREAD_ID)).rejects.toBeInstanceOf(WorkerApiError);
  });

  it('retries once with a forced refresh when 401 is returned', async () => {
    const fetchFn = vi
      .fn()
      .mockResolvedValueOnce(new Response('', { status: 401 }))
      .mockResolvedValueOnce(
        jsonResponse(200, {
          threadId: THREAD_ID,
          workerId: WORKER_ID,
          claudeSessionId: 'session-xyz',
          lastMessageAt: null,
        }),
      );
    const tm = makeTokenManager('old-token');
    const client = createWorkerApiClient({
      baseUrl: 'http://api.test',
      tokenManager: tm.manager,
      fetchFn: fetchFn as unknown as typeof fetch,
    });

    const session = await client.getOrCreateSession(THREAD_ID);

    expect(session.claudeSessionId).toBe('session-xyz');
    expect(tm.calls.force).toBe(1);
    const [, firstInit] = fetchFn.mock.calls[0] as [string, RequestInit];
    const [, secondInit] = fetchFn.mock.calls[1] as [string, RequestInit];
    expect(firstInit.headers).toMatchObject({ Authorization: 'Bearer old-token' });
    expect(secondInit.headers).toMatchObject({ Authorization: 'Bearer refreshed-token' });
  });

  it('surfaces WorkerApiError when retry after 401 still fails', async () => {
    const fetchFn = vi
      .fn()
      .mockResolvedValueOnce(new Response('', { status: 401 }))
      .mockResolvedValueOnce(new Response('{"detail":"still forbidden"}', { status: 401 }));
    const tm = makeTokenManager();
    const client = createWorkerApiClient({
      baseUrl: 'http://api.test',
      tokenManager: tm.manager,
      fetchFn: fetchFn as unknown as typeof fetch,
    });

    await expect(client.getOrCreateSession(THREAD_ID)).rejects.toBeInstanceOf(WorkerApiError);
  });
});

describe('WorkerApiClient.recordMessage', () => {
  it('posts to /api/workers/me/messages with Bearer + JSON body', async () => {
    const fetchFn = vi.fn().mockResolvedValue(
      jsonResponse(201, {
        messageId: '01976b7a-bbee-7e3a-9d7e-27f28cc8d5f0',
        threadId: THREAD_ID,
        createdAt: '2026-11-18T18:00:00Z',
      }),
    );
    const tm = makeTokenManager('jwt-post');
    const client = createWorkerApiClient({
      baseUrl: 'http://api.test',
      tokenManager: tm.manager,
      fetchFn: fetchFn as unknown as typeof fetch,
    });

    const recorded = await client.recordMessage({
      threadId: THREAD_ID,
      agentId: AGENT_ID,
      content: 'Here is the draft.',
      replyToMessageId: USER_MESSAGE_ID,
    });

    expect(recorded.messageId).toBe('01976b7a-bbee-7e3a-9d7e-27f28cc8d5f0');
    const [url, init] = fetchFn.mock.calls[0] as [string, RequestInit];
    expect(url).toBe('http://api.test/api/workers/me/messages');
    expect(init.method).toBe('POST');
    expect(init.headers).toMatchObject({
      Authorization: 'Bearer jwt-post',
      'Content-Type': 'application/json',
    });
    const body: unknown = JSON.parse(init.body as string);
    expect(body).toEqual({
      threadId: THREAD_ID,
      agentId: AGENT_ID,
      content: 'Here is the draft.',
      replyToMessageId: USER_MESSAGE_ID,
    });
  });

  it('coalesces missing replyToMessageId to null in the body', async () => {
    const fetchFn = vi
      .fn()
      .mockResolvedValue(
        jsonResponse(201, { messageId: 'id', threadId: THREAD_ID, createdAt: 'x' }),
      );
    const tm = makeTokenManager();
    const client = createWorkerApiClient({
      baseUrl: 'http://api.test',
      tokenManager: tm.manager,
      fetchFn: fetchFn as unknown as typeof fetch,
    });

    await client.recordMessage({
      threadId: THREAD_ID,
      agentId: AGENT_ID,
      content: 'Content.',
    });

    const [, init] = fetchFn.mock.calls[0] as [string, RequestInit];
    const body: unknown = JSON.parse(init.body as string);
    expect(body).toEqual({
      threadId: THREAD_ID,
      agentId: AGENT_ID,
      content: 'Content.',
      replyToMessageId: null,
    });
  });

  it('throws WorkerApiError when the API responds non-2xx (non-401)', async () => {
    const fetchFn = vi.fn().mockResolvedValue(new Response('{"detail":"nope"}', { status: 403 }));
    const tm = makeTokenManager();
    const client = createWorkerApiClient({
      baseUrl: 'http://api.test',
      tokenManager: tm.manager,
      fetchFn: fetchFn as unknown as typeof fetch,
    });

    await expect(
      client.recordMessage({ threadId: THREAD_ID, agentId: AGENT_ID, content: 'c' }),
    ).rejects.toBeInstanceOf(WorkerApiError);
  });
});
