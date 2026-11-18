import 'dotenv-flow/config';

import { loadConfig } from './config.js';
import { startHealthcheckServer } from './healthcheck.js';
import { createLogger } from './logger.js';

function maskSecrets(url: string): string {
  return url.replace(/:[^@/]+@/u, ':***@');
}

async function main(): Promise<void> {
  const config = loadConfig();
  const logger = createLogger({ level: config.logLevel, workerName: config.workerName });

  logger.info({ rabbitMqUrl: maskSecrets(config.rabbitMqUrl) }, 'Worker starting');

  const healthcheck = await startHealthcheckServer({
    port: config.healthcheckPort,
    logger,
  });

  const shutdown = async (signal: NodeJS.Signals): Promise<void> => {
    logger.info({ signal }, 'Shutting down worker');
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

main().catch((error: unknown) => {
  console.error('Worker startup failed:', error);
  process.exit(1);
});
