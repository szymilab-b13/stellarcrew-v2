import { useMutation, useQueryClient } from '@tanstack/react-query';

import { undoAuditEntry } from '../api/audit.api';
import type { UndoResult } from '../types';
import { auditEntriesQueryKey } from './useAuditEntries';

export function useUndoEntry() {
  const queryClient = useQueryClient();
  return useMutation<UndoResult, Error, string>({
    mutationFn: (id: string) => undoAuditEntry(id),
    onSuccess: () => {
      queryClient.invalidateQueries({ queryKey: auditEntriesQueryKey });
    },
  });
}
