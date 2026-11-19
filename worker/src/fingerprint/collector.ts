import { createHash } from 'node:crypto';
import nodeFs from 'node:fs';
import nodeOs from 'node:os';

export interface FingerprintMeta {
  readonly machineId: string | null;
  readonly hostname: string;
  readonly primaryMac: string | null;
  readonly cpuModel: string;
  readonly cpuCount: number;
  readonly osPlatform: string;
  readonly osRelease: string;
  readonly osVersion: string;
  readonly arch: string;
}

export interface Fingerprint {
  readonly hash: string;
  readonly meta: FingerprintMeta;
}

interface FsLike {
  readonly readFileSync: (path: string) => Buffer;
  readonly existsSync: (path: string) => boolean;
}

interface OsLike {
  readonly hostname: () => string;
  readonly cpus: () => Array<{ model: string }>;
  readonly platform: () => string;
  readonly release: () => string;
  readonly version: () => string;
  readonly networkInterfaces: () => NodeJS.Dict<nodeOs.NetworkInterfaceInfo[]>;
}

export interface CollectFingerprintDependencies {
  readonly fs?: FsLike;
  readonly os?: OsLike;
  readonly arch?: string;
}

const MACHINE_ID_PATHS = ['/etc/machine-id', '/var/lib/dbus/machine-id'];

export function collectFingerprintMeta(deps: CollectFingerprintDependencies = {}): FingerprintMeta {
  const fs = deps.fs ?? nodeFs;
  const os = deps.os ?? nodeOs;
  const arch = deps.arch ?? process.arch;

  return {
    machineId: readMachineId(fs),
    hostname: os.hostname(),
    primaryMac: findPrimaryMac(os),
    cpuModel: os.cpus()[0]?.model ?? 'unknown',
    cpuCount: os.cpus().length,
    osPlatform: os.platform(),
    osRelease: os.release(),
    osVersion: os.version(),
    arch,
  };
}

export function computeFingerprintHash(meta: FingerprintMeta): string {
  const input = JSON.stringify({
    machineId: meta.machineId,
    primaryMac: meta.primaryMac,
    cpuModel: meta.cpuModel,
    cpuCount: meta.cpuCount,
    osPlatform: meta.osPlatform,
    arch: meta.arch,
  });
  return createHash('sha256').update(input).digest('hex');
}

export function collectFingerprint(deps: CollectFingerprintDependencies = {}): Fingerprint {
  const meta = collectFingerprintMeta(deps);
  return { hash: computeFingerprintHash(meta), meta };
}

function readMachineId(fs: FsLike): string | null {
  for (const path of MACHINE_ID_PATHS) {
    if (!fs.existsSync(path)) continue;
    try {
      const raw = fs.readFileSync(path).toString('utf8').trim();
      if (raw !== '') return raw;
    } catch {
      continue;
    }
  }
  return null;
}

function findPrimaryMac(os: OsLike): string | null {
  const interfaces = os.networkInterfaces();
  for (const name of Object.keys(interfaces)) {
    const entries = interfaces[name];
    if (!entries) continue;
    for (const entry of entries) {
      if (entry.internal) continue;
      if (entry.mac === '00:00:00:00:00:00') continue;
      return entry.mac;
    }
  }
  return null;
}
