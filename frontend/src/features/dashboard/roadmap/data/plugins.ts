import type { RoadmapMilestone } from '../types';

export const PLUGINS_MILESTONE: RoadmapMilestone = {
  key: 'plugins',
  status: 'tbd',
  discussNeeded: true,
  note: 'Plugin architecture: per-workspace modules, not instance-global. Covers data model reboot (workspace_plugin pivot + per-workspace row scoping), lazy plugin loader (DI + guarded routes), dynamic workspace navigation, and later plugin sources (local → git URL → marketplace). Full research: knowledge/discovery/plugin-architecture.md. Needs ADR before scoping tasks.',
  tasks: [{ id: 'BL-PLUGIN-ARCH', status: 'planned' }],
};
