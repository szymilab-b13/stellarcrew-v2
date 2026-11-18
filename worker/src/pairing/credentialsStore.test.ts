import { mkdir, mkdtemp, readFile, rm, stat, writeFile } from 'node:fs/promises';
import { tmpdir } from 'node:os';
import { dirname, join } from 'node:path';
import { afterEach, beforeEach, describe, expect, it } from 'vitest';

import {
  createCredentialsStore,
  defaultCredentialsPath,
  type WorkerCredentials,
} from './credentialsStore.js';

const sampleCredentials: WorkerCredentials = {
  workerId: '11111111-2222-3333-4444-555555555555',
  secretToken: 'top-secret-token',
  rabbitmqUser: 'worker-111122223333444455555555',
  pairedAt: '2026-11-18T18:30:00.000Z',
};

describe('createCredentialsStore', () => {
  let workdir: string;
  let credentialsPath: string;

  beforeEach(async () => {
    workdir = await mkdtemp(join(tmpdir(), 'stellarcrew-worker-creds-'));
    credentialsPath = join(workdir, 'nested', 'worker.json');
  });

  afterEach(async () => {
    await rm(workdir, { recursive: true, force: true });
  });

  it('should return null when the credentials file does not exist', async () => {
    const store = createCredentialsStore(credentialsPath);

    const loaded = await store.load();

    expect(loaded).toBeNull();
  });

  it('should save credentials atomically, creating missing parent directories', async () => {
    const store = createCredentialsStore(credentialsPath);

    await store.save(sampleCredentials);

    const raw = await readFile(credentialsPath, 'utf8');
    expect(JSON.parse(raw)).toEqual(sampleCredentials);
  });

  it('should round-trip credentials through save and load', async () => {
    const store = createCredentialsStore(credentialsPath);

    await store.save(sampleCredentials);
    const loaded = await store.load();

    expect(loaded).toEqual(sampleCredentials);
  });

  it('should write the credentials file with owner-only (0o600) permissions', async () => {
    const store = createCredentialsStore(credentialsPath);

    await store.save(sampleCredentials);

    const info = await stat(credentialsPath);
    const perms = info.mode & 0o777;
    expect(perms).toBe(0o600);
  });

  it('should reject a corrupted credentials file with a helpful error', async () => {
    const store = createCredentialsStore(credentialsPath);
    await mkdir(dirname(credentialsPath), { recursive: true });
    await writeFile(credentialsPath, 'not-json', { encoding: 'utf8' });

    await expect(store.load()).rejects.toThrow(/credentials file/i);
  });

  it('should reject a credentials file missing required fields', async () => {
    const store = createCredentialsStore(credentialsPath);
    await mkdir(dirname(credentialsPath), { recursive: true });
    await writeFile(credentialsPath, JSON.stringify({ workerId: 'x' }), { encoding: 'utf8' });

    await expect(store.load()).rejects.toThrow(/credentials file/i);
  });
});

describe('defaultCredentialsPath', () => {
  it('should respect WORKER_CREDENTIALS_PATH when set', () => {
    const resolved = defaultCredentialsPath({ WORKER_CREDENTIALS_PATH: '/tmp/x/worker.json' });

    expect(resolved).toBe('/tmp/x/worker.json');
  });

  it('should fall back to $HOME/.stellarcrew/worker.json when HOME is defined', () => {
    const resolved = defaultCredentialsPath({ HOME: '/home/alice' });

    expect(resolved).toBe('/home/alice/.stellarcrew/worker.json');
  });
});
