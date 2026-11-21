import type { RoadmapMilestone } from '../types';

export const CHAT_AGENT_REPLY_MILESTONE: RoadmapMilestone = {
  key: 'chatAgentReply',
  status: 'done',
  note: 'Agent-generated replies via worker daemon: Worker aggregate + per-worker auth + RabbitMQ dispatch + Node worker daemon skeleton + pairing CLI + AMQP consumer + Claude spawn. Backend publishes AgentReplyRequested when thread has worker, worker consumes from per-worker queue, spawns `claude -p --resume` subprocess and records reply. Mercure streaming + production daemon packaging deferred.',
  tasks: [
    { id: 'BL-CAR-005b', status: 'done', commit: '7b56acb' },
    { id: 'BL-CAR-005d', status: 'done' },
    { id: 'BL-CAR-006', status: 'done', commit: 'c40e03f' },
    { id: 'BL-CAR-007', status: 'done', commit: '87fb7a3' },
    { id: 'BL-CAR-008', status: 'done', commit: '059668d' },
    { id: 'BL-CAR-008a', status: 'done', commit: 'f6bec66' },
    { id: 'BL-CAR-008b', status: 'done', commit: '5328dfd' },
    { id: 'BL-CAR-009', status: 'planned' },
    { id: 'BL-CAR-010', status: 'planned' },
  ],
};
