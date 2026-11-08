import type { RoadmapMilestone } from '../types';

export const PERMISSIONS_MILESTONE: RoadmapMilestone = {
  key: 'permissions',
  status: 'tbd',
  discussNeeded: true,
  note: 'Spec pending. Tasks below are tentative and will be rewritten after USER MANAGEMENT lands (needs voter test material: a second user with a different role).',
  tasks: [
    { id: 'BL-PERM-001', status: 'planned' },
    { id: 'BL-PERM-002', status: 'planned' },
    { id: 'BL-PERM-003', status: 'planned' },
  ],
};
