import type { RoadmapMilestone } from '../types';

export const AUDIT_MILESTONE: RoadmapMilestone = {
  key: 'audit',
  status: 'planned',
  tasks: [
    {
      id: 'BL-AUDIT-001',
      description:
        'Schema audit + audit_log table (actor_user_id, action, resource_type/id, before_state/after_state JSONB, metadata, occurred_at, undone_at/undone_by, reverts_entry_id) + indexes + migration.',
      status: 'tbd',
    },
    {
      id: 'BL-AUDIT-002',
      description:
        'AuditApi::log implementing Core/Api/AuditLoggerInterface (BL-CORE-008 finally gets its first implementation) + persistence + DI binding + unit tests.',
      status: 'tbd',
    },
    {
      id: 'BL-AUDIT-003',
      description:
        'AuditApi::search + get + pagination DTOs + HTTP GET /api/audit/entries(/{id}) with filters (actor, action, resource, date range, only_reversible, include_undone) + super_admin guard.',
      status: 'tbd',
    },
    {
      id: 'BL-AUDIT-004',
      description:
        'Undo dispatcher: UndoerInterface + UndoDescriberInterface tagged services, AuditApi::undo / describeUndo, HTTP POST /api/audit/entries/{id}/undo returning {new_entry_id}, 404/409/410/422 mapping.',
      status: 'tbd',
    },
    {
      id: 'BL-AUDIT-005',
      description:
        'UI /admin/audit: table + filters (actor autocomplete, action select grouped per module, resource, date range) + undo button + confirm modal with UndoDescriber output + i18n pl/en.',
      status: 'tbd',
    },
    {
      id: 'BL-AUDIT-006',
      description:
        'Sidebar menu item "Audit log" via MenuContributor (visible to super admin only).',
      status: 'tbd',
    },
    {
      id: 'BL-AUDIT-007',
      description:
        'UserManagement module registers UserManagementUndoer + UserManagementUndoDescriber for all 8 UM actions (user.created / .deactivated / .reactivated / .profile_updated, workspace_member.assigned / .removed / .role_changed). password_reset stays non-reversible.',
      status: 'tbd',
    },
  ],
};
