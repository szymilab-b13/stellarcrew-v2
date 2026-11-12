import { apiFetch } from '@/lib/apiClient';

export interface InstallStatus {
  isInstalled: boolean;
}

export interface InstallPayload {
  email: string;
  password: string;
  displayName?: string;
  instanceName?: string;
  locale?: string;
  workspaceName?: string;
  selectedAgentSlugs?: string[];
  logo?: File | null;
}

interface InstallStatusResponse {
  is_installed: boolean;
}

export async function fetchInstallStatus(): Promise<InstallStatus> {
  const response = await apiFetch<InstallStatusResponse>('/api/install/status');
  return { isInstalled: response.is_installed };
}

export async function install(payload: InstallPayload): Promise<void> {
  const formData = new FormData();
  formData.append('email', payload.email);
  formData.append('password', payload.password);
  if (payload.displayName) formData.append('displayName', payload.displayName);
  if (payload.instanceName) formData.append('instanceName', payload.instanceName);
  if (payload.locale) formData.append('locale', payload.locale);
  if (payload.workspaceName) formData.append('workspaceName', payload.workspaceName);
  if (payload.selectedAgentSlugs) {
    for (const slug of payload.selectedAgentSlugs) {
      formData.append('agents[]', slug);
    }
  }
  if (payload.logo) formData.append('logo', payload.logo);

  await apiFetch<unknown>('/api/install', {
    method: 'POST',
    body: formData,
  });
}
