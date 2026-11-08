import type { RoadmapMilestone } from '../types';

export const FLOWS_MILESTONE: RoadmapMilestone = {
  key: 'flows',
  status: 'tbd',
  discussNeeded: true,
  note: 'Flow Builder — orchestracja agentów w projekcie (np. Maja → Maciek brief → Luna makiety → programming → CR → security). Każdy krok z opcjonalną bramką akceptacji (wymaga/nie wymaga). Core feature produktu. Zależy od AGENTS + SKILLS. Needs brief with decisions: flow DSL (JSON vs dedicated schema), triggers, conditions, step types.',
  tasks: [{ id: 'BL-FLOW-ARCH', status: 'planned' }],
};
