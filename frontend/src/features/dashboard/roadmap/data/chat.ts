import type { RoadmapMilestone } from '../types';

export const CHAT_MILESTONE: RoadmapMilestone = {
  key: 'chat',
  status: 'done',
  note: 'Foundation: envelope model (ChatSession/ChatThread/ChatMessage) + 4 REST endpoints + frontend MVP (API layer + /chat shell + MessageList/MessageBubble + MessageComposer). ACL private per user (owner_user_id). Phase 2 (Supervisor + LLM classifier) and Phase 4 (realtime WS/SSE) in LATER. BL-CHAT-001b (c3fa5ec) is an owner_user_id ACL hotfix folded under BL-CHAT-001.',
  tasks: [
    { id: 'BL-CHAT-001', status: 'done', commit: '1da9e4a' },
    { id: 'BL-CHAT-002', status: 'done', commit: '6ad1deb' },
    { id: 'BL-CHAT-003', status: 'done', commit: 'ea1cc05' },
    { id: 'BL-CHAT-004', status: 'done', commit: '199dab1' },
    { id: 'BL-CHAT-005', status: 'done', commit: 'bf5be55' },
    { id: 'BL-CHAT-006', status: 'done', commit: '81b499f' },
    { id: 'BL-CHAT-007', status: 'done', commit: 'bcdb4ea' },
    { id: 'BL-CHAT-F01', status: 'done', commit: '31a6dae' },
    { id: 'BL-CHAT-F02', status: 'done', commit: 'cc78f27' },
    { id: 'BL-CHAT-F03', status: 'done', commit: '068d565' },
    { id: 'BL-CHAT-F04', status: 'done', commit: 'cb55714' },
  ],
};
