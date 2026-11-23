export type FingerprintStatus = 'ok' | 'warning' | 'missing';

export interface HostInfo {
  os?: string;
  osRelease?: string;
  arch?: string;
  cpuModel?: string;
  cpuCount?: number;
  memoryBytes?: number;
  hostname?: string;
  kernelVersion?: string;
  [key: string]: unknown;
}

export interface Worker {
  id: string;
  name: string;
  hostLabel: string | null;
  rabbitmqUser: string;
  lastHeartbeatAt: string | null;
  isOnline: boolean;
  createdAt: string;
  updatedAt: string;
  fingerprintHashShort: string | null;
  fingerprintStatus: FingerprintStatus;
}

export interface FingerprintChangeEntry {
  at: string;
  status: 'detected' | 'accepted';
  previousHash: string | null;
  previousMeta: Record<string, unknown> | null;
  newHash: string | null;
  newMeta: Record<string, unknown> | null;
}

export interface WorkerDetail extends Worker {
  fingerprintHash: string | null;
  fingerprintMeta: Record<string, unknown> | null;
  fingerprintChangeHistory: FingerprintChangeEntry[];
  hostInfo: HostInfo | null;
}

export interface ListWorkersResponse {
  workers: Worker[];
}

export interface PairingCodeResponse {
  code: string;
  expiresAt: string;
  pairCommand: string;
}
