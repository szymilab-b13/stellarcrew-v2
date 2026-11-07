import type { RoadmapMilestone } from '../types';
import { AGENTS_MILESTONE } from './agents';
import { AI_ENGINE_MILESTONE } from './aiEngine';
import { AUTH_MILESTONE } from './auth';
import { AUTH_REFRESH_MILESTONE } from './authRefresh';
import { CHAT_MILESTONE } from './chat';
import { CORE_MILESTONE } from './core';
import { PERMISSIONS_MILESTONE } from './permissions';
import { THREADS_MILESTONE } from './threads';
import { UI_MILESTONE } from './ui';
import { WORKSPACES_MILESTONE } from './workspaces';

export const ROADMAP: RoadmapMilestone[] = [
  CORE_MILESTONE,
  AUTH_MILESTONE,
  UI_MILESTONE,
  WORKSPACES_MILESTONE,
  PERMISSIONS_MILESTONE,
  AGENTS_MILESTONE,
  AI_ENGINE_MILESTONE,
  AUTH_REFRESH_MILESTONE,
  CHAT_MILESTONE,
  THREADS_MILESTONE,
];
