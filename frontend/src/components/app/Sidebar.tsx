import { ChevronDown, ChevronLeft, ChevronRight, LogOut } from 'lucide-react';
import { useTranslation } from 'react-i18next';
import { NavLink, useNavigate } from 'react-router-dom';

import { useLogout } from '@/features/auth/api/useLogout';
import { Logo } from '@/features/branding/Logo';
import { useMenu } from '@/features/menu/api/useMenu';
import { LanguageSwitcher } from '@/features/settings/LanguageSwitcher';
import { ThemeToggle } from '@/features/theme/ThemeToggle';

import { resolveIcon } from './iconMap';
import { useCollapsedSections, useSidebarCollapsed } from './useSidebarState';
import { WorkspaceSwitcher } from './WorkspaceSwitcher';

export function Sidebar() {
  const { t } = useTranslation();
  const navigate = useNavigate();
  const [collapsed, setCollapsed] = useSidebarCollapsed();
  const sections = useCollapsedSections();
  const menu = useMenu();
  const logout = useLogout();

  const widthClass = collapsed ? 'w-16' : 'w-64';

  function handleLogout() {
    logout.mutate(undefined, {
      onSettled: () => navigate('/login', { replace: true }),
    });
  }

  return (
    <aside
      className={`${widthClass} flex h-screen flex-col border-r border-sc-border bg-sc-bg-secondary transition-[width] duration-200`}
    >
      <div className="flex items-center justify-between gap-2 border-b border-sc-border px-3 py-3">
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

      <div className={`px-3 ${collapsed ? 'py-2' : 'py-3'}`}>
        <WorkspaceSwitcher collapsed={collapsed} />
      </div>

      <nav className="flex-1 overflow-y-auto px-2 py-2">
        {menu.isPending ? (
          <p className="px-3 py-2 text-xs text-sc-text-muted">{t('sidebar.loading')}</p>
        ) : null}
        {menu.isError ? (
          <p className="px-3 py-2 text-xs text-red-600">{t('sidebar.loadError')}</p>
        ) : null}
        {menu.data?.map((section, index) => {
          const isSectionCollapsed = sections.isCollapsed(section.id);
          const separatorClass = index === 0 ? '' : 'mt-3 border-t border-sc-border pt-3';
          return (
            <div key={section.id} className={`mb-3 ${separatorClass}`}>
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
                  {section.items.map((item) => {
                    const Icon = resolveIcon(item.icon);
                    return (
                      <li key={item.id}>
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
                          {!collapsed ? <span>{t(item.labelKey)}</span> : null}
                        </NavLink>
                      </li>
                    );
                  })}
                </ul>
              ) : null}
            </div>
          );
        })}
      </nav>

      <div className="flex flex-col gap-2 border-t border-sc-border px-3 py-3">
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
