import { ChevronDown, LayoutDashboard, Users } from 'lucide-react';
import { useTranslation } from 'react-i18next';
import { NavLink, useMatch } from 'react-router-dom';

import { useCollapsedSections } from './useSidebarState';

const SECTION_ID = 'workspace-context';

interface WorkspaceContextSectionProps {
  collapsed: boolean;
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

  const items = [
    {
      id: 'workspace.overview',
      labelKey: 'menu.items.workspaceOverview',
      icon: LayoutDashboard,
      path: `/workspace/${wsId}/overview`,
    },
    {
      id: 'workspace.members',
      labelKey: 'menu.items.workspaceMembers',
      icon: Users,
      path: `/workspace/${wsId}/settings/members`,
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
            return (
              <li key={item.id} className="relative">
                <NavLink
                  to={item.path}
                  title={collapsed ? t(item.labelKey) : undefined}
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
                  {!collapsed ? <span className="flex-1 truncate">{t(item.labelKey)}</span> : null}
                </NavLink>
              </li>
            );
          })}
        </ul>
      ) : null}
    </div>
  );
}
