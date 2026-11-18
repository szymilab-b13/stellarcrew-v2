import { chmod, mkdir, readFile, rename, writeFile } from 'node:fs/promises';
import { homedir } from 'node:os';
import { dirname, join } from 'node:path';

export interface WorkerCredentials {
  readonly workerId: string;
  readonly secretToken: string;
  readonly rabbitmqUser: string;
  readonly pairedAt: string;
}

export interface CredentialsStore {
  readonly path: string;
  readonly load: () => Promise<WorkerCredentials | null>;
  readonly save: (credentials: WorkerCredentials) => Promise<void>;
}

const FILE_MODE = 0o600;
const DIR_MODE = 0o700;

export function createCredentialsStore(filePath: string): CredentialsStore {
  return {
    path: filePath,
    load: () => loadCredentials(filePath),
    save: (credentials) => saveCredentials(filePath, credentials),
  };
}

export function defaultCredentialsPath(env: NodeJS.ProcessEnv = process.env): string {
  if (typeof env.WORKER_CREDENTIALS_PATH === 'string' && env.WORKER_CREDENTIALS_PATH !== '') {
    return env.WORKER_CREDENTIALS_PATH;
  }
  const home = typeof env.HOME === 'string' && env.HOME !== '' ? env.HOME : homedir();
  return join(home, '.stellarcrew', 'worker.json');
}

async function loadCredentials(filePath: string): Promise<WorkerCredentials | null> {
  let raw: string;
  try {
    raw = await readFile(filePath, 'utf8');
  } catch (error) {
    if (isNodeError(error) && error.code === 'ENOENT') return null;
    throw error;
  }

  let parsed: unknown;
  try {
    parsed = JSON.parse(raw);
  } catch {
    throw new Error(`Worker credentials file at ${filePath} is not valid JSON`);
  }

  if (!isCompleteCredentials(parsed)) {
    throw new Error(
      `Worker credentials file at ${filePath} is missing required fields (workerId, secretToken, rabbitmqUser, pairedAt)`,
    );
  }

  return {
    workerId: parsed.workerId,
    secretToken: parsed.secretToken,
    rabbitmqUser: parsed.rabbitmqUser,
    pairedAt: parsed.pairedAt,
  };
}

async function saveCredentials(filePath: string, credentials: WorkerCredentials): Promise<void> {
  const parent = dirname(filePath);
  await mkdir(parent, { recursive: true, mode: DIR_MODE });

  const tmp = `${filePath}.${process.pid.toString()}.tmp`;
  const body = `${JSON.stringify(credentials, null, 2)}\n`;
  await writeFile(tmp, body, { encoding: 'utf8', mode: FILE_MODE });
  await chmod(tmp, FILE_MODE);
  await rename(tmp, filePath);
  await chmod(filePath, FILE_MODE);
}

function isCompleteCredentials(value: unknown): value is WorkerCredentials {
  if (typeof value !== 'object' || value === null) return false;
  const v = value as Record<string, unknown>;
  return (
    typeof v.workerId === 'string' &&
    typeof v.secretToken === 'string' &&
    typeof v.rabbitmqUser === 'string' &&
    typeof v.pairedAt === 'string'
  );
}

function isNodeError(value: unknown): value is NodeJS.ErrnoException {
  return value instanceof Error && 'code' in value;
}
