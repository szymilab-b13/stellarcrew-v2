export interface UserFiltersValue {
  q: string;
  workspaceId: string;
  hasSuperAdmin: 'all' | 'yes' | 'no';
  onlyDeactivated: boolean;
}

export const DEFAULT_USER_FILTERS: UserFiltersValue = {
  q: '',
  workspaceId: '',
  hasSuperAdmin: 'all',
  onlyDeactivated: false,
};

export function toApiFilters(value: UserFiltersValue): {
  q: string | null;
  workspaceId: string | null;
  hasSuperAdmin: boolean | null;
  onlyDeactivated: boolean | null;
} {
  return {
    q: value.q.trim() === '' ? null : value.q.trim(),
    workspaceId: value.workspaceId === '' ? null : value.workspaceId,
    hasSuperAdmin: value.hasSuperAdmin === 'all' ? null : value.hasSuperAdmin === 'yes',
    onlyDeactivated: value.onlyDeactivated ? true : null,
  };
}
