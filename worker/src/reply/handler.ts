import type { Logger } from 'pino';

import type { AgentReplyPayload } from '../amqp/payload.js';
import type { ClaudeRunner } from './claudeRunner.js';
import type { WorkerApiClient } from './workerApiClient.js';

export interface ReplyHandlerOptions {
  readonly apiClient: WorkerApiClient;
  readonly claude: ClaudeRunner;
  readonly logger: Logger;
}

export function createReplyHandler({
  apiClient,
  claude,
  logger,
}: ReplyHandlerOptions): (payload: AgentReplyPayload) => Promise<void> {
  return async (payload) => {
    logger.info(
      {
        threadId: payload.threadId,
        agentId: payload.agentId,
        userMessageId: payload.userMessageId,
        subject: payload.subject,
      },
      'Processing AgentReplyRequested',
    );

    const session = await apiClient.getOrCreateSession(payload.threadId);

    const { stdout } = await claude.run({
      sessionId: session.claudeSessionId,
      prompt: payload.userContent,
    });

    const content = stdout.trim();
    if (content === '') {
      logger.warn(
        { threadId: payload.threadId, claudeSessionId: session.claudeSessionId },
        'Claude subprocess returned empty stdout, skipping record',
      );
      return;
    }

    const recorded = await apiClient.recordMessage({
      threadId: payload.threadId,
      agentId: payload.agentId,
      content,
      replyToMessageId: payload.userMessageId,
    });

    logger.info(
      {
        threadId: payload.threadId,
        messageId: recorded.messageId,
        claudeSessionId: session.claudeSessionId,
      },
      'Agent reply recorded',
    );
  };
}
