import { ChevronsUpDown, User } from 'lucide-react';
import { useEffect, useRef, useState } from 'react';
import { useTranslation } from 'react-i18next';

import { useMe } from '@/features/auth/api/useMe';
import { RolePill } from '@/features/user-management/components/RolePill';
import { useWorkspacesList } from '@/features/workspaces/api/useWorkspaces';
import { findColorOption, type Workspace } from '@/features/workspaces/types';

interface ProfileDropdownProps {
  collapsed: boolean;
}

export function ProfileDropdown({ collapsed }: ProfileDropdownProps) {
  const { t } = useTranslation();
  const me = useMe();
  const workspaces = useWorkspacesList();
  const [open, setOpen] = useState(false);
  const containerRef = useRef<HTMLDivElement>(null);

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

  const email = me.data?.email ?? '';
  const displayName = me.data?.displayName ?? email;
  const triggerLabel = email.length > 0 ? email : t('profileDropdown.trigger');
  const list = workspaces.data ?? [];

  return (
    <div ref={containerRef} className="relative">
      <button
        type="button"
        aria-haspopup="menu"
        aria-expanded={open}
        aria-label={triggerLabel}
        onClick={() => setOpen((previous) => !previous)}
        className={`flex w-full items-center ${collapsed ? 'justify-center' : 'justify-between'} gap-2 rounded-md border border-sc-border bg-sc-bg-card px-3 py-2 text-left text-sm text-sc-text transition-colors hover:border-sc-text-muted`}
      >
        <span className="flex min-w-0 items-center gap-2">
          <span
            className="inline-flex h-7 w-7 flex-none items-center justify-center rounded-full bg-sc-bg-secondary text-sc-text-muted"
            aria-hidden
          >
            <User size={14} />
          </span>
          {!collapsed ? (
            <span className="truncate text-sm font-medium text-sc-text">{displayName}</span>
          ) : null}
        </span>
        {!collapsed ? (
          <ChevronsUpDown size={14} className="flex-none text-sc-text-muted" aria-hidden />
        ) : null}
      </button>

      {open ? (
        <div
          role="menu"
          aria-label={t('profileDropdown.menuAria')}
          className="absolute bottom-full left-0 right-0 z-30 mb-2 overflow-hidden rounded-md border border-sc-border bg-sc-bg-card shadow-lg"
        >
          {email.length > 0 ? (
            <div className="border-b border-sc-border px-3 py-2 text-xs text-sc-text-muted">
              {email}
            </div>
          ) : null}
          <div className="px-3 py-2 text-[11px] font-semibold uppercase tracking-widest text-sc-text-muted">
            {t('profileDropdown.myWorkspacesHeading')}
          </div>
          {list.length > 0 ? (
            <ul className="flex max-h-64 flex-col overflow-y-auto py-1">
              {list.map((workspace) => (
                <li key={workspace.id}>
                  <WorkspaceRow workspace={workspace} />
                </li>
              ))}
            </ul>
          ) : (
            <p className="px-3 py-3 text-xs text-sc-text-muted">
              {t('profileDropdown.noWorkspaces')}
            </p>
          )}
        </div>
      ) : null}
    </div>
  );
}

function WorkspaceRow({ workspace }: { workspace: Workspace }) {
  const color = findColorOption(workspace.iconColor);
  return (
    <div className="flex items-center gap-2 px-3 py-2 text-sm text-sc-text">
      <span
        className={`inline-flex h-6 w-6 flex-none items-center justify-center rounded-md text-xs font-semibold ${color.bgClass} ${color.textClass}`}
        aria-hidden
      >
        {workspace.iconInitials}
      </span>
      <span className="min-w-0 flex-1 truncate">{workspace.name}</span>
      {workspace.role ? <RolePill role={workspace.role} size="sm" /> : null}
    </div>
  );
}
