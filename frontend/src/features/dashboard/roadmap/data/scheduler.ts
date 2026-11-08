import type { RoadmapMilestone } from '../types';

export const SCHEDULER_MILESTONE: RoadmapMilestone = {
  key: 'scheduler',
  status: 'tbd',
  discussNeeded: true,
  note: 'Scheduler w menu workspace + project — cykliczne zadania agentów (cron-like). Np. codzienny content run w workspace Agentic-Social-Media. Zależy od AGENTS + FLOWS. Needs brief with decisions: cron engine (Symfony Scheduler vs custom), granularity, time zones, missed run policy.',
  tasks: [{ id: 'BL-SCHED-ARCH', status: 'planned' }],
};
