import type { RoadmapMilestone } from '../types';

export const THREADS_MILESTONE: RoadmapMilestone = {
  key: 'threads',
  status: 'planned',
  note: 'Continuous threads that ride alongside milestones, not tied to a single release.',
  tasks: [
    {
      id: 'BL-DX-001',
      description:
        'Replace the ad-hoc fake health checks with a ci/services: block (postgres + redis + rabbit) once CI needs integration tests beyond the API surface.',
      status: 'planned',
    },
    {
      id: 'BL-DOC-001',
      description:
        'Each merged milestone ships with a short user-facing release note in the PR body (changelog style, no internals).',
      status: 'planned',
    },
  ],
};
