import {
  Bot,
  Calendar,
  ChevronDown,
  FolderKanban,
  LayoutDashboard,
  Settings,
  Users,
  type LucideIcon,
} from 'lucide-react';
import { useTranslation } from 'react-i18next';
import { NavLink, useMatch } from 'react-router-dom';

import { useCollapsedSections } from './useSidebarState';

const SECTION_ID = 'workspace-context';

interface WorkspaceContextSectionProps {
  collapsed: boolean;
}

interface WorkspaceContextItem {
  id: string;
  labelKey: string;
  icon: LucideIcon;
  path?: string;
  disabledMilestoneKey?: string;
}

export function WorkspaceContextSection({ collapsed }: WorkspaceContextSectionProps) {
  const { t } = useTranslation();
  const sections = useCollapsedSections();
  const match = useMatch('/workspace/:wsId/*');
  const wsId = match?.params.wsId ?? null;

  if (wsId === null) {
    return null;
  }

  const isSectionCollapsed = sections.isCollapsed(SECTION_ID);

  const items: WorkspaceContextItem[] = [
    {
      id: 'workspace.overview',
      labelKey: 'menu.items.workspaceOverview',
      icon: LayoutDashboard,
      path: `/workspace/${wsId}/overview`,
    },
    {
      id: 'workspace.projects',
      labelKey: 'menu.items.workspaceProjects',
      icon: FolderKanban,
      path: `/workspace/${wsId}/projects`,
    },
    {
      id: 'workspace.members',
      labelKey: 'menu.items.workspaceMembers',
      icon: Users,
      path: `/workspace/${wsId}/settings/members`,
    },
    {
      id: 'workspace.agents',
      labelKey: 'menu.items.workspaceAgents',
      icon: Bot,
      disabledMilestoneKey: 'menu.workspaceContext.milestones.agents',
    },
    {
      id: 'workspace.scheduler',
      labelKey: 'menu.items.workspaceScheduler',
      icon: Calendar,
      disabledMilestoneKey: 'menu.workspaceContext.milestones.scheduler',
    },
    {
      id: 'workspace.settings',
      labelKey: 'menu.items.workspaceSettings',
      icon: Settings,
      disabledMilestoneKey: 'menu.workspaceContext.milestones.settings',
    },
  ];

  return (
    <div className="mb-3 mt-3 border-t border-sc-border pt-3">
      {!collapsed ? (
        <button
          type="button"
          onClick={() => sections.toggle(SECTION_ID)}
          aria-expanded={!isSectionCollapsed}
          className="flex w-full items-center justify-between px-3 pt-2 pb-1 text-[11px] font-semibold uppercase tracking-widest text-sc-text-muted transition-colors hover:text-sc-text"
        >
          <span>{t('menu.sections.workspace')}</span>
          <ChevronDown
            size={12}
            className={`transition-transform ${isSectionCollapsed ? '-rotate-90' : ''}`}
            aria-hidden
          />
        </button>
      ) : null}
      {!isSectionCollapsed || collapsed ? (
        <ul className="flex flex-col gap-0.5">
          {items.map((item) => {
            const Icon = item.icon;
            const label = t(item.labelKey);
            if (item.path !== undefined) {
              return (
                <li key={item.id} className="relative">
                  <NavLink
                    to={item.path}
                    title={collapsed ? label : undefined}
                    className={({ isActive }) =>
                      `relative flex items-center gap-3 rounded-md px-3 py-2 text-sm transition-colors ${
                        collapsed ? 'justify-center' : ''
                      } ${
                        isActive
                          ? 'bg-sc-bg-card font-semibold text-sc-text before:absolute before:inset-y-1 before:left-0 before:w-0.5 before:rounded-full before:bg-sc-primary'
                          : 'text-sc-text-muted hover:bg-sc-bg-card hover:text-sc-text'
                      }`
                    }
                  >
                    <Icon size={16} aria-hidden />
                    {!collapsed ? <span className="flex-1 truncate">{label}</span> : null}
                  </NavLink>
                </li>
              );
            }
            const milestoneLabel = item.disabledMilestoneKey ? t(item.disabledMilestoneKey) : '';
            const tooltip = t('menu.workspaceContext.comingSoon', { milestone: milestoneLabel });
            return (
              <li key={item.id} className="relative">
                <span
                  aria-disabled="true"
                  title={collapsed ? `${label} — ${tooltip}` : tooltip}
                  className={`relative flex cursor-not-allowed items-center gap-3 rounded-md px-3 py-2 text-sm text-sc-text-muted opacity-60 ${
                    collapsed ? 'justify-center' : ''
                  }`}
                >
                  <Icon size={16} aria-hidden />
                  {!collapsed ? <span className="flex-1 truncate">{label}</span> : null}
                </span>
              </li>
            );
          })}
        </ul>
      ) : null}
    </div>
  );
}
