import type { Logger } from 'pino';

import type { Fingerprint } from '../fingerprint/collector.js';
import type { CredentialsStore, WorkerCredentials } from '../pairing/credentialsStore.js';
import type { TokenExchangeClient } from './tokenExchangeClient.js';

export interface TokenManager {
  readonly getAccessToken: () => Promise<string>;
  readonly forceRefresh: () => Promise<string>;
}

export interface TokenManagerOptions {
  readonly exchangeClient: TokenExchangeClient;
  readonly store: CredentialsStore;
  readonly logger: Logger;
  readonly collectFingerprint: () => Fingerprint;
  readonly clock?: () => number;
  readonly refreshBeforeExpiryMs?: number;
}

export class TokenManagerError extends Error {
  constructor(message: string) {
    super(message);
    this.name = 'TokenManagerError';
  }
}

export function createTokenManager(options: TokenManagerOptions): TokenManager {
  const clock = options.clock ?? ((): number => Date.now());
  const refreshBeforeExpiryMs = options.refreshBeforeExpiryMs ?? 60_000;
  let cachedAccessToken: string | null = null;
  let cachedExpiresAt = 0;

  const refresh = async (): Promise<string> => {
    const creds = await options.store.load();
    if (creds === null) {
      throw new TokenManagerError('Worker not paired; credentials file missing');
    }
    if (creds.refreshToken === undefined) {
      throw new TokenManagerError('Worker credentials missing refreshToken (re-pair required)');
    }

    const fp = options.collectFingerprint();
    const result = await options.exchangeClient.exchange({
      refreshToken: creds.refreshToken,
      fingerprintHash: fp.hash,
      fingerprintMeta: fp.meta,
    });

    const nextCreds: WorkerCredentials = {
      workerId: creds.workerId,
      secretToken: creds.secretToken,
      rabbitmqUser: creds.rabbitmqUser,
      pairedAt: creds.pairedAt,
      refreshToken: result.refreshToken,
    };
    await options.store.save(nextCreds);

    cachedAccessToken = result.accessToken;
    cachedExpiresAt = clock() + result.expiresIn * 1000;

    if (result.fingerprintWarning) {
      options.logger.warn(
        { workerId: creds.workerId, currentFingerprintHash: result.currentFingerprintHash },
        'Backend reports fingerprint mismatch; operator must accept or revoke worker',
      );
    }

    return result.accessToken;
  };

  return {
    getAccessToken: async (): Promise<string> => {
      if (cachedAccessToken !== null && clock() + refreshBeforeExpiryMs < cachedExpiresAt) {
        return cachedAccessToken;
      }
      return refresh();
    },
    forceRefresh: async (): Promise<string> => {
      cachedAccessToken = null;
      cachedExpiresAt = 0;
      return refresh();
    },
  };
}
