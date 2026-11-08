import type { RoadmapMilestone } from '../types';

export const THREADS_MILESTONE: RoadmapMilestone = {
  key: 'threads',
  status: 'tbd',
  discussNeeded: true,
  note: 'Continuous threads that ride alongside milestones, not tied to a single release. All items need Szef confirmation before promotion to planned.',
  tasks: [
    { id: 'BL-DX-001', status: 'planned' },
    { id: 'BL-DOC-001', status: 'planned' },
    { id: 'BL-DOC-002', status: 'planned' },
  ],
};
