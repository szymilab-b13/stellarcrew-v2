import type { RoadmapMilestone } from '../types';

export const THREADS_MILESTONE: RoadmapMilestone = {
  key: 'threads',
  status: 'tbd',
  discussNeeded: true,
  note: 'Continuous threads that ride alongside milestones, not tied to a single release. All items need Szef confirmation before promotion to planned.',
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
    {
      id: 'BL-DOC-002',
      description:
        'Audit stellarcrew.dev public site: review current copy/features/roadmap claims vs v2 reality, identify stale content and missing screenshots, produce a short diff doc in knowledge/discovery/ before touching the site.',
      status: 'planned',
    },
  ],
};
