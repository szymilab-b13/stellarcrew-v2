import type { RoadmapMilestone } from '../types';

export const AGENT_RUNTIME_MILESTONE: RoadmapMilestone = {
  key: 'agentRuntime',
  status: 'tbd',
  discussNeeded: true,
  note: 'Agent runtime infrastructure: states machine (discovery/builder/analitics), builder auto-switch to listener gdy brak pracy, worker split / thread orchestration (Viktor → worker-1/2/3 z izolacją kontekstu), is_first + compact protection (force-clear po context overflow), smart prompt sync (nie wysyłaj całego prompta co raz). Zależy od AGENTS. Needs brief.',
  tasks: [
    { id: 'BL-RUNTIME-STATES', status: 'planned' },
    { id: 'BL-RUNTIME-WORKERS', status: 'planned' },
    { id: 'BL-RUNTIME-COMPACT', status: 'planned' },
    { id: 'BL-RUNTIME-PROMPT-SYNC', status: 'planned' },
  ],
};
