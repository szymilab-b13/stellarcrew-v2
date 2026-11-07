import type { RoadmapMilestone } from '../types';
import { AGENTS_MILESTONE } from './agents';
import { AUTH_MILESTONE } from './auth';
import { CORE_MILESTONE } from './core';
import { PERMISSIONS_MILESTONE } from './permissions';
import { UI_MILESTONE } from './ui';
import { WORKSPACES_MILESTONE } from './workspaces';

export const ROADMAP: RoadmapMilestone[] = [
  CORE_MILESTONE,
  AUTH_MILESTONE,
  UI_MILESTONE,
  WORKSPACES_MILESTONE,
  PERMISSIONS_MILESTONE,
  AGENTS_MILESTONE,
];
