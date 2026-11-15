import type { RoadmapMilestone } from '../types';

export const AGENTS_AUDIT_MILESTONE: RoadmapMilestone = {
  key: 'agentsAudit',
  status: 'done',
  note: 'Audit retrofit dla 4 handlerów Agents module (Create / Update / Delete / CreateFromTemplate). Mirror BL-PROJ-004/009..013: AuditLogger wire + before/after snapshot + agents.agent.* events. 4/4 BL done, branch feat/agents-audit.',
  tasks: [
    { id: 'BL-AGAUDIT-001', status: 'done', commit: '8204d62' },
    { id: 'BL-AGAUDIT-002', status: 'done', commit: 'ff8cb8d' },
    { id: 'BL-AGAUDIT-003', status: 'done', commit: '41c4707' },
    { id: 'BL-AGAUDIT-004', status: 'done', commit: '5d1a825' },
  ],
};
