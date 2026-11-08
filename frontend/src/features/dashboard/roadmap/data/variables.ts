import type { RoadmapMilestone } from '../types';

export const VARIABLES_MILESTONE: RoadmapMilestone = {
  key: 'variables',
  status: 'tbd',
  discussNeeded: true,
  note: 'Variables/Zmienne system — globalne + per-workspace/project (klucze typu IP_SERVERA, API_TOKEN). Agenci referencują przez $VAR_NAME bez eksponowania wartości w promptach. Potrzebne przed implementacją agentów używających external services. Needs brief with decisions: scope (global vs workspace vs project), secret vs plain, encryption at rest.',
  tasks: [{ id: 'BL-VAR-ARCH', status: 'planned' }],
};
