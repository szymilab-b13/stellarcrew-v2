import { clearAuthToken, readAuthToken } from './authToken';

export const API_BASE_URL = (import.meta.env.VITE_API_URL ?? '') as string;

export class ApiError extends Error {
  public readonly status: number;
  public readonly body: unknown;

  public constructor(status: number, body: unknown, message?: string) {
    super(message ?? `Request failed with status ${status}`);
    this.status = status;
    this.body = body;
  }
}

export async function apiFetch<TResponse>(
  path: string,
  init: RequestInit = {},
): Promise<TResponse> {
  const url = `${API_BASE_URL}${path}`;
  const body = init.body;
  const isFormData = typeof FormData !== 'undefined' && body instanceof FormData;

  const headers: Record<string, string> = { Accept: 'application/json' };
  if (body && !isFormData) headers['Content-Type'] = 'application/json';

  const token = readAuthToken();
  if (token) headers.Authorization = `Bearer ${token}`;

  const response = await fetch(url, {
    ...init,
    headers: { ...headers, ...(init.headers as Record<string, string> | undefined) },
  });

  const contentType = response.headers.get('content-type') ?? '';
  const payload = contentType.includes('application/json') ? await response.json() : null;

  if (response.status === 401) {
    clearAuthToken();
  }

  if (!response.ok) {
    throw new ApiError(response.status, payload);
  }

  return payload as TResponse;
}
