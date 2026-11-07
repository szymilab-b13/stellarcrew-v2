import type { RoadmapMilestone } from '../types';

export const UI_MILESTONE: RoadmapMilestone = {
  key: 'ui',
  status: 'done',
  tasks: [
    {
      id: 'BL-UI-001',
      description:
        'Backend menu registry: MenuContributor interface + MenuAssembler + GET /api/menu. CoreMenu (Dashboard) and WorkspacesMenu contributors, admin sees everything, non-admins filtered per UserRole.',
      status: 'done',
      commit: 'ad1ac4d',
    },
    {
      id: 'BL-UI-002',
      description:
        'Frontend app shell: AppLayout with ~240 px sidebar (Logo, collapse arrow, collapsible sections, footer), /dashboard + /workspaces stubs, redirect from / to /dashboard, i18n PL/EN for menu labels.',
      status: 'done',
      commit: '2d3136d',
    },
    {
      id: 'BL-UI-003',
      description:
        'WorkspaceSwitcher placeholder under the Logo (single "Default workspace" option, no-op) — replaced by the real switcher in milestone WORKSPACES.',
      status: 'done',
      commit: '2d3136d',
    },
    {
      id: 'BL-UI-TOPBAR',
      description:
        'AppTopbar with user menu; Logout, language and theme toggles moved out of the sidebar into the topbar.',
      status: 'done',
      commit: '55cad6c',
    },
    {
      id: 'BL-UI-FIX-01',
      description:
        'Deptrac fix: break Api→Domain dependency on UserRole in MenuItem DTO (role stored as string, MenuAssembler compares role->value).',
      status: 'done',
      commit: 'ebb282b',
    },
    {
      id: 'BL-UI-004',
      description:
        'Menu pinning UI: hover star to pin/unpin, pinned section at the top of the sidebar, max 5, scroll beyond, hidden when empty, persisted in localStorage. usePinnedMenu hook + 8 unit tests.',
      status: 'done',
      commit: '45f116c',
    },
    {
      id: 'BL-UI-POLISH-001',
      description:
        'Design 2.0 polish: shared Button + Input components with sc design tokens (--sc-text-cancel, --sc-danger, --sc-danger-muted, white --sc-primary-foreground). Button variants primary/secondary/destructive, Input invalid prop.',
      status: 'done',
      commit: '699a705',
    },
    {
      id: 'BL-WS-UI-001',
      description:
        'WorkspaceDialog + DeleteConfirmDialog rebuilt on shared Button/Input. ArrowRight CTA, sc-text-cancel ghost Cancel, aria-invalid red border on inputs, destructive variant on delete modal.',
      status: 'done',
      commit: 'c82625e',
    },
    {
      id: 'BL-UI-POLISH-002',
      description:
        'Breadcrumbs in AppTopbar (Design 2.0): usePageBreadcrumbs hook + zustand store feeding Home icon + chevron-separated segments. Only rendered inside AppLayout; i18n breadcrumbs.* keys.',
      status: 'done',
      commit: '202c3df',
    },
    {
      id: 'BL-WS-UI-002',
      description:
        'DeleteConfirmDialog polish per modal_delete.jpg: AlertTriangle danger pill, max-w-md + p-7 padding, full-width action buttons, Delete gated behind per-workspace acknowledge checkbox.',
      status: 'done',
      commit: 'bc3d98c',
    },
  ],
};
