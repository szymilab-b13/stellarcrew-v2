import type { Channel, ConsumeMessage } from 'amqplib';
import type { Logger } from 'pino';

import { parseAgentReplyPayload, type AgentReplyPayload } from './payload.js';

export const AGENT_REPLIES_EXCHANGE = 'chat.agent_replies';

export interface AgentReplyConsumerHandle {
  readonly queueName: string;
  readonly consumerTag: string;
  readonly cancel: () => Promise<void>;
}

export interface AgentReplyConsumerOptions {
  readonly channel: Channel;
  readonly workerId: string;
  readonly logger: Logger;
  readonly onPayload: (payload: AgentReplyPayload) => Promise<void>;
  readonly exchangeName?: string;
}

export async function startAgentReplyConsumer({
  channel,
  workerId,
  logger,
  onPayload,
  exchangeName = AGENT_REPLIES_EXCHANGE,
}: AgentReplyConsumerOptions): Promise<AgentReplyConsumerHandle> {
  const queueName = queueNameFor(workerId);

  await channel.assertExchange(exchangeName, 'direct', { durable: true });
  await channel.assertQueue(queueName, { durable: true });
  await channel.bindQueue(queueName, exchangeName, workerId);

  const { consumerTag } = await channel.consume(queueName, (message) => {
    void handleMessage({ channel, logger, onPayload, message });
  });

  logger.info({ queueName, exchangeName, consumerTag }, 'AMQP agent-reply consumer ready');

  return {
    queueName,
    consumerTag,
    cancel: async () => {
      await channel.cancel(consumerTag);
    },
  };
}

export function queueNameFor(workerId: string): string {
  return `chat.worker.${workerId}.inbox`;
}

async function handleMessage({
  channel,
  logger,
  onPayload,
  message,
}: {
  readonly channel: Channel;
  readonly logger: Logger;
  readonly onPayload: (payload: AgentReplyPayload) => Promise<void>;
  readonly message: ConsumeMessage | null;
}): Promise<void> {
  if (message === null) {
    logger.warn('AMQP consumer received null message, broker cancelled the consumer');
    return;
  }

  let payload: AgentReplyPayload;
  try {
    const raw: unknown = JSON.parse(message.content.toString('utf8'));
    payload = parseAgentReplyPayload(raw);
  } catch (error) {
    logger.error({ err: error }, 'Failed to parse AgentReplyRequested payload, dropping message');
    channel.nack(message, false, false);
    return;
  }

  try {
    await onPayload(payload);
    channel.ack(message);
  } catch (error) {
    logger.error(
      { err: error, threadId: payload.threadId, workerId: payload.workerId },
      'Reply handler failed, requeuing message',
    );
    channel.nack(message, false, true);
  }
}
