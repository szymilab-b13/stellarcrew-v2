import type { RoadmapMilestone } from '../types';

export const AUDIT_MILESTONE: RoadmapMilestone = {
  key: 'audit',
  status: 'done',
  tasks: [
    {
      id: 'BL-AUDIT-001',
      description:
        'Schema audit + audit_log table (actor_user_id, action, resource_type/id, before_state/after_state JSONB, metadata, occurred_at, undone_at/undone_by, reverts_entry_id) + indexes + migration.',
      status: 'done',
      commit: 'fc1fe35',
    },
    {
      id: 'BL-AUDIT-002',
      description:
        'AuditApi::log implementing Core/Api/AuditLoggerInterface (BL-CORE-008 finally gets its first implementation) + persistence + DI binding + unit tests.',
      status: 'done',
      commit: 'f6ef9f9',
    },
    {
      id: 'BL-AUDIT-003',
      description:
        'AuditApi::search + get + pagination DTOs + HTTP GET /api/audit/entries(/{id}) with filters (actor, action, resource, date range, only_reversible, include_undone) + super_admin guard.',
      status: 'done',
      commit: '2ea0aa8',
    },
    {
      id: 'BL-AUDIT-004',
      description:
        'Undo dispatcher: UndoerInterface + UndoDescriberInterface tagged services, AuditApi::undo / describeUndo, HTTP POST /api/audit/entries/{id}/undo returning {new_entry_id}, 404/409/410/422 mapping.',
      status: 'done',
      commit: '0633493',
    },
    {
      id: 'BL-AUDIT-005',
      description:
        'UI /admin/audit: table + filters (actor autocomplete, action select grouped per module, resource, date range) + undo button + confirm modal with UndoDescriber output + i18n pl/en.',
      status: 'done',
      commit: 'b610bb1',
    },
    {
      id: 'BL-AUDIT-006',
      description:
        'Sidebar menu item "Audit log" via MenuContributor (visible to super admin only).',
      status: 'done',
      commit: '8489f05',
    },
    {
      id: 'BL-AUDIT-007',
      description:
        'UserManagement module registers UserManagementUndoer + UserManagementUndoDescriber for 6 reversible UM actions (user.created / .deactivated / .reactivated, workspace_member.assigned / .removed / .role_changed). profile_updated out of scope (no handler emits it yet); password_reset stays non-reversible. (Split: user actions 58a8052, workspace_member actions 672c8c2, describer ae2bb97.)',
      status: 'done',
      commit: 'ae2bb97',
    },
  ],
};
