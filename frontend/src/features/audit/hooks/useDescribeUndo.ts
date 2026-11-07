import { useQuery } from '@tanstack/react-query';

import { describeAuditUndo } from '../api/audit.api';
import type { UndoDescription } from '../types';

export const describeUndoQueryKey = (id: string | null) => ['audit', 'describe-undo', id] as const;

export function useDescribeUndo(id: string | null) {
  return useQuery<UndoDescription>({
    queryKey: describeUndoQueryKey(id),
    queryFn: () => describeAuditUndo(id as string),
    enabled: id !== null,
    staleTime: 0,
    gcTime: 0,
  });
}
