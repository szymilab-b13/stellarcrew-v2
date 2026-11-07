import type { RoadmapMilestone } from '../types';

export const PERMISSIONS_MILESTONE: RoadmapMilestone = {
  key: 'permissions',
  status: 'tbd',
  discussNeeded: true,
  note: 'Spec pending. Tasks below are tentative and will be rewritten after USER MANAGEMENT lands (needs voter test material: a second user with a different role).',
  tasks: [
    {
      id: 'BL-PERM-001',
      description:
        'Permission enum per module (workspaces.view, workspaces.manage, agents.view, …); keep UserRole as a shorthand for "has all permissions in a workspace".',
      status: 'planned',
    },
    {
      id: 'BL-PERM-002',
      description:
        'Symfony voters resolving per-workspace membership + permission, replacing the Admin/User flag at the policy boundary.',
      status: 'planned',
    },
    {
      id: 'BL-PERM-003',
      description:
        'Role management UI inside a workspace: list members, assign per-workspace role, toggle individual permissions.',
      status: 'planned',
    },
  ],
};
