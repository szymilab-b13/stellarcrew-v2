import type { RoadmapMilestone } from '../types';

export const CHAT_MILESTONE: RoadmapMilestone = {
  key: 'chat',
  status: 'tbd',
  discussNeeded: true,
  note: 'Depends on AGENTS and WORKSPACES. Scope to lock in after AGENTS lands: conversation model, message streaming, per-agent context assembly and UI pattern for threads.',
  tasks: [],
};
