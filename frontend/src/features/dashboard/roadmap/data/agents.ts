import type { RoadmapMilestone } from '../types';

export const AGENTS_MILESTONE: RoadmapMilestone = {
  key: 'agents',
  status: 'planned',
  tasks: [
    {
      id: 'BL-AGENTS-001',
      description:
        'Predefined agents catalog (admin only): agents.predefined_agents schema + Doctrine mapping + admin CLI/panel to seed Maja, Viktor, Diego templates.',
      status: 'planned',
    },
    {
      id: 'BL-AGENTS-002',
      description:
        'User agent creation: agents.user_agents schema + endpoints to copy a predefined agent for the current user or create a custom one (predefined_agent_id = NULL).',
      status: 'planned',
    },
    {
      id: 'BL-AGENTS-003',
      description:
        'Agent assignment: agents.agent_assignments with polymorphic scope (workspace | project) + endpoints to attach/detach a user agent to a scope, UNIQUE (user_agent_id, scope, scope_id).',
      status: 'planned',
    },
    {
      id: 'BL-AGENTS-004',
      description:
        'Agent override: user may override name/instructions/avatar_color/shared_memory on their user_agent copy without touching the predefined template.',
      status: 'planned',
    },
    {
      id: 'BL-AGENTS-005',
      description:
        'Memory model (strategy TBD): agents.agent_memory with memory_type enum (conversation, document, embedding, note) + JSONB payload, scope-aware (shared_memory flag).',
      status: 'planned',
    },
    {
      id: 'BL-AGENTS-006',
      description:
        'Context strategies per agent (TBD): selection rules (RAG top-K, recency, importance) for assembling the context window from agent_memory — specified per agent, not globally.',
      status: 'tbd',
    },
    {
      id: 'BL-AGENTS-007',
      description:
        'Pinned agents in the sidebar: UI pattern to pin assigned user_agents next to the workspace switcher, with scope badge and quick jump.',
      status: 'planned',
    },
  ],
};
