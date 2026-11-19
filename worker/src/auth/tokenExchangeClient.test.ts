import { describe, expect, it } from 'vitest';

import { createTokenExchangeClient, TokenExchangeError } from './tokenExchangeClient.js';

function buildFetch(response: Response): typeof fetch {
  return (async () => response) as unknown as typeof fetch;
}

describe('tokenExchangeClient', () => {
  it('should POST refresh token + fingerprint and parse the token result', async () => {
    const calls: Array<{ url: string; init: RequestInit | undefined }> = [];
    const fetchFn = (async (url: unknown, init?: RequestInit) => {
      calls.push({ url: String(url), init });
      return new Response(
        JSON.stringify({
          accessToken: 'jwt-abc',
          refreshToken: 'rotated',
          expiresIn: 900,
          fingerprintWarning: false,
          previousFingerprintMeta: null,
          currentFingerprintHash: null,
        }),
        { status: 200, headers: { 'content-type': 'application/json' } },
      );
    }) as unknown as typeof fetch;

    const client = createTokenExchangeClient({ baseUrl: 'http://localhost:8080/', fetchFn });
    const result = await client.exchange({
      refreshToken: 'current-refresh',
      fingerprintHash: 'f'.repeat(64),
      fingerprintMeta: { hostname: 'test' },
    });

    expect(calls[0].url).toBe('http://localhost:8080/api/workers/me/token');
    expect(calls[0].init?.method).toBe('POST');
    const sentBody = JSON.parse(String((calls[0].init as RequestInit).body)) as {
      refreshToken: string;
      fingerprintHash: string;
    };
    expect(sentBody.refreshToken).toBe('current-refresh');
    expect(sentBody.fingerprintHash).toBe('f'.repeat(64));
    expect(result.accessToken).toBe('jwt-abc');
    expect(result.refreshToken).toBe('rotated');
    expect(result.expiresIn).toBe(900);
    expect(result.fingerprintWarning).toBe(false);
  });

  it('should capture the fingerprint warning fields on mismatch', async () => {
    const fetchFn = buildFetch(
      new Response(
        JSON.stringify({
          accessToken: 'jwt-warn',
          refreshToken: 'rot',
          expiresIn: 900,
          fingerprintWarning: true,
          previousFingerprintMeta: { hostname: 'old' },
          currentFingerprintHash: 'a'.repeat(64),
        }),
        { status: 200 },
      ),
    );

    const client = createTokenExchangeClient({ baseUrl: 'http://api.test', fetchFn });
    const result = await client.exchange({
      refreshToken: 'rt',
      fingerprintHash: 'b'.repeat(64),
      fingerprintMeta: { hostname: 'new' },
    });

    expect(result.fingerprintWarning).toBe(true);
    expect(result.previousFingerprintMeta).toEqual({ hostname: 'old' });
    expect(result.currentFingerprintHash).toBe('a'.repeat(64));
  });

  it('should throw TokenExchangeError on 401', async () => {
    const fetchFn = buildFetch(new Response('{"error":"invalid refresh"}', { status: 401 }));

    const client = createTokenExchangeClient({ baseUrl: 'http://api.test', fetchFn });

    await expect(
      client.exchange({
        refreshToken: 'bad',
        fingerprintHash: 'c'.repeat(64),
        fingerprintMeta: {},
      }),
    ).rejects.toBeInstanceOf(TokenExchangeError);
  });
});
