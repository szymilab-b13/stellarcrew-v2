import type { RoadmapMilestone } from '../types';

export const PROJECTS_MILESTONE: RoadmapMilestone = {
  key: 'projectsLayer',
  status: 'tbd',
  discussNeeded: true,
  note: 'Project jako warstwa między Workspace a agentami: workspace ma wiele projektów (np. TalentBridge API/APP/Yii), każdy z własnymi settings/agents/members/skills/flow/tasks. Fundament pod ~60% reszty wizji (Scheduler, Tasks, Flows per projekt). Needs brief before scoping tasks.',
  tasks: [{ id: 'BL-PROJ-ARCH', status: 'planned' }],
};
