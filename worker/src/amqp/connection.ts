import { connect, type Channel, type ChannelModel } from 'amqplib';
import type { Logger } from 'pino';

import type { RabbitMqConfig } from '../config.js';
import type { WorkerCredentials } from '../pairing/credentialsStore.js';

export interface AmqpConnection {
  readonly connection: ChannelModel;
  readonly channel: Channel;
  readonly close: () => Promise<void>;
}

export function buildAmqpUrl(rabbitmq: RabbitMqConfig, credentials: WorkerCredentials): string {
  const user = encodeURIComponent(credentials.rabbitmqUser);
  const pass = encodeURIComponent(credentials.secretToken);
  const vhost = encodeURIComponent(rabbitmq.vhost);
  return `amqp://${user}:${pass}@${rabbitmq.host}:${rabbitmq.port.toString()}/${vhost}`;
}

export function maskAmqpUrl(url: string): string {
  return url.replace(/:[^@/]+@/u, ':***@');
}

export async function connectAmqp({
  url,
  logger,
}: {
  readonly url: string;
  readonly logger: Logger;
}): Promise<AmqpConnection> {
  logger.info({ amqpUrl: maskAmqpUrl(url) }, 'Connecting to RabbitMQ');
  const connection = await connect(url);
  const channel = await connection.createChannel();
  const close = async (): Promise<void> => {
    try {
      await channel.close();
    } catch (error) {
      logger.warn({ err: error }, 'Failed to close AMQP channel cleanly');
    }
    try {
      await connection.close();
    } catch (error) {
      logger.warn({ err: error }, 'Failed to close AMQP connection cleanly');
    }
  };
  return { connection, channel, close };
}
