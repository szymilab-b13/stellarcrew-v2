import { apiFetch } from '@/lib/apiClient';

import { parseMenu, type MenuSection } from '../types';

interface MenuApiPayload {
  sections: MenuSection[];
}

export async function fetchMenu(): Promise<MenuSection[]> {
  const payload = await apiFetch<MenuApiPayload>('/api/menu');
  return parseMenu(payload);
}
