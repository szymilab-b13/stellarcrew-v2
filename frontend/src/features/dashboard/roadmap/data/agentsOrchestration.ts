import type { RoadmapMilestone } from '../types';

export const AGENTS_ORCHESTRATION_MILESTONE: RoadmapMilestone = {
  key: 'agentsOrchestration',
  status: 'tbd',
  discussNeeded: true,
  note: 'Multi-step agentic workflow with sub-agents and context isolation. Main agent delegates research/planning/execution to isolated sub-sessions whose output lands in a scratchpad (short-term, per-thread, per-task), then the main agent continues with the digest. Covers: AgentOrchestrator service, agent_sub_sessions lifecycle, agent_scratchpads design (DEFERRED — scratchpad may be generated in-flight from chat; pending Context object decision), agent_todo_items per thread, sub-session UI streaming, context compaction policy, observability/trace UI (Langfuse-like). Depends on AGENTS-RUNTIME (runtime_mode + engine_provider wiring) and AGENTS-KNOWLEDGE (MCP tools catalog).',
  tasks: [
    { id: 'BL-AGENT-ORCH-001', status: 'planned' },
    { id: 'BL-AGENT-ORCH-002', status: 'planned' },
    { id: 'BL-AGENT-ORCH-003', status: 'tbd' },
    { id: 'BL-AGENT-ORCH-004', status: 'planned' },
    { id: 'BL-AGENT-ORCH-005', status: 'planned' },
    { id: 'BL-AGENT-ORCH-006', status: 'planned' },
    { id: 'BL-AGENT-ORCH-007', status: 'planned' },
  ],
};
