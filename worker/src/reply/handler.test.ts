import type { Logger } from 'pino';
import { describe, expect, it, vi } from 'vitest';

import type { AgentReplyPayload } from '../amqp/payload.js';
import { createReplyHandler } from './handler.js';
import type { ClaudeRunner } from './claudeRunner.js';
import type { RecordedMessage, WorkerApiClient, WorkerSessionView } from './workerApiClient.js';

const payload: AgentReplyPayload = {
  threadId: '01976b7a-1a9d-7e3a-9b7e-27f28cc8d5f0',
  workerId: '01976b7a-9f12-7a5e-b7e2-5c9d2b8a0f00',
  agentId: '01976b7a-ab72-7f5e-8a9c-02dc3f4e9c10',
  userMessageId: '01976b7a-ba12-7a6b-9f3b-40ab82c9d100',
  userContent: 'Please draft the release notes.',
  subject: 'Release notes draft',
};

const session: WorkerSessionView = {
  threadId: payload.threadId,
  workerId: payload.workerId,
  claudeSessionId: '01976b7a-cafe-7d5e-8d9c-02dc3f4e9c10',
  lastMessageAt: null,
};

const recorded: RecordedMessage = {
  messageId: '01976b7a-feed-7e3a-9d7e-27f28cc8d5f0',
  threadId: payload.threadId,
  createdAt: '2026-11-18T18:00:00Z',
};

function silentLogger(): Logger {
  return {
    info: vi.fn(),
    warn: vi.fn(),
    error: vi.fn(),
    debug: vi.fn(),
    trace: vi.fn(),
    fatal: vi.fn(),
  } as unknown as Logger;
}

function fakeApiClient(overrides: Partial<WorkerApiClient> = {}): {
  readonly client: WorkerApiClient;
  readonly getOrCreateSession: ReturnType<typeof vi.fn>;
  readonly recordMessage: ReturnType<typeof vi.fn>;
} {
  const getOrCreateSession = vi.fn().mockResolvedValue(session);
  const recordMessage = vi.fn().mockResolvedValue(recorded);
  const client: WorkerApiClient = {
    getOrCreateSession: overrides.getOrCreateSession ?? getOrCreateSession,
    recordMessage: overrides.recordMessage ?? recordMessage,
  };
  return { client, getOrCreateSession, recordMessage };
}

function fakeClaude(stdout: string): ClaudeRunner {
  return {
    run: vi.fn().mockResolvedValue({ stdout }),
  };
}

describe('createReplyHandler', () => {
  it('fetches session, runs claude with user content, and records the trimmed reply', async () => {
    const api = fakeApiClient();
    const claude = fakeClaude('  Here is the draft.  \n');
    const handler = createReplyHandler({ apiClient: api.client, claude, logger: silentLogger() });

    await handler(payload);

    expect(api.getOrCreateSession).toHaveBeenCalledWith(payload.threadId);
    expect(claude.run).toHaveBeenCalledWith({
      sessionId: session.claudeSessionId,
      prompt: payload.userContent,
    });
    expect(api.recordMessage).toHaveBeenCalledWith({
      threadId: payload.threadId,
      agentId: payload.agentId,
      content: 'Here is the draft.',
      replyToMessageId: payload.userMessageId,
    });
  });

  it('skips recordMessage when claude stdout is blank after trim', async () => {
    const api = fakeApiClient();
    const claude = fakeClaude('   \n\t  ');
    const handler = createReplyHandler({ apiClient: api.client, claude, logger: silentLogger() });

    await handler(payload);

    expect(api.getOrCreateSession).toHaveBeenCalledOnce();
    expect(api.recordMessage).not.toHaveBeenCalled();
  });

  it('propagates the error when getOrCreateSession fails', async () => {
    const getOrCreateSession = vi.fn().mockRejectedValue(new Error('session boom'));
    const recordMessage = vi.fn();
    const claude: ClaudeRunner = { run: vi.fn() };
    const handler = createReplyHandler({
      apiClient: { getOrCreateSession, recordMessage },
      claude,
      logger: silentLogger(),
    });

    await expect(handler(payload)).rejects.toThrow(/session boom/);
    expect(claude.run).not.toHaveBeenCalled();
    expect(recordMessage).not.toHaveBeenCalled();
  });

  it('propagates the error when claude.run rejects', async () => {
    const api = fakeApiClient();
    const claude: ClaudeRunner = { run: vi.fn().mockRejectedValue(new Error('claude boom')) };
    const handler = createReplyHandler({ apiClient: api.client, claude, logger: silentLogger() });

    await expect(handler(payload)).rejects.toThrow(/claude boom/);
    expect(api.recordMessage).not.toHaveBeenCalled();
  });
});
