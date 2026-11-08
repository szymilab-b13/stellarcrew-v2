import type { RoadmapMilestone } from '../types';

export const SKILLS_MILESTONE: RoadmapMilestone = {
  key: 'skills',
  status: 'tbd',
  discussNeeded: true,
  note: 'Skills system — atomowe procedury które agent wywołuje per akcja (np. "nowy moduł Symfony", "napisz SQL", "weryfikuj branch"). Ogólna baza skills + dedykowana per agent. Z ogólnej można przypisywać do wszystkich. Needs brief with decisions: skill storage (DB vs filesystem), skill versioning, skill execution context.',
  tasks: [{ id: 'BL-SKILL-ARCH', status: 'planned' }],
};
