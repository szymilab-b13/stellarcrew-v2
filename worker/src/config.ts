export interface WorkerConfig {
  readonly rabbitMqUrl: string;
  readonly healthcheckPort: number;
  readonly logLevel: string;
  readonly workerName: string;
}

const DEFAULT_RABBITMQ_URL = 'amqp://guest:guest@rabbitmq:5672/';
const DEFAULT_HEALTHCHECK_PORT = 3001;
const DEFAULT_LOG_LEVEL = 'info';
const DEFAULT_WORKER_NAME = 'stellarcrew-worker';

const MAX_PORT = 65535;

export function loadConfig(env: NodeJS.ProcessEnv = process.env): WorkerConfig {
  const rabbitMqUrl = env.RABBITMQ_URL ?? DEFAULT_RABBITMQ_URL;

  const portRaw = env.HEALTHCHECK_PORT;
  const parsedPort =
    portRaw === undefined ? DEFAULT_HEALTHCHECK_PORT : Number.parseInt(portRaw, 10);
  if (!Number.isInteger(parsedPort) || parsedPort < 0 || parsedPort > MAX_PORT) {
    throw new Error(`Invalid HEALTHCHECK_PORT value: ${String(portRaw)}`);
  }

  const logLevel = env.LOG_LEVEL ?? DEFAULT_LOG_LEVEL;
  const workerName = env.WORKER_NAME ?? DEFAULT_WORKER_NAME;

  return {
    rabbitMqUrl,
    healthcheckPort: parsedPort,
    logLevel,
    workerName,
  };
}
