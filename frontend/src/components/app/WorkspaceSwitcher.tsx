import { ChevronsUpDown, Loader2, Settings2 } from 'lucide-react';
import { useEffect, useRef, useState } from 'react';
import { useTranslation } from 'react-i18next';
import { NavLink } from 'react-router-dom';

import { RolePill } from '@/features/user-management/components/RolePill';
import { useWorkspacesList } from '@/features/workspaces/api/useWorkspaces';
import { useCurrentWorkspace } from '@/features/workspaces/state/useCurrentWorkspace';
import { findColorOption, type Workspace } from '@/features/workspaces/types';

interface WorkspaceSwitcherProps {
  collapsed: boolean;
}

export function WorkspaceSwitcher({ collapsed }: WorkspaceSwitcherProps) {
  const { t } = useTranslation();
  const list = useWorkspacesList();
  const currentId = useCurrentWorkspace((state) => state.currentWorkspaceId);
  const setCurrentWorkspaceId = useCurrentWorkspace((state) => state.setCurrentWorkspaceId);
  const [open, setOpen] = useState(false);
  const containerRef = useRef<HTMLDivElement>(null);

  const workspaces = list.data ?? [];
  const current = workspaces.find((workspace) => workspace.id === currentId) ?? null;

  useEffect(() => {
    if (!open) return;
    function handleClickOutside(event: MouseEvent) {
      const target = event.target;
      if (!(target instanceof Node)) return;
      if (containerRef.current && !containerRef.current.contains(target)) {
        setOpen(false);
      }
    }
    function handleKeyDown(event: KeyboardEvent) {
      if (event.key === 'Escape') setOpen(false);
    }
    document.addEventListener('mousedown', handleClickOutside);
    document.addEventListener('keydown', handleKeyDown);
    return () => {
      document.removeEventListener('mousedown', handleClickOutside);
      document.removeEventListener('keydown', handleKeyDown);
    };
  }, [open]);

  function handleSelect(workspace: Workspace) {
    setCurrentWorkspaceId(workspace.id);
    setOpen(false);
  }

  if (collapsed) {
    return (
      <CollapsedTile
        current={current}
        loading={list.isPending}
        loadingLabel={t('workspaceSwitcher.loading')}
        emptyLabel={t('workspaceSwitcher.collapsedEmpty')}
      />
    );
  }

  const displayName = current ? current.name : t('workspaceSwitcher.empty');

  return (
    <div ref={containerRef} className="relative mt-2">
      <button
        type="button"
        aria-haspopup="menu"
        aria-expanded={open}
        onClick={() => setOpen((previous) => !previous)}
        disabled={list.isPending}
        className="flex w-full items-center justify-between gap-2 rounded-md border border-sc-border bg-sc-bg-card px-3 py-2 text-left text-sm font-medium text-sc-text transition-colors hover:border-sc-text-muted disabled:cursor-wait disabled:opacity-70"
      >
        <span className="flex min-w-0 items-center gap-2">
          <WorkspaceIconSmall workspace={current} />
          <span className="flex min-w-0 flex-col">
            <span className="truncate text-xs text-sc-text-muted">
              {t('workspaceSwitcher.label')}
            </span>
            <span className="truncate text-sm font-semibold text-sc-text">{displayName}</span>
            {current?.role ? (
              <span className="mt-0.5">
                <RolePill role={current.role} size="sm" />
              </span>
            ) : null}
          </span>
        </span>
        {list.isPending ? (
          <Loader2 size={14} className="flex-none animate-spin text-sc-text-muted" aria-hidden />
        ) : (
          <ChevronsUpDown size={14} className="flex-none text-sc-text-muted" aria-hidden />
        )}
      </button>

      {open ? (
        <div
          role="menu"
          aria-label={t('workspaceSwitcher.menuAria')}
          className="absolute left-0 right-0 top-full z-20 mt-1 overflow-hidden rounded-md border border-sc-border bg-sc-bg-card shadow-lg"
        >
          {workspaces.length > 0 ? (
            <ul className="flex flex-col py-1">
              {workspaces.map((workspace) => {
                const isActive = workspace.id === currentId;
                return (
                  <li key={workspace.id}>
                    <button
                      type="button"
                      role="menuitemradio"
                      aria-checked={isActive}
                      onClick={() => handleSelect(workspace)}
                      className={`flex w-full items-center gap-2 px-3 py-2 text-left text-sm transition-colors ${
                        isActive
                          ? 'bg-sc-bg-secondary font-semibold text-sc-text'
                          : 'text-sc-text-muted hover:bg-sc-bg-secondary hover:text-sc-text'
                      }`}
                    >
                      <WorkspaceIconSmall workspace={workspace} />
                      <span className="min-w-0 flex-1 truncate">{workspace.name}</span>
                      {workspace.role ? <RolePill role={workspace.role} size="sm" /> : null}
                    </button>
                  </li>
                );
              })}
            </ul>
          ) : null}
          <div className={workspaces.length > 0 ? 'border-t border-sc-border py-1' : 'py-1'}>
            <NavLink
              to="/workspaces"
              role="menuitem"
              onClick={() => setOpen(false)}
              className="flex items-center gap-2 px-3 py-2 text-sm text-sc-text-muted transition-colors hover:bg-sc-bg-secondary hover:text-sc-text"
            >
              <Settings2 size={14} aria-hidden />
              <span className="truncate">{t('workspaceSwitcher.manage')}</span>
            </NavLink>
          </div>
        </div>
      ) : null}
    </div>
  );
}

interface CollapsedTileProps {
  current: Workspace | null;
  loading: boolean;
  loadingLabel: string;
  emptyLabel: string;
}

function CollapsedTile({ current, loading, loadingLabel, emptyLabel }: CollapsedTileProps) {
  if (loading) {
    return (
      <div
        className="mx-auto mt-2 flex h-8 w-8 items-center justify-center rounded-md border border-sc-border bg-sc-bg-card text-sc-text-muted"
        title={loadingLabel}
        aria-label={loadingLabel}
      >
        <Loader2 size={14} className="animate-spin" aria-hidden />
      </div>
    );
  }

  if (current === null) {
    return (
      <div
        className="mx-auto mt-2 flex h-8 w-8 items-center justify-center rounded-md border border-dashed border-sc-text-muted text-xs font-semibold text-sc-text-muted"
        title={emptyLabel}
        aria-label={emptyLabel}
      >
        ?
      </div>
    );
  }

  const color = findColorOption(current.iconColor);
  return (
    <div
      className={`mx-auto mt-2 flex h-8 w-8 items-center justify-center rounded-md text-xs font-semibold ${color.bgClass} ${color.textClass}`}
      title={current.name}
      aria-label={current.name}
    >
      {current.iconInitials}
    </div>
  );
}

interface WorkspaceIconSmallProps {
  workspace: Workspace | null;
}

function WorkspaceIconSmall({ workspace }: WorkspaceIconSmallProps) {
  if (workspace === null) {
    return (
      <span
        className="inline-flex h-6 w-6 flex-none items-center justify-center rounded-md border border-dashed border-sc-text-muted text-xs font-semibold text-sc-text-muted"
        aria-hidden
      >
        ?
      </span>
    );
  }
  const color = findColorOption(workspace.iconColor);
  return (
    <span
      className={`inline-flex h-6 w-6 flex-none items-center justify-center rounded-md text-xs font-semibold ${color.bgClass} ${color.textClass}`}
      aria-hidden
    >
      {workspace.iconInitials}
    </span>
  );
}
