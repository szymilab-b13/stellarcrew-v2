import type { RoadmapMilestone } from '../types';

export const KNOWLEDGE_MILESTONE: RoadmapMilestone = {
  key: 'knowledge',
  status: 'tbd',
  discussNeeded: true,
  note: 'Knowledge base w systemie (nie w plikach jak teraz). Dwa poziomy: ogólna (shared) + dedykowana per agent. Dla bezpieczeństwa DB per agent + globalna dla kontekstu jeśli pójdzie w świat. Needs brief with decisions: storage (table + JSONB vs vector DB like Qdrant/Chroma), retrieval strategy (semantic search vs keyword), scope visibility.',
  tasks: [{ id: 'BL-KB-ARCH', status: 'planned' }],
};
