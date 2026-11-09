import type { RoadmapMilestone } from '../types';

export const PROJECTS_MILESTONE: RoadmapMilestone = {
  key: 'projectsLayer',
  status: 'inProgress',
  note: 'Project jako warstwa między Workspace a agentami: workspace ma wiele projektów (np. TalentBridge API/APP/Yii), każdy z własnymi settings/agents/members/skills/flow/tasks. Fundament pod ~60% reszty wizji (Scheduler, Tasks, Flows per projekt). Brief zatwierdzony 2026-11-09 (8 decyzji: A/A/A/A/A/A/C/A).',
  tasks: [
    { id: 'BL-PROJ-001', status: 'done', commit: 'c23aae5' },
    { id: 'BL-PROJ-002', status: 'planned' },
    { id: 'BL-PROJ-003', status: 'planned' },
    { id: 'BL-PROJ-004', status: 'planned' },
    { id: 'BL-PROJ-005', status: 'planned' },
    { id: 'BL-PROJ-006', status: 'planned' },
    { id: 'BL-PROJ-007', status: 'planned' },
    { id: 'BL-PROJ-008', status: 'planned' },
    { id: 'BL-PROJ-009', status: 'planned' },
    { id: 'BL-PROJ-010', status: 'planned' },
    { id: 'BL-PROJ-011', status: 'done', commit: '6ee9ad8' },
    { id: 'BL-PROJ-012', status: 'done', commit: '3e03083' },
    { id: 'BL-PROJ-013', status: 'done', commit: 'c79e809' },
    { id: 'BL-PROJ-014', status: 'done', commit: 'ce15929' },
    { id: 'BL-PROJ-015', status: 'done', commit: '5810301' },
  ],
};
