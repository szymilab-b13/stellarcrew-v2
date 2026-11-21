import { apiFetch } from '@/lib/apiClient';

import type { ListWorkersResponse, PairingCodeResponse, WorkerDetail } from '../types';

export interface CreatePairingCodeInput {
  name?: string;
  hostLabel?: string;
}

export async function fetchWorkers(): Promise<ListWorkersResponse> {
  return apiFetch<ListWorkersResponse>('/api/workers');
}

export async function fetchWorker(id: string): Promise<WorkerDetail> {
  return apiFetch<WorkerDetail>(`/api/workers/${id}`);
}

export async function createPairingCode(_input: CreatePairingCodeInput = {}): Promise<PairingCodeResponse> {
  return apiFetch<PairingCodeResponse>('/api/workers/pairing-codes', {
    method: 'POST',
    body: JSON.stringify({}),
  });
}

export async function cancelPairingCode(code: string): Promise<void> {
  await apiFetch<void>(`/api/workers/pairing-codes/${code}`, {
    method: 'DELETE',
  });
}

export async function revokeWorker(id: string): Promise<void> {
  await apiFetch<void>(`/api/workers/${id}`, {
    method: 'DELETE',
  });
}

export async function acceptFingerprint(
  id: string,
  payload: { fingerprintHash: string; fingerprintMeta?: Record<string, unknown> | null },
): Promise<void> {
  await apiFetch<void>(`/api/workers/${id}/fingerprint/accept`, {
    method: 'POST',
    body: JSON.stringify(payload),
  });
}
