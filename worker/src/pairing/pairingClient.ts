export interface PairWorkerInput {
  readonly apiBaseUrl: string;
  readonly code: string;
  readonly name: string;
  readonly hostLabel?: string;
  readonly fingerprintHash?: string;
  readonly fingerprintMeta?: object;
}

export interface PairWorkerResult {
  readonly workerId: string;
  readonly secretToken: string;
  readonly rabbitmqUser: string;
  readonly refreshToken?: string;
}

export type PairingClient = (input: PairWorkerInput) => Promise<PairWorkerResult>;

export class PairingClientError extends Error {
  public readonly status: number;
  public readonly responseBody: string;

  public constructor(status: number, responseBody: string, message: string) {
    super(message);
    this.name = 'PairingClientError';
    this.status = status;
    this.responseBody = responseBody;
  }
}

export function createPairingClient(fetchFn: typeof fetch = fetch): PairingClient {
  return async (input: PairWorkerInput): Promise<PairWorkerResult> => {
    const base = input.apiBaseUrl.endsWith('/') ? input.apiBaseUrl.slice(0, -1) : input.apiBaseUrl;
    const url = `${base}/api/workers/pair`;

    const payload: Record<string, unknown> = {
      code: input.code,
      name: input.name,
    };
    if (input.hostLabel !== undefined && input.hostLabel !== '') {
      payload.hostLabel = input.hostLabel;
    }
    if (input.fingerprintHash !== undefined && input.fingerprintHash !== '') {
      payload.fingerprintHash = input.fingerprintHash;
    }
    if (input.fingerprintMeta !== undefined) {
      payload.fingerprintMeta = input.fingerprintMeta;
    }

    const response = await fetchFn(url, {
      method: 'POST',
      headers: {
        'content-type': 'application/json',
        accept: 'application/json',
      },
      body: JSON.stringify(payload),
    });

    if (!response.ok) {
      const text = await response.text();
      throw new PairingClientError(response.status, text, buildErrorMessage(response.status, text));
    }

    const raw: unknown = await response.json();
    return assertPairingResult(raw);
  };
}

function buildErrorMessage(status: number, body: string): string {
  const snippet = extractDetail(body);
  const suffix = snippet === null ? '' : `: ${snippet}`;
  return `Pairing failed with HTTP ${String(status)}${suffix}`;
}

function extractDetail(body: string): string | null {
  if (body === '') return null;
  try {
    const parsed = JSON.parse(body) as unknown;
    if (isRecord(parsed)) {
      const detail = parsed.detail ?? parsed.title ?? parsed.message;
      if (typeof detail === 'string' && detail !== '') return detail;
    }
  } catch {
    return body;
  }
  return body;
}

function assertPairingResult(raw: unknown): PairWorkerResult {
  if (!isRecord(raw)) {
    throw new Error('Pairing response body is not an object');
  }
  const workerId = raw.workerId;
  const secretToken = raw.secretToken;
  const rabbitmqUser = raw.rabbitmqUser;
  if (
    typeof workerId !== 'string' ||
    typeof secretToken !== 'string' ||
    typeof rabbitmqUser !== 'string'
  ) {
    throw new Error('Pairing response body is missing workerId, secretToken or rabbitmqUser');
  }
  const refreshToken = typeof raw.refreshToken === 'string' ? raw.refreshToken : undefined;
  return refreshToken !== undefined
    ? { workerId, secretToken, rabbitmqUser, refreshToken }
    : { workerId, secretToken, rabbitmqUser };
}

function isRecord(value: unknown): value is Record<string, unknown> {
  return typeof value === 'object' && value !== null;
}
