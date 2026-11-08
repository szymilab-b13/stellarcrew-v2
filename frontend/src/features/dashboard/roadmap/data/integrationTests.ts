import type { RoadmapMilestone } from '../types';

export const INTEGRATION_TESTS_MILESTONE: RoadmapMilestone = {
  key: 'integrationTests',
  status: 'tbd',
  discussNeeded: true,
  note: 'Atomic integration tests foundation: DAMA DoctrineTestBundle (transaction rollback per test) + IntegrationTestCase base + TracingDomainEventDispatcher + Test/DataProvider split pattern. Covers BL-UM-004 followup (real-DB ListUsersIntegrationTest). Brief: knowledge/briefs/1e-integration-tests.md. Needs decisions on factories (Foundry vs pure PHP), DB strategy (tx rollback only vs opt-in recreate), event assertion helpers, naming.',
  tasks: [{ id: 'BL-INT-FOUNDATION', status: 'planned' }],
};
