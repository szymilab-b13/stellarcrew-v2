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
  readonly workerId: string;
  readonly secretToken: string;
  readonly fetchFn?: typeof fetch;
}

export function createWorkerApiClient({
  baseUrl,
  workerId,
  secretToken,
  fetchFn = fetch,
}: WorkerApiClientOptions): WorkerApiClient {
  const base = stripTrailingSlash(baseUrl);
  const authHeaders: Record<string, string> = {
    'X-Worker-Id': workerId,
    'X-Worker-Secret': secretToken,
    Accept: 'application/json',
  };

  async function getOrCreateSession(threadId: string): Promise<WorkerSessionView> {
    const url = `${base}/api/workers/me/threads/${threadId}/session`;
    const response = await fetchFn(url, {
      method: 'GET',
      headers: authHeaders,
    });
    if (!response.ok) {
      throw await makeError(response, `GET ${url}`);
    }
    return (await response.json()) as WorkerSessionView;
  }

  async function recordMessage(input: RecordMessageInput): Promise<RecordedMessage> {
    const url = `${base}/api/workers/me/messages`;
    const response = await fetchFn(url, {
      method: 'POST',
      headers: {
        ...authHeaders,
        'Content-Type': 'application/json',
      },
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
