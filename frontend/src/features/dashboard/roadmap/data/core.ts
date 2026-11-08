import type { RoadmapMilestone } from '../types';

export const CORE_MILESTONE: RoadmapMilestone = {
  key: 'core',
  status: 'done',
  tasks: [
    { id: 'BL-SETUP-003', status: 'done' },
    { id: 'BL-CORE-001', status: 'done' },
    { id: 'BL-CORE-002', status: 'done' },
    { id: 'BL-CORE-003A', status: 'done', commit: 'f52b949' },
    { id: 'BL-CORE-003B', status: 'done', commit: '2062b4c' },
    { id: 'BL-CORE-003C', status: 'done', commit: 'e6df462' },
    { id: 'BL-CORE-003C-hotfix', status: 'done', commit: '492e971' },
    { id: 'BL-CORE-003D-a', status: 'done', commit: '66e022e' },
    { id: 'BL-CORE-006', status: 'done' },
    { id: 'BL-CORE-007', status: 'done' },
  ],
};
