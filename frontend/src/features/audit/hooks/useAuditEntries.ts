import { useQuery } from '@tanstack/react-query';

import { fetchAuditEntries } from '../api/audit.api';
import type { AuditFilters, AuditPage } from '../types';

export const auditEntriesQueryKey = ['audit', 'entries'] as const;

function auditListKey(filters: AuditFilters) {
  return [...auditEntriesQueryKey, filters] as const;
}

export function useAuditEntries(filters: AuditFilters = {}) {
  return useQuery<AuditPage>({
    queryKey: auditListKey(filters),
    queryFn: () => fetchAuditEntries(filters),
    staleTime: 15_000,
  });
}
