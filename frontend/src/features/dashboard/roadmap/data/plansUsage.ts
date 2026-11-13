import type { RoadmapMilestone } from '../types';

export const PLANS_USAGE_MILESTONE: RoadmapMilestone = {
  key: 'plansUsage',
  status: 'tbd',
  discussNeeded: true,
  note: 'Cross-cutting plan tiers + usage quotas. Retrofit per module: Agents, Projects, Workspaces, Members. Each write guard checks PlanLimitsService before create. Draft scope: Plan entity, Organization.planId FK, per-org usage read-model, billing/subscription integration (Stripe) in Phase 1.',
  tasks: [
    { id: 'BL-PLANS-001', status: 'planned' },
    { id: 'BL-PLANS-002', status: 'planned' },
    { id: 'BL-PLANS-003', status: 'planned' },
    { id: 'BL-PLANS-004', status: 'planned' },
    { id: 'BL-PLANS-005', status: 'planned' },
    { id: 'BL-PLANS-006', status: 'planned' },
    { id: 'BL-PLANS-007', status: 'planned' },
    { id: 'BL-PLANS-008', status: 'tbd' },
  ],
};
