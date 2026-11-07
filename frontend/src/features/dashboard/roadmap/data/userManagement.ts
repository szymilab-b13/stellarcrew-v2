import type { RoadmapMilestone } from '../types';

export const USER_MANAGEMENT_MILESTONE: RoadmapMilestone = {
  key: 'userManagement',
  status: 'planned',
  tasks: [
    {
      id: 'BL-UM-001',
      description:
        'Domain model: schema user_management, UserProfile entity + Doctrine mapping + migration, UserProfilesApi stub, install wizard seeds admin profile.',
      status: 'tbd',
    },
    {
      id: 'BL-UM-002',
      description:
        'UserManagementApi::createUser (consumes AuthApi::createUser) + Command/Handler + emits audit event user_management.user.created with after_state (email, is_super_admin).',
      status: 'tbd',
    },
    {
      id: 'BL-UM-003',
      description:
        'HTTP POST /api/user-management/users with #[MapRequestPayload] DTO, validation (email unique, password min 12), 201/422 mapping, security guard (super admin only).',
      status: 'tbd',
    },
    {
      id: 'BL-UM-004',
      description:
        'UserManagementApi::listAllUsers + listUsersVisibleTo + Query/Handler + filters q / workspace_id / has_super_admin / only_deactivated, pagination.',
      status: 'tbd',
    },
    {
      id: 'BL-UM-005',
      description:
        'UI /admin/users (super admin): table + filters + Add User modal + sidebar menu item via MenuContributor + i18n pl/en.',
      status: 'tbd',
    },
    {
      id: 'BL-UM-006',
      description:
        'Workspaces members management in WorkspacesApi: assignMember / changeMemberRole / removeMember with hard-constraint guard (admin cannot be removed, downgrade first). Audit logs on every mutation.',
      status: 'tbd',
    },
    {
      id: 'BL-UM-007',
      description:
        'HTTP POST/PATCH/DELETE /api/user-management/users/{id}/workspace-assignments/* with validation + 422 on admin-remove attempts.',
      status: 'tbd',
    },
    {
      id: 'BL-UM-008',
      description:
        'UI /workspace/{id}/settings/members: table + add member autocomplete (visible-users API) + role select (admin/member/guest) + remove modal + i18n.',
      status: 'tbd',
    },
    {
      id: 'BL-UM-009',
      description:
        'Role pills on tiled landing /workspaces + subtitle on workspace switcher + "Moje workspace\'y" section in profile dropdown. Colors: owner=amber, admin=blue, member=emerald, guest=slate.',
      status: 'tbd',
    },
    {
      id: 'BL-UM-010',
      description:
        'User detail page /admin/users/{id}: header with status pills + workspace assignments section + account section + "Historia zmian" with last 20 audit entries + link to full audit filtered by user.',
      status: 'tbd',
    },
    {
      id: 'BL-UM-011',
      description:
        'Deactivate / reactivate account: endpoints + UI confirm modals + AuthApi::markDeactivated / markReactivated + audit logs (both reversible).',
      status: 'tbd',
    },
    {
      id: 'BL-UM-012',
      description:
        'Reset password (super admin): endpoint + AuthApi::setPassword + UI modal with password+confirm fields + audit log (non-reversible, no password in before_state).',
      status: 'tbd',
    },
  ],
};
