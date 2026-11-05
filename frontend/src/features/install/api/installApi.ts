import { apiFetch } from '@/lib/apiClient';

export interface InstallStatus {
  isInstalled: boolean;
}

export interface InstallPayload {
  email: string;
  password: string;
}

interface InstallStatusResponse {
  is_installed: boolean;
}

export async function fetchInstallStatus(): Promise<InstallStatus> {
  const response = await apiFetch<InstallStatusResponse>('/api/install/status');
  return { isInstalled: response.is_installed };
}

export async function install(payload: InstallPayload): Promise<void> {
  await apiFetch<unknown>('/api/install', {
    method: 'POST',
    body: JSON.stringify(payload),
  });
}
