import type { RoadmapMilestone } from '../types';
import { AGENT_RUNTIME_MILESTONE } from './agentRuntime';
import { AGENTS_MILESTONE } from './agents';
import { AGENTS_ORCHESTRATION_MILESTONE } from './agentsOrchestration';
import { AI_ENGINE_MILESTONE } from './aiEngine';
import { AUDIT_MILESTONE } from './audit';
import { AUTH_MILESTONE } from './auth';
import { AUTH_REFRESH_MILESTONE } from './authRefresh';
import { CHAT_MILESTONE } from './chat';
import { CORE_MILESTONE } from './core';
import { FLOWS_MILESTONE } from './flows';
import { INTEGRATION_TESTS_MILESTONE } from './integrationTests';
import { KNOWLEDGE_MILESTONE } from './knowledge';
import { ORGANIZATION_MILESTONE } from './organization';
import { PERMISSIONS_MILESTONE } from './permissions';
import { PLANS_USAGE_MILESTONE } from './plansUsage';
import { PLUGINS_MILESTONE } from './plugins';
import { PROJECT_DASHBOARDS_MILESTONE } from './projectDashboards';
import { PROJECT_DOCS_MILESTONE } from './projectDocs';
import { PROJECTS_MILESTONE } from './projects';
import { SCHEDULER_MILESTONE } from './scheduler';
import { SKILLS_MILESTONE } from './skills';
import { THREADS_MILESTONE } from './threads';
import { UI_MILESTONE } from './ui';
import { USER_MANAGEMENT_MILESTONE } from './userManagement';
import { VARIABLES_MILESTONE } from './variables';
import { WORKSPACE_UX_MILESTONE } from './workspaceUx';
import { WORKSPACES_MILESTONE } from './workspaces';

export const ROADMAP: RoadmapMilestone[] = [
  CORE_MILESTONE,
  AUTH_MILESTONE,
  UI_MILESTONE,
  WORKSPACES_MILESTONE,
  USER_MANAGEMENT_MILESTONE,
  AUDIT_MILESTONE,
  WORKSPACE_UX_MILESTONE,
  INTEGRATION_TESTS_MILESTONE,
  PERMISSIONS_MILESTONE,
  ORGANIZATION_MILESTONE,
  PROJECTS_MILESTONE,
  PROJECT_DOCS_MILESTONE,
  VARIABLES_MILESTONE,
  AGENTS_MILESTONE,
  AI_ENGINE_MILESTONE,
  KNOWLEDGE_MILESTONE,
  SKILLS_MILESTONE,
  FLOWS_MILESTONE,
  AGENT_RUNTIME_MILESTONE,
  PLUGINS_MILESTONE,
  SCHEDULER_MILESTONE,
  PROJECT_DASHBOARDS_MILESTONE,
  AUTH_REFRESH_MILESTONE,
  CHAT_MILESTONE,
  THREADS_MILESTONE,
  PLANS_USAGE_MILESTONE,
  AGENTS_ORCHESTRATION_MILESTONE,
];
