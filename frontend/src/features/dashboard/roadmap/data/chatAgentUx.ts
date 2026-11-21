import type { RoadmapMilestone } from '../types';

export const CHAT_AGENT_UX_MILESTONE: RoadmapMilestone = {
  key: 'chatAgentUx',
  status: 'done',
  note: 'Agent-centric chat redesign on top of CHAT foundation: AgentsSidebar filtered to direct messages with per-agent brand colors, Add Conversation modal (search + agents table), ConversationFeed with ChatHeader + ThreadTabs (All chip + per-thread chips) and ThreadComposer with a gear popover (engine type / engine id / priority / new topic toggle). Follow-up redesign BL-CAGU-R (9f6f486 + 3795bb8): dropped ThreadTabs + ThreadSection, flat chronological stream, per-agent color bubble tint, ChatSettingsModal in header. Thread tree view deferred to CHAT-BRAIN.',
  tasks: [
    { id: 'BL-CAGU-001', status: 'done', commit: '9907e92' },
    { id: 'BL-CAGU-002', status: 'done', commit: 'f0cc8cb' },
    { id: 'BL-CAGU-003', status: 'done', commit: '58a82e9' },
    { id: 'BL-CAGU-004', status: 'done', commit: '5e73ce7' },
    { id: 'BL-CAGU-005', status: 'done', commit: '13b5415' },
    { id: 'BL-CAGU-006', status: 'done', commit: '2144c90' },
    { id: 'BL-CAGU-006b', status: 'done', commit: 'd00eb3d' },
    { id: 'BL-CAGU-007', status: 'done', commit: 'a264682' },
    { id: 'BL-CAGU-008', status: 'done', commit: 'de8471c' },
    { id: 'BL-CAGU-009a', status: 'done', commit: '7dce839' },
    { id: 'BL-CAGU-009b', status: 'done', commit: 'a63c48f' },
    { id: 'BL-CAGU-009c', status: 'done', commit: '2d017ff' },
    { id: 'BL-CAGU-010', status: 'done', commit: '1a56c18' },
    { id: 'BL-CAGU-011', status: 'done', commit: '32346c9' },
    { id: 'BL-CAGU-R', status: 'done', commit: '9f6f486' },
  ],
};
