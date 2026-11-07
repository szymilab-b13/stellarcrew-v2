import type { RoadmapMilestone } from '../types';

export const WORKSPACES_MILESTONE: RoadmapMilestone = {
  key: 'workspaces',
  status: 'done',
  tasks: [
    {
      id: 'BL-WS-001',
      description:
        'Workspaces module: Workspace / Member / Invitation domain + Doctrine mapping + WorkspacesApi, migration, install seeds a Default workspace owned by the admin.',
      status: 'done',
      commit: 'aced1c8',
    },
    {
      id: 'BL-WS-002',
      description:
        'Workspaces CRUD endpoints: GET/POST /api/workspaces, PATCH/DELETE /api/workspaces/{id} with workspace.edit / workspace.delete checks, soft delete, 404/403/422 mapping.',
      status: 'done',
      commit: 'ff866e3',
    },
    {
      id: 'BL-WS-003',
      description:
        'Workspaces UI: tiled landing at /workspaces with WorkspaceTile + AddWorkspaceTile, create/edit dialog (react-hook-form + zod, 8-color palette, auto-initials), delete confirmation, TanStack Query hooks, i18n pl + en.',
      status: 'done',
      commit: '5dc1815',
    },
    {
      id: 'BL-WS-004',
      description:
        'useCurrentWorkspace Zustand store (persist in localStorage) + CurrentWorkspaceGuard around /dashboard (auto-pick, redirect, stale cleanup), logout resets selection.',
      status: 'done',
      commit: '907f198',
    },
    {
      id: 'BL-WS-005',
      description:
        'Real WorkspaceSwitcher in the sidebar: popover menu wired to useWorkspacesList + useCurrentWorkspace, Manage link to /workspaces, collapsed-mode tile with tooltip.',
      status: 'done',
      commit: 'ef731c7',
    },
    {
      id: 'BL-WS-HOTFIX-001',
      description:
        'Hotfix: frontend posted iconColor as hex while backend expected palette keys; switched FE to palette keys. Reverted in HOTFIX-002 after Szef decided to store hex end-to-end.',
      status: 'done',
      commit: '2adfd6a',
    },
    {
      id: 'BL-WS-HOTFIX-002',
      description:
        'Storage model swap: iconColor stored as #RRGGBB hex (regex validation) instead of a fixed palette enum, FE rolled back to hex, BE accepts arbitrary palette entries.',
      status: 'done',
      commit: '404e4cc',
    },
  ],
};
