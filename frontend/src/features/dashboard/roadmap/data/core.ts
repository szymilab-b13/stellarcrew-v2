import type { RoadmapMilestone } from '../types';

export const CORE_MILESTONE: RoadmapMilestone = {
  key: 'core',
  status: 'done',
  tasks: [
    {
      id: 'BL-SETUP-003',
      description:
        'Modular wiring: autoload for Modules\\* bundles, Shared Kernel scaffold, backend container, phpstan/deptrac/cs-fixer config.',
      status: 'done',
    },
    {
      id: 'BL-CORE-001',
      description:
        'HealthController + HealthCheckInterface wiring (Postgres, Redis, RabbitMQ probes).',
      status: 'done',
    },
    {
      id: 'BL-CORE-002',
      description:
        'Install CLI (`bin/console stellarcrew:install`) that seeds the first admin and flips the is_installed flag.',
      status: 'done',
    },
    {
      id: 'BL-CORE-003A',
      description:
        'User moved from Core to Auth bundle; AuthApi exposed as the cross-module read port (ADR-BE-001).',
      status: 'done',
      commit: 'f52b949',
    },
    {
      id: 'BL-CORE-003B',
      description:
        'Install REST endpoints: GET /api/install/status + POST /api/install, ExceptionListener maps domain errors to 400/404.',
      status: 'done',
      commit: '2062b4c',
    },
    {
      id: 'BL-CORE-003C',
      description:
        'Install wizard web shell: React form + react-hook-form + zod, TanStack Query, Vite proxy for /install + /health.',
      status: 'done',
      commit: 'e6df462',
    },
    {
      id: 'BL-CORE-003C-hotfix',
      description:
        'Install gate on /, Vite proxy + env fallback, HomePage tests (bundled into PR #8).',
      status: 'done',
      commit: '492e971',
    },
    {
      id: 'BL-CORE-003D-a',
      description:
        '5-step install wizard layout (sidebar + topbar + step rail + footer), admin + branding + logo + workspace + review.',
      status: 'done',
      commit: '66e022e',
    },
    {
      id: 'BL-CORE-006',
      description:
        'Shared Kernel value objects: UserId, WorkspaceId, Clock, UuidGenerator (ADR-BE-002).',
      status: 'done',
    },
    {
      id: 'BL-CORE-007',
      description:
        'ExceptionListener converting domain exceptions to JSON error payloads consumed by the frontend.',
      status: 'done',
    },
  ],
};
