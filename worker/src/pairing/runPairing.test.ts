import { describe, expect, it } from 'vitest';

import type { Fingerprint } from '../fingerprint/collector.js';
import type { CredentialsStore, WorkerCredentials } from './credentialsStore.js';
import type { PairWorkerInput, PairWorkerResult, PairingClient } from './pairingClient.js';
import { PairingClientError } from './pairingClient.js';
import type { PairingPrompts } from './runPairing.js';
import { PAIRING_CODE_PATTERN, runPairing } from './runPairing.js';

const sampleApiResult: PairWorkerResult = {
  workerId: '11111111-2222-3333-4444-555555555555',
  secretToken: 's3cr3t',
  rabbitmqUser: 'worker-111122223333444455555555',
};

const sampleFingerprint: Fingerprint = {
  hash: 'f'.repeat(64),
  meta: {
    machineId: 'machine-abc',
    hostname: 'test-host',
    primaryMac: 'aa:bb:cc:dd:ee:ff',
    cpuModel: 'Test CPU',
    cpuCount: 4,
    osPlatform: 'linux',
    osRelease: '6.6.0',
    osVersion: '#1 SMP',
    arch: 'x64',
  },
};

const fakeCollectFingerprint = (): Fingerprint => sampleFingerprint;

function createRecordingStore(): {
  readonly store: CredentialsStore;
  readonly saved: WorkerCredentials[];
} {
  const saved: WorkerCredentials[] = [];
  const store: CredentialsStore = {
    path: '/fake/.stellarcrew/worker.json',
    load: async () => null,
    save: async (credentials) => {
      saved.push(credentials);
    },
  };
  return { store, saved };
}

function createScriptedPrompts(answers: {
  code: string[];
  name: string[];
  hostLabel: (string | undefined)[];
  confirm?: boolean[];
}): {
  readonly prompts: PairingPrompts;
  readonly infos: string[];
  readonly errors: string[];
} {
  const infos: string[] = [];
  const errors: string[] = [];
  const codeQueue = [...answers.code];
  const nameQueue = [...answers.name];
  const hostLabelQueue = [...answers.hostLabel];
  const confirmQueue = [...(answers.confirm ?? [true])];
  const prompts: PairingPrompts = {
    askCode: async () => {
      const next = codeQueue.shift();
      if (next === undefined) throw new Error('No more code answers scripted');
      return next;
    },
    askName: async () => {
      const next = nameQueue.shift();
      if (next === undefined) throw new Error('No more name answers scripted');
      return next;
    },
    askHostLabel: async () => {
      if (hostLabelQueue.length === 0) throw new Error('No more hostLabel answers scripted');
      return hostLabelQueue.shift();
    },
    confirmOverwrite: async () => {
      const next = confirmQueue.shift();
      if (next === undefined) throw new Error('No more confirm answers scripted');
      return next;
    },
    info: (msg) => {
      infos.push(msg);
    },
    error: (msg) => {
      errors.push(msg);
    },
  };
  return { prompts, infos, errors };
}

function captureClient(result: PairWorkerResult): {
  readonly client: PairingClient;
  readonly calls: PairWorkerInput[];
} {
  const calls: PairWorkerInput[] = [];
  const client: PairingClient = async (input) => {
    calls.push(input);
    return result;
  };
  return { client, calls };
}

describe('runPairing', () => {
  it('should call the pairing client with the prompted inputs and persist credentials', async () => {
    const { store, saved } = createRecordingStore();
    const { client, calls } = captureClient(sampleApiResult);
    const { prompts } = createScriptedPrompts({
      code: ['ABCD-2345'],
      name: ['MacBook Pro'],
      hostLabel: ['macbook.local'],
    });

    const result = await runPairing({
      apiBaseUrl: 'http://localhost:8080',
      client,
      store,
      prompts,
      now: () => new Date('2026-11-18T18:30:00.000Z'),
      collectFingerprint: fakeCollectFingerprint,
    });

    expect(calls).toEqual([
      {
        apiBaseUrl: 'http://localhost:8080',
        code: 'ABCD-2345',
        name: 'MacBook Pro',
        hostLabel: 'macbook.local',
        fingerprintHash: sampleFingerprint.hash,
        fingerprintMeta: sampleFingerprint.meta,
      },
    ]);
    expect(saved).toEqual([
      {
        workerId: sampleApiResult.workerId,
        secretToken: sampleApiResult.secretToken,
        rabbitmqUser: sampleApiResult.rabbitmqUser,
        pairedAt: '2026-11-18T18:30:00.000Z',
      },
    ]);
    expect(result).toEqual(saved[0]);
  });

  it('should treat an empty hostLabel as undefined when calling the client', async () => {
    const { store } = createRecordingStore();
    const { client, calls } = captureClient(sampleApiResult);
    const { prompts } = createScriptedPrompts({
      code: ['ABCD-2345'],
      name: ['laptop'],
      hostLabel: [undefined],
    });

    await runPairing({
      apiBaseUrl: 'http://localhost:8080',
      client,
      store,
      prompts,
      now: () => new Date('2026-11-18T18:30:00.000Z'),
      collectFingerprint: fakeCollectFingerprint,
    });

    expect(calls[0].hostLabel).toBeUndefined();
  });

  it('should re-prompt when the pairing code fails the local regex', async () => {
    const { store } = createRecordingStore();
    const { client, calls } = captureClient(sampleApiResult);
    const { prompts, errors } = createScriptedPrompts({
      code: ['bad', 'ABCD-2345'],
      name: ['laptop'],
      hostLabel: [undefined],
    });

    await runPairing({
      apiBaseUrl: 'http://localhost:8080',
      client,
      store,
      prompts,
      now: () => new Date(),
      collectFingerprint: fakeCollectFingerprint,
    });

    expect(calls).toHaveLength(1);
    expect(calls[0].code).toBe('ABCD-2345');
    expect(errors.some((msg) => msg.includes('XXXX-XXXX'))).toBe(true);
  });

  it('should let the worker name stay blank so the backend assigns a default', async () => {
    const { store } = createRecordingStore();
    const { client, calls } = captureClient(sampleApiResult);
    const { prompts } = createScriptedPrompts({
      code: ['ABCD-2345'],
      name: ['   '],
      hostLabel: [undefined],
    });

    await runPairing({
      apiBaseUrl: 'http://localhost:8080',
      client,
      store,
      prompts,
      now: () => new Date(),
      collectFingerprint: fakeCollectFingerprint,
    });

    expect(calls[0].name).toBeUndefined();
  });

  it('should ask for confirmation before overwriting an existing credentials file', async () => {
    const saved: WorkerCredentials[] = [];
    const store: CredentialsStore = {
      path: '/fake/.stellarcrew/worker.json',
      load: async () => ({
        workerId: 'old',
        secretToken: 'old',
        rabbitmqUser: 'old',
        pairedAt: '2026-01-01T00:00:00.000Z',
      }),
      save: async (credentials) => {
        saved.push(credentials);
      },
    };
    const { client } = captureClient(sampleApiResult);
    const { prompts } = createScriptedPrompts({
      code: ['ABCD-2345'],
      name: ['laptop'],
      hostLabel: [undefined],
      confirm: [false],
    });

    await expect(
      runPairing({
        apiBaseUrl: 'http://localhost:8080',
        client,
        store,
        prompts,
        now: () => new Date(),
        collectFingerprint: fakeCollectFingerprint,
      }),
    ).rejects.toThrow(/aborted/i);
    expect(saved).toEqual([]);
  });

  it('should attach the collected fingerprint to the pairing client call', async () => {
    const { store } = createRecordingStore();
    const { client, calls } = captureClient(sampleApiResult);
    const { prompts } = createScriptedPrompts({
      code: ['ABCD-2345'],
      name: ['laptop'],
      hostLabel: [undefined],
    });
    const customFingerprint: Fingerprint = {
      hash: 'a'.repeat(64),
      meta: {
        machineId: 'custom-machine',
        hostname: 'custom-host',
        primaryMac: '11:22:33:44:55:66',
        cpuModel: 'Custom CPU',
        cpuCount: 8,
        osPlatform: 'darwin',
        osRelease: '24.1.0',
        osVersion: 'Darwin',
        arch: 'arm64',
      },
    };

    await runPairing({
      apiBaseUrl: 'http://localhost:8080',
      client,
      store,
      prompts,
      now: () => new Date('2026-11-19T10:00:00.000Z'),
      collectFingerprint: () => customFingerprint,
    });

    expect(calls[0].fingerprintHash).toBe(customFingerprint.hash);
    expect(calls[0].fingerprintMeta).toBe(customFingerprint.meta);
  });

  it('should surface a PairingClientError from the client with a friendly message', async () => {
    const { store } = createRecordingStore();
    const failingClient: PairingClient = async () => {
      throw new PairingClientError(404, '', 'Pairing failed with HTTP 404');
    };
    const { prompts, errors } = createScriptedPrompts({
      code: ['ABCD-2345'],
      name: ['laptop'],
      hostLabel: [undefined],
    });

    await expect(
      runPairing({
        apiBaseUrl: 'http://localhost:8080',
        client: failingClient,
        store,
        prompts,
        now: () => new Date(),
        collectFingerprint: fakeCollectFingerprint,
      }),
    ).rejects.toBeInstanceOf(PairingClientError);
    expect(errors.some((msg) => msg.includes('404'))).toBe(true);
  });
});

describe('PAIRING_CODE_PATTERN', () => {
  it('should accept the unambiguous XXXX-XXXX format', () => {
    expect(PAIRING_CODE_PATTERN.test('ABCD-2345')).toBe(true);
  });

  it('should reject letters banned by the backend (I, O)', () => {
    expect(PAIRING_CODE_PATTERN.test('ABCO-1234')).toBe(false);
  });
});
