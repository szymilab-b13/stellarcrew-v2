import { apiFetch } from '@/lib/apiClient';

import {
  mapAuditPage,
  type AuditFilters,
  type AuditPage,
  type AuditPagePayload,
  type UndoDescription,
  type UndoResult,
  type UndoResultPayload,
} from '../types';

export async function fetchAuditEntries(filters: AuditFilters = {}): Promise<AuditPage> {
  const payload = await apiFetch<AuditPagePayload>(buildListPath(filters));
  return mapAuditPage(payload);
}

export async function undoAuditEntry(id: string): Promise<UndoResult> {
  const payload = await apiFetch<UndoResultPayload>(`/api/audit/entries/${id}/undo`, {
    method: 'POST',
  });
  return { newEntryId: payload.new_entry_id };
}

export async function describeAuditUndo(id: string): Promise<UndoDescription> {
  return apiFetch<UndoDescription>(`/api/audit/entries/${id}/describe-undo`);
}

function buildListPath(filters: AuditFilters): string {
  const params = new URLSearchParams();
  if (filters.actorUserId) params.set('actor_user_id', filters.actorUserId);
  if (filters.action) params.set('action', filters.action);
  if (filters.resourceType) params.set('resource_type', filters.resourceType);
  if (filters.resourceId) params.set('resource_id', filters.resourceId);
  if (filters.from) params.set('from', filters.from);
  if (filters.to) params.set('to', filters.to);
  if (filters.includeUndone !== null && filters.includeUndone !== undefined) {
    params.set('include_undone', filters.includeUndone ? 'true' : 'false');
  }
  if (filters.page !== undefined) params.set('page', String(filters.page));
  if (filters.perPage !== undefined) params.set('per_page', String(filters.perPage));

  const query = params.toString();
  return query === '' ? '/api/audit/entries' : `/api/audit/entries?${query}`;
}
