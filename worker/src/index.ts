import 'dotenv-flow/config';

import type { AgentReplyPayload } from './amqp/payload.js';
import { startAgentReplyConsumer } from './amqp/consumer.js';
import { buildAmqpUrl, connectAmqp, type AmqpConnection } from './amqp/connection.js';
import { loadConfig } from './config.js';
import { startHealthcheckServer } from './healthcheck.js';
import { createLogger } from './logger.js';
import { createCredentialsStore, defaultCredentialsPath } from './pairing/credentialsStore.js';

async function main(): Promise<void> {
  const config = loadConfig();
  const logger = createLogger({ level: config.logLevel, workerName: config.workerName });

  logger.info(
    {
      rabbitmqHost: config.rabbitmq.host,
      rabbitmqPort: config.rabbitmq.port,
      rabbitmqVhost: config.rabbitmq.vhost,
      apiBaseUrl: config.api.baseUrl,
    },
    'Worker starting',
  );

  const credentialsStore = createCredentialsStore(defaultCredentialsPath());
  const credentials = await credentialsStore.load();
  if (credentials === null) {
    logger.error(
      { credentialsPath: credentialsStore.path },
      'Worker credentials missing. Run `pnpm pair` to pair this worker before starting.',
    );
    process.exit(2);
  }

  const healthcheck = await startHealthcheckServer({
    port: config.healthcheckPort,
    logger,
  });

  const amqp = await connectAmqp({
    url: buildAmqpUrl(config.rabbitmq, credentials),
    logger,
  });

  const consumer = await startAgentReplyConsumer({
    channel: amqp.channel,
    workerId: credentials.workerId,
    logger,
    onPayload: async (payload: AgentReplyPayload) => {
      logger.info(
        {
          threadId: payload.threadId,
          agentId: payload.agentId,
          userMessageId: payload.userMessageId,
          subject: payload.subject,
        },
        'AgentReplyRequested received (handler wiring pending BL-CAR-008b)',
      );
    },
  });

  const shutdown = async (signal: NodeJS.Signals): Promise<void> => {
    logger.info({ signal }, 'Shutting down worker');
    await safeCancel(consumer, logger);
    await safeClose(amqp, logger);
    await healthcheck.stop();
    process.exit(0);
  };

  process.on('SIGTERM', (signal) => {
    void shutdown(signal);
  });
  process.on('SIGINT', (signal) => {
    void shutdown(signal);
  });
}

async function safeCancel(
  consumer: Awaited<ReturnType<typeof startAgentReplyConsumer>>,
  logger: ReturnType<typeof createLogger>,
): Promise<void> {
  try {
    await consumer.cancel();
  } catch (error) {
    logger.warn({ err: error }, 'Failed to cancel AMQP consumer cleanly');
  }
}

async function safeClose(
  amqp: AmqpConnection,
  logger: ReturnType<typeof createLogger>,
): Promise<void> {
  try {
    await amqp.close();
  } catch (error) {
    logger.warn({ err: error }, 'Failed to close AMQP connection cleanly');
  }
}

main().catch((error: unknown) => {
  console.error('Worker startup failed:', error);
  process.exit(1);
});
