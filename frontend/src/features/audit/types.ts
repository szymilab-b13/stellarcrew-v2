export interface AuditEntry {
  id: string;
  actorUserId: string;
  actorEmail: string | null;
  action: string;
  resourceType: string | null;
  resourceId: string | null;
  beforeState: Record<string, unknown> | null;
  afterState: Record<string, unknown> | null;
  metadata: Record<string, unknown>;
  occurredAt: string;
  undoneAt: string | null;
  undoneBy: string | null;
  undoneByEmail: string | null;
  revertsEntryId: string | null;
}

export interface AuditPage {
  items: AuditEntry[];
  total: number;
  page: number;
  perPage: number;
}

export interface AuditFilters {
  actorUserId?: string | null;
  action?: string | null;
  resourceType?: string | null;
  resourceId?: string | null;
  from?: string | null;
  to?: string | null;
  includeUndone?: boolean | null;
  page?: number;
  perPage?: number;
}

export interface UndoResult {
  newEntryId: string;
}

export interface UndoDescription {
  description: string;
}

export interface AuditEntryPayload {
  id: string;
  actor_user_id: string;
  actor_email: string | null;
  action: string;
  resource_type: string | null;
  resource_id: string | null;
  before_state: Record<string, unknown> | null;
  after_state: Record<string, unknown> | null;
  metadata: Record<string, unknown>;
  occurred_at: string;
  undone_at: string | null;
  undone_by: string | null;
  undone_by_email: string | null;
  reverts_entry_id: string | null;
}

export interface AuditPagePayload {
  items: AuditEntryPayload[];
  total: number;
  page: number;
  per_page: number;
}

export interface UndoResultPayload {
  new_entry_id: string;
}

export function mapAuditEntry(payload: AuditEntryPayload): AuditEntry {
  return {
    id: payload.id,
    actorUserId: payload.actor_user_id,
    actorEmail: payload.actor_email,
    action: payload.action,
    resourceType: payload.resource_type,
    resourceId: payload.resource_id,
    beforeState: payload.before_state,
    afterState: payload.after_state,
    metadata: payload.metadata,
    occurredAt: payload.occurred_at,
    undoneAt: payload.undone_at,
    undoneBy: payload.undone_by,
    undoneByEmail: payload.undone_by_email,
    revertsEntryId: payload.reverts_entry_id,
  };
}

export function mapAuditPage(payload: AuditPagePayload): AuditPage {
  return {
    items: payload.items.map(mapAuditEntry),
    total: payload.total,
    page: payload.page,
    perPage: payload.per_page,
  };
}
