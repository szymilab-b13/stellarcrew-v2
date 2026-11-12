import type { RoadmapMilestone } from '../types';

export const ORGANIZATION_MILESTONE: RoadmapMilestone = {
  key: 'organization',
  status: 'inProgress',
  note: 'Organization jako top-level warstwa 1:1 ownera, zawierająca Workspaces. Auto-create przy signupie/instalacji, settings page i breadcrumbs owner/invited gating. MVP backend complete (BL-ORG-001..007) + sidebar org id header (BL-ORG-008). BL-ORG-009 (settings page) + BL-ORG-010 (breadcrumbs gating) odłożone do Fazy 1. Transfer ownership, members, billing i logo deferred do oddzielnego milestone.',
  tasks: [
    { id: 'BL-ORG-001', status: 'done', commit: 'e2d66f2' },
    { id: 'BL-ORG-002', status: 'done', commit: '65b0205' },
    { id: 'BL-ORG-003', status: 'done', commit: '81cd616' },
    { id: 'BL-ORG-004', status: 'done', commit: '7f71483' },
    { id: 'BL-ORG-004b', status: 'done', commit: 'b07a94d' },
    { id: 'BL-ORG-005', status: 'done', commit: '17bb242' },
    { id: 'BL-ORG-006', status: 'done', commit: 'bd946c7' },
    { id: 'BL-ORG-007', status: 'done', commit: 'd7e1732' },
    { id: 'BL-ORG-008', status: 'done', commit: '80332be' },
    { id: 'BL-ORG-009', status: 'planned' },
    { id: 'BL-ORG-010', status: 'planned' },
  ],
};
