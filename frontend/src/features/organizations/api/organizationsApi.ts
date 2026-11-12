import { ApiError, apiFetch } from '@/lib/apiClient';

import type { Organization } from '../types';

export async function fetchMyOrganization(): Promise<Organization | null> {
  try {
    return await apiFetch<Organization>('/api/my/organization');
  } catch (error) {
    if (error instanceof ApiError && error.status === 404) {
      return null;
    }
    throw error;
  }
}
