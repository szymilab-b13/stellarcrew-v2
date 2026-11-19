import type { TokenManager } from '../auth/tokenManager.js';

export interface WorkerSessionView {
  readonly threadId: string;
  readonly workerId: string;
  readonly claudeSessionId: string;
  readonly lastMessageAt: string | null;
}

export interface RecordMessageInput {
  readonly threadId: string;
  readonly agentId: string;
  readonly content: string;
  readonly replyToMessageId?: string | null;
}

export interface RecordedMessage {
  readonly messageId: string;
  readonly threadId: string;
  readonly createdAt: string;
}

export interface WorkerApiClient {
  readonly getOrCreateSession: (threadId: string) => Promise<WorkerSessionView>;
  readonly recordMessage: (input: RecordMessageInput) => Promise<RecordedMessage>;
}

export class WorkerApiError extends Error {
  public readonly status: number;
  public readonly body: string;

  constructor(status: number, body: string, message: string) {
    super(message);
    this.name = 'WorkerApiError';
    this.status = status;
    this.body = body;
  }
}

export interface WorkerApiClientOptions {
  readonly baseUrl: string;
  readonly tokenManager: TokenManager;
  readonly fetchFn?: typeof fetch;
}

export function createWorkerApiClient({
  baseUrl,
  tokenManager,
  fetchFn = fetch,
}: WorkerApiClientOptions): WorkerApiClient {
  const base = stripTrailingSlash(baseUrl);

  interface AuthorizedInit {
    readonly method: 'GET' | 'POST';
    readonly headers?: Record<string, string>;
    readonly body?: string;
  }

  async function authorizedFetch(url: string, init: AuthorizedInit): Promise<Response> {
    const accessToken = await tokenManager.getAccessToken();
    const headers = buildHeaders(init.headers, accessToken);
    const first = await fetchFn(url, { ...init, headers });
    if (first.status !== 401) {
      return first;
    }
    const refreshed = await tokenManager.forceRefresh();
    const retried = await fetchFn(url, { ...init, headers: buildHeaders(init.headers, refreshed) });
    return retried;
  }

  async function getOrCreateSession(threadId: string): Promise<WorkerSessionView> {
    const url = `${base}/api/workers/me/threads/${threadId}/session`;
    const response = await authorizedFetch(url, { method: 'GET' });
    if (!response.ok) {
      throw await makeError(response, `GET ${url}`);
    }
    return (await response.json()) as WorkerSessionView;
  }

  async function recordMessage(input: RecordMessageInput): Promise<RecordedMessage> {
    const url = `${base}/api/workers/me/messages`;
    const response = await authorizedFetch(url, {
      method: 'POST',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify({
        threadId: input.threadId,
        agentId: input.agentId,
        content: input.content,
        replyToMessageId: input.replyToMessageId ?? null,
      }),
    });
    if (!response.ok) {
      throw await makeError(response, `POST ${url}`);
    }
    return (await response.json()) as RecordedMessage;
  }

  return { getOrCreateSession, recordMessage };
}

function buildHeaders(
  incoming: Record<string, string> | undefined,
  accessToken: string,
): Record<string, string> {
  const base: Record<string, string> = {
    Authorization: `Bearer ${accessToken}`,
    Accept: 'application/json',
  };
  return incoming === undefined ? base : { ...incoming, ...base };
}

async function makeError(response: Response, context: string): Promise<WorkerApiError> {
  const body = await response.text().catch(() => '');
  return new WorkerApiError(
    response.status,
    body,
    `${context} failed with HTTP ${response.status.toString()}`,
  );
}

function stripTrailingSlash(value: string): string {
  return value.endsWith('/') ? value.slice(0, -1) : value;
}
