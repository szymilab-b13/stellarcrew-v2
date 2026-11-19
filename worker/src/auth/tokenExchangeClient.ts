export interface TokenExchangeInput {
  readonly refreshToken: string;
  readonly fingerprintHash: string;
  readonly fingerprintMeta: object;
}

export interface TokenExchangeResult {
  readonly accessToken: string;
  readonly refreshToken: string;
  readonly expiresIn: number;
  readonly fingerprintWarning: boolean;
  readonly previousFingerprintMeta: Record<string, unknown> | null;
  readonly currentFingerprintHash: string | null;
}

export class TokenExchangeError extends Error {
  public readonly status: number;
  public readonly body: string;

  constructor(status: number, body: string, message: string) {
    super(message);
    this.name = 'TokenExchangeError';
    this.status = status;
    this.body = body;
  }
}

export interface TokenExchangeClient {
  readonly exchange: (input: TokenExchangeInput) => Promise<TokenExchangeResult>;
}

export interface TokenExchangeClientOptions {
  readonly baseUrl: string;
  readonly fetchFn?: typeof fetch;
}

export function createTokenExchangeClient({
  baseUrl,
  fetchFn = fetch,
}: TokenExchangeClientOptions): TokenExchangeClient {
  const base = baseUrl.endsWith('/') ? baseUrl.slice(0, -1) : baseUrl;

  return {
    exchange: async (input) => {
      const url = `${base}/api/workers/me/token`;
      const response = await fetchFn(url, {
        method: 'POST',
        headers: {
          'content-type': 'application/json',
          accept: 'application/json',
        },
        body: JSON.stringify({
          refreshToken: input.refreshToken,
          fingerprintHash: input.fingerprintHash,
          fingerprintMeta: input.fingerprintMeta,
        }),
      });

      if (!response.ok) {
        const body = await response.text().catch(() => '');
        throw new TokenExchangeError(
          response.status,
          body,
          `Token exchange failed with HTTP ${String(response.status)}`,
        );
      }

      const raw = (await response.json()) as Record<string, unknown>;
      return {
        accessToken: String(raw.accessToken ?? ''),
        refreshToken: String(raw.refreshToken ?? ''),
        expiresIn: typeof raw.expiresIn === 'number' ? raw.expiresIn : 0,
        fingerprintWarning: Boolean(raw.fingerprintWarning),
        previousFingerprintMeta:
          raw.previousFingerprintMeta === null || raw.previousFingerprintMeta === undefined
            ? null
            : (raw.previousFingerprintMeta as Record<string, unknown>),
        currentFingerprintHash:
          typeof raw.currentFingerprintHash === 'string' ? raw.currentFingerprintHash : null,
      };
    },
  };
}
