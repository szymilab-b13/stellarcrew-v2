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
  const response = await fetch(url, {
    ...init,
    headers: {
      Accept: 'application/json',
      ...(body && !isFormData ? { 'Content-Type': 'application/json' } : {}),
      ...(init.headers ?? {}),
    },
  });

  const contentType = response.headers.get('content-type') ?? '';
  const payload = contentType.includes('application/json') ? await response.json() : null;

  if (!response.ok) {
    throw new ApiError(response.status, payload);
  }

  return payload as TResponse;
}
