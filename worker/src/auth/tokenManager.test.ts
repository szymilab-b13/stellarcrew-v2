import type { Logger } from 'pino';
import { describe, expect, it, vi } from 'vitest';

import type { Fingerprint } from '../fingerprint/collector.js';
import type { CredentialsStore, WorkerCredentials } from '../pairing/credentialsStore.js';
import { createTokenManager, TokenManagerError } from './tokenManager.js';
import type { TokenExchangeClient, TokenExchangeResult } from './tokenExchangeClient.js';

const sampleFingerprint: Fingerprint = {
  hash: 'a'.repeat(64),
  meta: {
    machineId: 'machine',
    hostname: 'host',
    primaryMac: '00:00:00:00:00:01',
    cpuModel: 'cpu',
    cpuCount: 2,
    osPlatform: 'linux',
    osRelease: '6.0.0',
    osVersion: '#1',
    arch: 'x64',
  },
};

function makeCreds(overrides: Partial<WorkerCredentials> = {}): WorkerCredentials {
  return {
    workerId: 'worker-1',
    secretToken: 'secret',
    rabbitmqUser: 'rmq',
    pairedAt: '2026-11-19T10:00:00Z',
    refreshToken: 'current-refresh',
    ...overrides,
  };
}

function makeStore(initial: WorkerCredentials | null): {
  readonly store: CredentialsStore;
  readonly saved: WorkerCredentials[];
} {
  const saved: WorkerCredentials[] = [];
  let state = initial;
  const store: CredentialsStore = {
    path: '/fake/worker.json',
    load: async () => state,
    save: async (c) => {
      saved.push(c);
      state = c;
    },
  };
  return { store, saved };
}

function makeExchange(result: TokenExchangeResult): {
  readonly client: TokenExchangeClient;
  readonly calls: number;
} {
  let calls = 0;
  const client: TokenExchangeClient = {
    exchange: async () => {
      calls += 1;
      return result;
    },
  };
  return {
    client,
    get calls() {
      return calls;
    },
  };
}

const silentLogger: Logger = {
  warn: () => {},
  info: () => {},
  error: () => {},
  debug: () => {},
  trace: () => {},
  fatal: () => {},
} as unknown as Logger;

const sampleResult: TokenExchangeResult = {
  accessToken: 'jwt-abc',
  refreshToken: 'rotated',
  expiresIn: 900,
  fingerprintWarning: false,
  previousFingerprintMeta: null,
  currentFingerprintHash: null,
};

describe('tokenManager', () => {
  it('should cache the access token and skip exchange within the expiry window', async () => {
    const { store } = makeStore(makeCreds());
    const exchange = makeExchange(sampleResult);
    const clock = vi.fn().mockReturnValue(1_000);
    const sut = createTokenManager({
      exchangeClient: exchange.client,
      store,
      logger: silentLogger,
      collectFingerprint: () => sampleFingerprint,
      clock,
    });

    const first = await sut.getAccessToken();
    const second = await sut.getAccessToken();

    expect(first).toBe('jwt-abc');
    expect(second).toBe('jwt-abc');
    expect(exchange.calls).toBe(1);
  });

  it('should refresh when cached token is near expiry', async () => {
    const { store } = makeStore(makeCreds());
    const exchange = makeExchange(sampleResult);
    const clockStub = vi
      .fn()
      .mockReturnValueOnce(1_000)
      .mockReturnValueOnce(1_000 + 900 * 1000)
      .mockReturnValueOnce(1_000 + 900 * 1000);
    const sut = createTokenManager({
      exchangeClient: exchange.client,
      store,
      logger: silentLogger,
      collectFingerprint: () => sampleFingerprint,
      clock: clockStub,
      refreshBeforeExpiryMs: 60_000,
    });

    await sut.getAccessToken();
    await sut.getAccessToken();

    expect(exchange.calls).toBe(2);
  });

  it('should rotate refresh token in the store on each exchange', async () => {
    const { store, saved } = makeStore(makeCreds({ refreshToken: 'start' }));
    const exchange = makeExchange({ ...sampleResult, refreshToken: 'rotated-1' });
    const sut = createTokenManager({
      exchangeClient: exchange.client,
      store,
      logger: silentLogger,
      collectFingerprint: () => sampleFingerprint,
    });

    await sut.getAccessToken();

    expect(saved).toHaveLength(1);
    expect(saved[0].refreshToken).toBe('rotated-1');
  });

  it('should throw when no credentials file', async () => {
    const { store } = makeStore(null);
    const exchange = makeExchange(sampleResult);
    const sut = createTokenManager({
      exchangeClient: exchange.client,
      store,
      logger: silentLogger,
      collectFingerprint: () => sampleFingerprint,
    });

    await expect(sut.getAccessToken()).rejects.toBeInstanceOf(TokenManagerError);
  });

  it('should throw when credentials lack refresh token', async () => {
    const { store } = makeStore(makeCreds({ refreshToken: undefined }));
    const exchange = makeExchange(sampleResult);
    const sut = createTokenManager({
      exchangeClient: exchange.client,
      store,
      logger: silentLogger,
      collectFingerprint: () => sampleFingerprint,
    });

    await expect(sut.getAccessToken()).rejects.toBeInstanceOf(TokenManagerError);
  });

  it('should bypass cache on forceRefresh', async () => {
    const { store } = makeStore(makeCreds());
    const exchange = makeExchange(sampleResult);
    const sut = createTokenManager({
      exchangeClient: exchange.client,
      store,
      logger: silentLogger,
      collectFingerprint: () => sampleFingerprint,
    });

    await sut.getAccessToken();
    await sut.forceRefresh();

    expect(exchange.calls).toBe(2);
  });

  it('should log a warning when backend signals fingerprint mismatch', async () => {
    const { store } = makeStore(makeCreds());
    const exchange = makeExchange({
      ...sampleResult,
      fingerprintWarning: true,
      currentFingerprintHash: 'b'.repeat(64),
    });
    const warn = vi.fn();
    const sut = createTokenManager({
      exchangeClient: exchange.client,
      store,
      logger: { ...silentLogger, warn } as unknown as Logger,
      collectFingerprint: () => sampleFingerprint,
    });

    await sut.getAccessToken();

    expect(warn).toHaveBeenCalledTimes(1);
  });
});
