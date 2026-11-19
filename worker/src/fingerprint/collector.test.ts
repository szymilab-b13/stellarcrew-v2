import { createHash } from 'node:crypto';
import { describe, expect, it } from 'vitest';

import { collectFingerprintMeta, computeFingerprintHash } from './collector.js';

interface FakeFsOptions {
  readonly files: Record<string, string>;
}

function makeFs({ files }: FakeFsOptions) {
  return {
    existsSync: (path: string): boolean => Object.hasOwn(files, path),
    readFileSync: (path: string): Buffer => Buffer.from(files[path] ?? '', 'utf8'),
  };
}

function makeOs(overrides: {
  readonly hostname?: string;
  readonly cpuModel?: string;
  readonly cpuCount?: number;
  readonly platform?: string;
  readonly release?: string;
  readonly version?: string;
  readonly interfaces?: Record<string, Array<{ mac: string; internal: boolean }>>;
}) {
  return {
    hostname: (): string => overrides.hostname ?? 'test-host',
    cpus: (): Array<{ model: string }> =>
      Array.from({ length: overrides.cpuCount ?? 4 }, () => ({
        model: overrides.cpuModel ?? 'Test CPU',
      })),
    platform: (): string => overrides.platform ?? 'linux',
    release: (): string => overrides.release ?? '6.6.0',
    version: (): string => overrides.version ?? '#1 SMP',
    networkInterfaces: () =>
      (overrides.interfaces ?? {
        eth0: [{ mac: 'aa:bb:cc:dd:ee:ff', internal: false }],
      }) as unknown as ReturnType<typeof import('node:os').networkInterfaces>,
  };
}

describe('computeFingerprintHash', () => {
  it('should return a stable sha256 for the same subset of fields', () => {
    const meta = {
      machineId: 'machine-123',
      hostname: 'host-a',
      primaryMac: 'aa:bb:cc:dd:ee:ff',
      cpuModel: 'Apple M2 Pro',
      cpuCount: 10,
      osPlatform: 'darwin',
      osRelease: '24.1.0',
      osVersion: 'Darwin Kernel',
      arch: 'arm64',
    };

    const hash = computeFingerprintHash(meta);
    const expected = createHash('sha256')
      .update(
        JSON.stringify({
          machineId: 'machine-123',
          primaryMac: 'aa:bb:cc:dd:ee:ff',
          cpuModel: 'Apple M2 Pro',
          cpuCount: 10,
          osPlatform: 'darwin',
          arch: 'arm64',
        }),
      )
      .digest('hex');

    expect(hash).toBe(expected);
    expect(hash).toMatch(/^[a-f0-9]{64}$/);
  });

  it('should produce the same hash when non-stable fields differ', () => {
    const base = {
      machineId: 'machine-123',
      hostname: 'host-a',
      primaryMac: 'aa:bb:cc:dd:ee:ff',
      cpuModel: 'Apple M2',
      cpuCount: 8,
      osPlatform: 'linux',
      osRelease: '6.6.0',
      osVersion: '#1 SMP',
      arch: 'x64',
    };
    const other = { ...base, hostname: 'different-host', osRelease: '6.7.0', osVersion: '#2 SMP' };

    expect(computeFingerprintHash(base)).toBe(computeFingerprintHash(other));
  });
});

describe('collectFingerprintMeta', () => {
  it('should read machine id from /etc/machine-id when present', () => {
    const fs = makeFs({ files: { '/etc/machine-id': 'abc-machine-id\n' } });
    const os = makeOs({});

    const meta = collectFingerprintMeta({ fs, os, arch: 'x64' });

    expect(meta.machineId).toBe('abc-machine-id');
  });

  it('should fall back to null machine id when no file present', () => {
    const fs = makeFs({ files: {} });
    const os = makeOs({});

    const meta = collectFingerprintMeta({ fs, os, arch: 'x64' });

    expect(meta.machineId).toBeNull();
  });

  it('should pick first non-internal MAC address', () => {
    const fs = makeFs({ files: {} });
    const os = makeOs({
      interfaces: {
        lo: [{ mac: '00:00:00:00:00:00', internal: true }],
        eth0: [{ mac: '11:22:33:44:55:66', internal: false }],
        wlan0: [{ mac: 'aa:bb:cc:dd:ee:ff', internal: false }],
      },
    });

    const meta = collectFingerprintMeta({ fs, os, arch: 'arm64' });

    expect(meta.primaryMac).toBe('11:22:33:44:55:66');
  });
});
