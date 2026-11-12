import { ChevronDown, ChevronLeft, ChevronRight, LogOut, Pin, Star } from 'lucide-react';
import { Fragment, useMemo } from 'react';
import { useTranslation } from 'react-i18next';
import { NavLink, useNavigate } from 'react-router-dom';

import { useLogout } from '@/features/auth/api/useLogout';
import { Logo } from '@/features/branding/Logo';
import { useMenu } from '@/features/menu/api/useMenu';
import type { MenuItem, MenuSection } from '@/features/menu/types';
import { LanguageSwitcher } from '@/features/settings/LanguageSwitcher';
import { ThemeToggle } from '@/features/theme/ThemeToggle';

import { resolveIcon } from './iconMap';
import { OrgIdHeader } from './OrgIdHeader';
import { ProfileDropdown } from './ProfileDropdown';
import { ProjectContextSection } from './ProjectContextSection';
import { PINNED_MENU_LIMIT, usePinnedMenu } from './usePinnedMenu';
import { useCollapsedSections, useSidebarCollapsed } from './useSidebarState';
import { WorkspaceContextSection } from './WorkspaceContextSection';
import { WorkspaceSwitcher } from './WorkspaceSwitcher';

const WORKSPACE_CONTEXT_ORDER = 50;

const PINNED_SECTION_ID = 'pinned';

function flattenPinned(sections: MenuSection[] | undefined, pinnedIds: string[]): MenuItem[] {
  if (!sections) return [];
  const byId = new Map<string, MenuItem>();
  for (const section of sections) {
    for (const item of section.items) {
      byId.set(item.id, item);
    }
  }
  return pinnedIds.map((id) => byId.get(id)).filter((item): item is MenuItem => item !== undefined);
}

export function Sidebar() {
  const { t } = useTranslation();
  const navigate = useNavigate();
  const [collapsed, setCollapsed] = useSidebarCollapsed();
  const sections = useCollapsedSections();
  const menu = useMenu();
  const pins = usePinnedMenu();
  const logout = useLogout();

  const widthClass = collapsed ? 'w-16' : 'w-64';
  const pinnedItems = useMemo(
    () => flattenPinned(menu.data, pins.pinned),
    [menu.data, pins.pinned],
  );
  const showPinnedSection = !collapsed && pinnedItems.length > 0;
  const isPinnedSectionCollapsed = sections.isCollapsed(PINNED_SECTION_ID);

  function handleLogout() {
    logout.mutate(undefined, {
      onSettled: () => navigate('/login', { replace: true }),
    });
  }

  function renderItem(item: MenuItem, options: { showStar: boolean }) {
    const Icon = resolveIcon(item.icon);
    const pinned = pins.isPinned(item.id);
    const canToggle = pinned || pins.canPinMore;
    const title = pinned ? t('menu.pinRemove') : t('menu.pinAdd');
    const label = t(item.labelKey);

    if (item.disabledMilestoneKey) {
      const milestoneLabel = t(item.disabledMilestoneKey);
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
    }

    function handleTogglePin(event: React.MouseEvent<HTMLButtonElement>) {
      event.preventDefault();
      event.stopPropagation();
      if (canToggle) {
        pins.toggle(item.id);
      }
    }

    return (
      <li key={item.id} className="group relative">
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
        {options.showStar && !collapsed ? (
          <button
            type="button"
            onClick={handleTogglePin}
            disabled={!canToggle}
            aria-label={title}
            aria-pressed={pinned}
            title={canToggle ? title : t('menu.pinLimit', { limit: PINNED_MENU_LIMIT })}
            className={`absolute right-2 top-1/2 -translate-y-1/2 inline-flex h-6 w-6 items-center justify-center rounded-md transition-opacity ${
              pinned
                ? 'opacity-100 text-sc-primary hover:bg-sc-bg-card'
                : 'opacity-0 text-sc-text-muted group-hover:opacity-100 hover:bg-sc-bg-card hover:text-sc-text'
            } disabled:cursor-not-allowed disabled:opacity-30`}
          >
            <Star size={13} fill={pinned ? 'currentColor' : 'none'} aria-hidden />
          </button>
        ) : null}
      </li>
    );
  }

  return (
    <aside
      className={`${widthClass} flex h-screen flex-col border-r border-sc-border bg-sc-bg-secondary transition-[width] duration-200`}
    >
      <div className="flex items-center justify-between gap-2 px-3 py-3">
        <div className={`flex items-center gap-2 ${collapsed ? 'mx-auto' : ''}`}>
          <Logo size={28} />
          {!collapsed ? (
            <span className="text-sm font-semibold tracking-tight text-sc-text">StellarCrew</span>
          ) : null}
        </div>
        <button
          type="button"
          onClick={() => setCollapsed(!collapsed)}
          aria-label={collapsed ? t('sidebar.expand') : t('sidebar.collapse')}
          title={collapsed ? t('sidebar.expand') : t('sidebar.collapse')}
          className="inline-flex h-7 w-7 items-center justify-center rounded-md text-sc-text-muted transition-colors hover:bg-sc-bg-card hover:text-sc-text"
        >
          {collapsed ? (
            <ChevronRight size={16} aria-hidden />
          ) : (
            <ChevronLeft size={16} aria-hidden />
          )}
        </button>
      </div>

      <div className={`px-3 ${collapsed ? 'py-1' : 'pt-0 pb-2'}`}>
        <OrgIdHeader collapsed={collapsed} />
      </div>

      <div className={`border-b border-sc-border px-3 ${collapsed ? 'py-2' : 'pt-0 pb-3'}`}>
        <WorkspaceSwitcher collapsed={collapsed} />
      </div>

      <nav className="flex-1 overflow-y-auto px-2 py-2">
        {menu.isPending ? (
          <p className="px-3 py-2 text-xs text-sc-text-muted">{t('sidebar.loading')}</p>
        ) : null}
        {menu.isError ? (
          <p className="px-3 py-2 text-xs text-red-600">{t('sidebar.loadError')}</p>
        ) : null}

        {showPinnedSection ? (
          <div className="mb-3">
            <button
              type="button"
              onClick={() => sections.toggle(PINNED_SECTION_ID)}
              aria-expanded={!isPinnedSectionCollapsed}
              className="flex w-full items-center justify-between px-3 pt-2 pb-1 text-[11px] font-semibold uppercase tracking-widest text-sc-text-muted transition-colors hover:text-sc-text"
            >
              <span className="flex items-center gap-1.5">
                <Pin size={11} aria-hidden />
                {t('menu.sections.pinned')}
              </span>
              <ChevronDown
                size={12}
                className={`transition-transform ${isPinnedSectionCollapsed ? '-rotate-90' : ''}`}
                aria-hidden
              />
            </button>
            {!isPinnedSectionCollapsed ? (
              <ul className="flex flex-col gap-0.5">
                {pinnedItems.map((item) => renderItem(item, { showStar: true }))}
              </ul>
            ) : null}
          </div>
        ) : null}

        {menu.data?.map((section, index) => {
          const isSectionCollapsed = sections.isCollapsed(section.id);
          const separatorClass =
            index === 0 && !showPinnedSection ? '' : 'mt-3 border-t border-sc-border pt-3';
          const previousOrder = index > 0 ? menu.data[index - 1].order : 0;
          const injectContextBefore =
            previousOrder < WORKSPACE_CONTEXT_ORDER && section.order >= WORKSPACE_CONTEXT_ORDER;
          return (
            <Fragment key={section.id}>
              {injectContextBefore ? (
                <>
                  <WorkspaceContextSection collapsed={collapsed} />
                  <ProjectContextSection collapsed={collapsed} />
                </>
              ) : null}
              <div className={`mb-3 ${separatorClass}`}>
                {!collapsed ? (
                  <button
                    type="button"
                    onClick={() => sections.toggle(section.id)}
                    aria-expanded={!isSectionCollapsed}
                    className="flex w-full items-center justify-between px-3 pt-2 pb-1 text-[11px] font-semibold uppercase tracking-widest text-sc-text-muted transition-colors hover:text-sc-text"
                  >
                    <span>{t(section.labelKey)}</span>
                    <ChevronDown
                      size={12}
                      className={`transition-transform ${isSectionCollapsed ? '-rotate-90' : ''}`}
                      aria-hidden
                    />
                  </button>
                ) : null}
                {!isSectionCollapsed || collapsed ? (
                  <ul className="flex flex-col gap-0.5">
                    {section.items.map((item) => renderItem(item, { showStar: true }))}
                  </ul>
                ) : null}
              </div>
            </Fragment>
          );
        })}
        {menu.data && menu.data.every((section) => section.order < WORKSPACE_CONTEXT_ORDER) ? (
          <>
            <WorkspaceContextSection collapsed={collapsed} />
            <ProjectContextSection collapsed={collapsed} />
          </>
        ) : null}
      </nav>

      <div className="flex flex-col gap-2 border-t border-sc-border px-3 py-3">
        <ProfileDropdown collapsed={collapsed} />
        <div className={`flex items-center gap-2 ${collapsed ? 'flex-col' : 'justify-between'}`}>
          {!collapsed ? <LanguageSwitcher /> : null}
          <ThemeToggle />
        </div>
        <button
          type="button"
          onClick={handleLogout}
          disabled={logout.isPending}
          aria-label={t('sidebar.logout')}
          title={t('sidebar.logout')}
          className="inline-flex w-full items-center justify-center gap-2 rounded-md border border-sc-border bg-sc-bg-card px-3 py-1.5 text-xs font-semibold text-sc-text transition-colors hover:bg-sc-bg disabled:opacity-60"
        >
          <LogOut size={14} aria-hidden />
          {!collapsed ? <span>{t('sidebar.logout')}</span> : null}
        </button>
      </div>
    </aside>
  );
}
