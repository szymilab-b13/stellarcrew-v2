import type { Channel, ConsumeMessage } from 'amqplib';
import type { Logger } from 'pino';
import { describe, expect, it, vi } from 'vitest';

import { AGENT_REPLIES_EXCHANGE, queueNameFor, startAgentReplyConsumer } from './consumer.js';

const WORKER_ID = '01976b7a-9f12-7a5e-b7e2-5c9d2b8a0f00';

const validPayload = {
  threadId: '01976b7a-1a9d-7e3a-9b7e-27f28cc8d5f0',
  workerId: WORKER_ID,
  agentId: '01976b7a-ab72-7f5e-8a9c-02dc3f4e9c10',
  userMessageId: '01976b7a-ba12-7a6b-9f3b-40ab82c9d100',
  userContent: 'Please draft the release notes.',
  subject: 'Release notes draft',
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

interface ChannelMock {
  readonly channel: Channel;
  readonly assertExchange: ReturnType<typeof vi.fn>;
  readonly assertQueue: ReturnType<typeof vi.fn>;
  readonly bindQueue: ReturnType<typeof vi.fn>;
  readonly consume: ReturnType<typeof vi.fn>;
  readonly ack: ReturnType<typeof vi.fn>;
  readonly nack: ReturnType<typeof vi.fn>;
  readonly cancel: ReturnType<typeof vi.fn>;
  triggerMessage: (message: ConsumeMessage | null) => void;
}

function createChannelMock(): ChannelMock {
  let consumeCallback: ((message: ConsumeMessage | null) => void) | null = null;
  const assertExchange = vi.fn().mockResolvedValue({});
  const assertQueue = vi.fn().mockResolvedValue({});
  const bindQueue = vi.fn().mockResolvedValue({});
  const consume = vi.fn().mockImplementation(async (_queue, callback) => {
    consumeCallback = callback as (message: ConsumeMessage | null) => void;
    return { consumerTag: 'consumer-tag-1' };
  });
  const cancel = vi.fn().mockResolvedValue(undefined);
  const ack = vi.fn();
  const nack = vi.fn();
  const channel = {
    assertExchange,
    assertQueue,
    bindQueue,
    consume,
    cancel,
    ack,
    nack,
  } as unknown as Channel;
  return {
    channel,
    assertExchange,
    assertQueue,
    bindQueue,
    consume,
    ack,
    nack,
    cancel,
    triggerMessage: (message) => consumeCallback?.(message),
  };
}

function messageFor(body: unknown): ConsumeMessage {
  const serialised = typeof body === 'string' ? body : JSON.stringify(body);
  return {
    content: Buffer.from(serialised, 'utf8'),
    fields: { consumerTag: 'c', deliveryTag: 1, redelivered: false, exchange: '', routingKey: '' },
    properties: {
      contentType: 'application/json',
      contentEncoding: '',
      headers: {},
      deliveryMode: 2,
      priority: 0,
      correlationId: '',
      replyTo: '',
      expiration: '',
      messageId: '',
      timestamp: 0,
      type: 'AgentReplyRequested',
      userId: '',
      appId: '',
      clusterId: '',
    },
  } satisfies ConsumeMessage;
}

async function waitForCall(fn: ReturnType<typeof vi.fn>): Promise<void> {
  await vi.waitUntil(() => fn.mock.calls.length > 0, { timeout: 500, interval: 5 });
}

describe('startAgentReplyConsumer', () => {
  it('declares the direct exchange, per-worker queue, and binding using worker id as routing key', async () => {
    const mock = createChannelMock();

    const handle = await startAgentReplyConsumer({
      channel: mock.channel,
      workerId: WORKER_ID,
      logger: silentLogger(),
      onPayload: vi.fn(),
    });

    expect(handle.queueName).toBe(queueNameFor(WORKER_ID));
    expect(mock.assertExchange).toHaveBeenCalledWith(AGENT_REPLIES_EXCHANGE, 'direct', {
      durable: true,
    });
    expect(mock.assertQueue).toHaveBeenCalledWith(handle.queueName, { durable: true });
    expect(mock.bindQueue).toHaveBeenCalledWith(
      handle.queueName,
      AGENT_REPLIES_EXCHANGE,
      WORKER_ID,
    );
    expect(mock.consume).toHaveBeenCalledWith(handle.queueName, expect.any(Function));
    expect(handle.consumerTag).toBe('consumer-tag-1');
  });

  it('acks the message after onPayload resolves successfully', async () => {
    const mock = createChannelMock();
    const onPayload = vi.fn().mockResolvedValue(undefined);

    await startAgentReplyConsumer({
      channel: mock.channel,
      workerId: WORKER_ID,
      logger: silentLogger(),
      onPayload,
    });

    const message = messageFor(validPayload);
    mock.triggerMessage(message);
    await waitForCall(mock.ack);

    expect(onPayload).toHaveBeenCalledWith(validPayload);
    expect(mock.ack).toHaveBeenCalledWith(message);
    expect(mock.nack).not.toHaveBeenCalled();
  });

  it('nacks without requeue when the payload JSON is malformed', async () => {
    const mock = createChannelMock();
    const onPayload = vi.fn();

    await startAgentReplyConsumer({
      channel: mock.channel,
      workerId: WORKER_ID,
      logger: silentLogger(),
      onPayload,
    });

    const message = messageFor('not-json');
    mock.triggerMessage(message);
    await waitForCall(mock.nack);

    expect(mock.nack).toHaveBeenCalledWith(message, false, false);
    expect(onPayload).not.toHaveBeenCalled();
  });

  it('nacks without requeue when the payload fails schema validation', async () => {
    const mock = createChannelMock();
    const onPayload = vi.fn();

    await startAgentReplyConsumer({
      channel: mock.channel,
      workerId: WORKER_ID,
      logger: silentLogger(),
      onPayload,
    });

    const message = messageFor({ ...validPayload, threadId: 'not-a-uuid' });
    mock.triggerMessage(message);
    await waitForCall(mock.nack);

    expect(mock.nack).toHaveBeenCalledWith(message, false, false);
    expect(onPayload).not.toHaveBeenCalled();
  });

  it('nacks with requeue when onPayload rejects', async () => {
    const mock = createChannelMock();
    const onPayload = vi.fn().mockRejectedValue(new Error('downstream failure'));

    await startAgentReplyConsumer({
      channel: mock.channel,
      workerId: WORKER_ID,
      logger: silentLogger(),
      onPayload,
    });

    const message = messageFor(validPayload);
    mock.triggerMessage(message);
    await waitForCall(mock.nack);

    expect(mock.nack).toHaveBeenCalledWith(message, false, true);
    expect(mock.ack).not.toHaveBeenCalled();
  });

  it('ignores null delivery (broker cancellation) without touching ack/nack', async () => {
    const mock = createChannelMock();
    const onPayload = vi.fn();

    await startAgentReplyConsumer({
      channel: mock.channel,
      workerId: WORKER_ID,
      logger: silentLogger(),
      onPayload,
    });

    mock.triggerMessage(null);

    expect(onPayload).not.toHaveBeenCalled();
    expect(mock.ack).not.toHaveBeenCalled();
    expect(mock.nack).not.toHaveBeenCalled();
  });

  it('cancels the consumer tag through handle.cancel', async () => {
    const mock = createChannelMock();

    const handle = await startAgentReplyConsumer({
      channel: mock.channel,
      workerId: WORKER_ID,
      logger: silentLogger(),
      onPayload: vi.fn(),
    });

    await handle.cancel();

    expect(mock.cancel).toHaveBeenCalledWith('consumer-tag-1');
  });
});
