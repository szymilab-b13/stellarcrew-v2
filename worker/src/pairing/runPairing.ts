import { collectFingerprint, type Fingerprint } from '../fingerprint/collector.js';
import type { CredentialsStore, WorkerCredentials } from './credentialsStore.js';
import { PairingClientError, type PairingClient } from './pairingClient.js';

export const PAIRING_CODE_PATTERN = /^[A-HJ-NP-Z2-9]{4}-[A-HJ-NP-Z2-9]{4}$/u;

const NAME_MAX_LENGTH = 80;
const HOST_LABEL_MAX_LENGTH = 120;

export interface PairingPrompts {
  readonly askCode: () => Promise<string>;
  readonly askName: () => Promise<string>;
  readonly askHostLabel: () => Promise<string | undefined>;
  readonly confirmOverwrite: (existing: WorkerCredentials) => Promise<boolean>;
  readonly info: (message: string) => void;
  readonly error: (message: string) => void;
}

export interface RunPairingDeps {
  readonly apiBaseUrl: string;
  readonly client: PairingClient;
  readonly store: CredentialsStore;
  readonly prompts: PairingPrompts;
  readonly now: () => Date;
  readonly collectFingerprint?: () => Fingerprint;
}

export async function runPairing(deps: RunPairingDeps): Promise<WorkerCredentials> {
  const { apiBaseUrl, client, store, prompts, now } = deps;
  const collect = deps.collectFingerprint ?? collectFingerprint;

  const existing = await store.load();
  if (existing !== null) {
    const confirmed = await prompts.confirmOverwrite(existing);
    if (!confirmed) {
      throw new Error(`Pairing aborted: existing credentials at ${store.path} were preserved`);
    }
  }

  const code = await promptUntil(
    prompts.askCode,
    (value) => validateCode(value),
    (reason) => prompts.error(reason),
  );
  const name = await promptUntilOptional(
    prompts.askName,
    (value) => validateName(value),
    (reason) => prompts.error(reason),
  );
  const hostLabel = await promptUntilOptional(
    prompts.askHostLabel,
    (value) => validateHostLabel(value),
    (reason) => prompts.error(reason),
  );

  const fingerprint = collect();

  try {
    const result = await client({
      apiBaseUrl,
      code,
      name,
      hostLabel,
      fingerprintHash: fingerprint.hash,
      fingerprintMeta: fingerprint.meta,
      hostInfo: {
        os: fingerprint.meta.osPlatform,
        osRelease: fingerprint.meta.osRelease,
        arch: fingerprint.meta.arch,
        cpuModel: fingerprint.meta.cpuModel,
        cpuCount: fingerprint.meta.cpuCount,
        hostname: fingerprint.meta.hostname,
        kernelVersion: fingerprint.meta.osVersion,
      },
    });
    const credentials: WorkerCredentials =
      result.refreshToken !== undefined
        ? {
            workerId: result.workerId,
            secretToken: result.secretToken,
            rabbitmqUser: result.rabbitmqUser,
            pairedAt: now().toISOString(),
            refreshToken: result.refreshToken,
          }
        : {
            workerId: result.workerId,
            secretToken: result.secretToken,
            rabbitmqUser: result.rabbitmqUser,
            pairedAt: now().toISOString(),
          };
    await store.save(credentials);
    prompts.info(`Worker paired. Credentials saved to ${store.path}`);
    return credentials;
  } catch (error) {
    if (error instanceof PairingClientError) {
      prompts.error(`Pairing API responded with HTTP ${String(error.status)}: ${error.message}`);
    }
    throw error;
  }
}

interface ValidationResult {
  readonly value: string;
  readonly error?: undefined;
}

interface ValidationFailure {
  readonly value?: undefined;
  readonly error: string;
}

type Validation = ValidationResult | ValidationFailure;

async function promptUntil(
  ask: () => Promise<string>,
  validate: (value: string) => Validation,
  reportError: (reason: string) => void,
): Promise<string> {
  for (;;) {
    const raw = await ask();
    const outcome = validate(raw);
    if (outcome.error === undefined) return outcome.value;
    reportError(outcome.error);
  }
}

async function promptUntilOptional(
  ask: () => Promise<string | undefined>,
  validate: (value: string) => Validation,
  reportError: (reason: string) => void,
): Promise<string | undefined> {
  for (;;) {
    const raw = await ask();
    if (raw === undefined) return undefined;
    const trimmed = raw.trim();
    if (trimmed === '') return undefined;
    const outcome = validate(trimmed);
    if (outcome.error === undefined) return outcome.value;
    reportError(outcome.error);
  }
}

function validateCode(raw: string): Validation {
  const normalised = raw.trim().toUpperCase();
  if (!PAIRING_CODE_PATTERN.test(normalised)) {
    return {
      error: 'Pairing code must match XXXX-XXXX using unambiguous letters and digits only.',
    };
  }
  return { value: normalised };
}

function validateName(trimmed: string): Validation {
  if (trimmed.length > NAME_MAX_LENGTH) {
    return { error: `Worker name is too long (max ${String(NAME_MAX_LENGTH)} characters).` };
  }
  return { value: trimmed };
}

function validateHostLabel(trimmed: string): Validation {
  if (trimmed.length > HOST_LABEL_MAX_LENGTH) {
    return {
      error: `Host label is too long (max ${String(HOST_LABEL_MAX_LENGTH)} characters).`,
    };
  }
  return { value: trimmed };
}
