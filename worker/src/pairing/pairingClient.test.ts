import { describe, expect, it, vi } from 'vitest';

import { PairingClientError, createPairingClient } from './pairingClient.js';

interface FetchCall {
  readonly url: string;
  readonly init: RequestInit;
}

function createFakeFetch(response: Response): {
  readonly fn: typeof fetch;
  readonly calls: FetchCall[];
} {
  const calls: FetchCall[] = [];
  const fn: typeof fetch = async (input, init = {}) => {
    calls.push({ url: String(input), init });
    return response;
  };
  return { fn, calls };
}

function jsonResponse(status: number, body: unknown): Response {
  return new Response(JSON.stringify(body), {
    status,
    headers: { 'content-type': 'application/json' },
  });
}

describe('createPairingClient', () => {
  it('should POST to /api/workers/pair with the pairing payload and return credentials on 201', async () => {
    const { fn, calls } = createFakeFetch(
      jsonResponse(201, {
        workerId: '11111111-2222-3333-4444-555555555555',
        secretToken: 's3cr3t',
        rabbitmqUser: 'worker-111122223333444455555555',
      }),
    );
    const client = createPairingClient(fn);

    const result = await client({
      apiBaseUrl: 'http://localhost:8080',
      code: 'ABCD-1234',
      name: 'MacBook Pro',
      hostLabel: 'macbook.local',
    });

    expect(calls).toHaveLength(1);
    expect(calls[0].url).toBe('http://localhost:8080/api/workers/pair');
    expect(calls[0].init.method).toBe('POST');
    expect(calls[0].init.headers).toMatchObject({
      'content-type': 'application/json',
      accept: 'application/json',
    });
    expect(JSON.parse(String(calls[0].init.body))).toEqual({
      code: 'ABCD-1234',
      name: 'MacBook Pro',
      hostLabel: 'macbook.local',
    });
    expect(result).toEqual({
      workerId: '11111111-2222-3333-4444-555555555555',
      secretToken: 's3cr3t',
      rabbitmqUser: 'worker-111122223333444455555555',
    });
  });

  it('should omit hostLabel from the payload when not provided', async () => {
    const { fn, calls } = createFakeFetch(
      jsonResponse(201, {
        workerId: 'a',
        secretToken: 'b',
        rabbitmqUser: 'c',
      }),
    );
    const client = createPairingClient(fn);

    await client({
      apiBaseUrl: 'http://localhost:8080',
      code: 'ABCD-1234',
      name: 'MacBook Pro',
    });

    expect(JSON.parse(String(calls[0].init.body))).toEqual({
      code: 'ABCD-1234',
      name: 'MacBook Pro',
    });
  });

  it('should strip a trailing slash on the API base URL', async () => {
    const { fn, calls } = createFakeFetch(
      jsonResponse(201, { workerId: 'a', secretToken: 'b', rabbitmqUser: 'c' }),
    );
    const client = createPairingClient(fn);

    await client({
      apiBaseUrl: 'http://localhost:8080/',
      code: 'ABCD-1234',
      name: 'worker',
    });

    expect(calls[0].url).toBe('http://localhost:8080/api/workers/pair');
  });

  it('should throw PairingClientError with status and body for 404 unknown code', async () => {
    const { fn } = createFakeFetch(
      jsonResponse(404, { title: 'Not Found', detail: 'Pairing code "AAAA-1111" not found.' }),
    );
    const client = createPairingClient(fn);

    await expect(
      client({ apiBaseUrl: 'http://localhost:8080', code: 'AAAA-1111', name: 'w' }),
    ).rejects.toSatisfy((err) => {
      if (!(err instanceof PairingClientError)) return false;
      return err.status === 404 && err.message.includes('not found');
    });
  });

  it('should throw PairingClientError with status 400 for invalid or expired code', async () => {
    const { fn } = createFakeFetch(jsonResponse(400, { detail: 'code expired' }));
    const client = createPairingClient(fn);

    await expect(
      client({ apiBaseUrl: 'http://localhost:8080', code: 'ABCD-1234', name: 'w' }),
    ).rejects.toSatisfy((err) => err instanceof PairingClientError && err.status === 400);
  });

  it('should throw when the response body is missing required fields', async () => {
    const { fn } = createFakeFetch(jsonResponse(201, { workerId: 'a' }));
    const client = createPairingClient(fn);

    await expect(
      client({ apiBaseUrl: 'http://localhost:8080', code: 'ABCD-1234', name: 'w' }),
    ).rejects.toThrow(/response body/);
  });

  it('should propagate network errors from fetch', async () => {
    const brokenFetch = vi.fn(async () => {
      throw new Error('ECONNREFUSED');
    }) as unknown as typeof fetch;
    const client = createPairingClient(brokenFetch);

    await expect(
      client({ apiBaseUrl: 'http://localhost:8080', code: 'ABCD-1234', name: 'w' }),
    ).rejects.toThrow(/ECONNREFUSED/);
  });
});
