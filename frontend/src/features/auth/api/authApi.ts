import { apiFetch } from '@/lib/apiClient';
import { clearAuthToken, writeAuthToken } from '@/lib/authToken';

import {
  mapAuthUser,
  mapLoginResponse,
  type AuthUser,
  type LoginPayload,
  type LoginResponse,
} from '../types';

interface AuthUserPayload {
  email: string;
  display_name: string | null;
  role: string;
}

interface LoginApiPayload {
  token: string;
  user: AuthUserPayload;
}

export async function login(payload: LoginPayload): Promise<LoginResponse> {
  const response = await apiFetch<LoginApiPayload>('/api/auth/login', {
    method: 'POST',
    body: JSON.stringify(payload),
  });
  const mapped = mapLoginResponse(response);
  writeAuthToken(mapped.token);
  return mapped;
}

export async function fetchMe(): Promise<AuthUser> {
  const response = await apiFetch<AuthUserPayload>('/api/auth/me');
  return mapAuthUser(response);
}

export async function logout(): Promise<void> {
  try {
    await apiFetch<unknown>('/api/auth/logout', { method: 'POST' });
  } finally {
    clearAuthToken();
  }
}
