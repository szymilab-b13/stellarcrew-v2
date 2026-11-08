import type { AuditFilters } from '../types';

export const AUDIT_ACTIONS: ReadonlyArray<{ group: string; actions: string[] }> = [
  {
    group: 'auth',
    actions: ['auth.user.login.success', 'auth.user.logout'],
  },
  {
    group: 'user_management',
    actions: [
      'user_management.user.created',
      'user_management.user.deactivated',
      'user_management.user.reactivated',
      'user_management.user.password_reset',
    ],
  },
  {
    group: 'workspaces',
    actions: [
      'workspace_member.assigned',
      'workspace_member.role_changed',
      'workspace_member.removed',
    ],
  },
];

export interface AuditFiltersValue {
  actorUserId: string;
  action: string;
  resourceType: string;
  resourceId: string;
  from: string;
  to: string;
  onlyReversible: boolean;
  showUndone: boolean;
}

export const DEFAULT_AUDIT_FILTERS: AuditFiltersValue = {
  actorUserId: '',
  action: '',
  resourceType: '',
  resourceId: '',
  from: '',
  to: '',
  onlyReversible: false,
  showUndone: true,
};

const REVERSIBLE_ACTIONS = new Set<string>([
  'user_management.user.created',
  'user_management.user.deactivated',
  'user_management.user.reactivated',
  'user_management.user.password_reset',
  'workspace_member.assigned',
  'workspace_member.role_changed',
  'workspace_member.removed',
]);

export function isReversibleAction(action: string): boolean {
  return REVERSIBLE_ACTIONS.has(action);
}

export function toApiFilters(value: AuditFiltersValue): AuditFilters {
  return {
    actorUserId: value.actorUserId === '' ? null : value.actorUserId,
    action: value.action === '' ? null : value.action,
    resourceType: value.resourceType.trim() === '' ? null : value.resourceType.trim(),
    resourceId: value.resourceId.trim() === '' ? null : value.resourceId.trim(),
    from: value.from === '' ? null : new Date(value.from).toISOString(),
    to: value.to === '' ? null : new Date(value.to).toISOString(),
    includeUndone: value.showUndone,
  };
}

export function filtersFromSearchParams(params: URLSearchParams): AuditFiltersValue {
  return {
    actorUserId: params.get('actor_user_id') ?? '',
    action: params.get('action') ?? '',
    resourceType: params.get('resource_type') ?? '',
    resourceId: params.get('resource_id') ?? '',
    from: params.get('from') ?? '',
    to: params.get('to') ?? '',
    onlyReversible: params.get('only_reversible') === 'true',
    showUndone: params.get('show_undone') !== 'false',
  };
}
