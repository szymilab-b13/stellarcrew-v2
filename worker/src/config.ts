export interface RabbitMqConfig {
  readonly host: string;
  readonly port: number;
  readonly vhost: string;
}

export interface WorkerApiConfig {
  readonly baseUrl: string;
}

export interface WorkerConfig {
  readonly rabbitmq: RabbitMqConfig;
  readonly api: WorkerApiConfig;
  readonly healthcheckPort: number;
  readonly logLevel: string;
  readonly workerName: string;
}

const DEFAULT_RABBITMQ_HOST = 'rabbitmq';
const DEFAULT_RABBITMQ_PORT = 5672;
const DEFAULT_RABBITMQ_VHOST = '/';
const DEFAULT_API_BASE_URL = 'http://localhost:8080';
const DEFAULT_HEALTHCHECK_PORT = 3001;
const DEFAULT_LOG_LEVEL = 'info';
const DEFAULT_WORKER_NAME = 'stellarcrew-worker';

const MAX_PORT = 65535;

export function loadConfig(env: NodeJS.ProcessEnv = process.env): WorkerConfig {
  const rabbitmq: RabbitMqConfig = {
    host: nonEmpty(env.RABBITMQ_HOST, DEFAULT_RABBITMQ_HOST),
    port: parsePort(env.RABBITMQ_PORT, DEFAULT_RABBITMQ_PORT, 'RABBITMQ_PORT'),
    vhost: nonEmpty(env.RABBITMQ_VHOST, DEFAULT_RABBITMQ_VHOST),
  };

  const api: WorkerApiConfig = {
    baseUrl: stripTrailingSlash(nonEmpty(env.API_BASE_URL, DEFAULT_API_BASE_URL)),
  };

  const healthcheckPort = parsePort(
    env.HEALTHCHECK_PORT,
    DEFAULT_HEALTHCHECK_PORT,
    'HEALTHCHECK_PORT',
  );

  return {
    rabbitmq,
    api,
    healthcheckPort,
    logLevel: nonEmpty(env.LOG_LEVEL, DEFAULT_LOG_LEVEL),
    workerName: nonEmpty(env.WORKER_NAME, DEFAULT_WORKER_NAME),
  };
}

function nonEmpty(value: string | undefined, fallback: string): string {
  if (value === undefined || value === '') return fallback;
  return value;
}

function parsePort(raw: string | undefined, fallback: number, envName: string): number {
  if (raw === undefined || raw === '') return fallback;
  const parsed = Number.parseInt(raw, 10);
  if (!Number.isInteger(parsed) || parsed < 0 || parsed > MAX_PORT) {
    throw new Error(`Invalid ${envName} value: ${raw}`);
  }
  return parsed;
}

function stripTrailingSlash(value: string): string {
  return value.endsWith('/') ? value.slice(0, -1) : value;
}
