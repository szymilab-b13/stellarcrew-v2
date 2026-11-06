import { ChevronDown, LogOut } from 'lucide-react';
import { useEffect, useRef, useState } from 'react';
import { useTranslation } from 'react-i18next';
import { useNavigate } from 'react-router-dom';

import { useLogout } from '@/features/auth/api/useLogout';
import type { AuthUser } from '@/features/auth/types';

interface UserMenuProps {
  user: AuthUser;
}

export function UserMenu({ user }: UserMenuProps) {
  const { t } = useTranslation();
  const navigate = useNavigate();
  const logout = useLogout();
  const [open, setOpen] = useState(false);
  const containerRef = useRef<HTMLDivElement>(null);

  const displayName = user.displayName ?? user.email;
  const initial = displayName.charAt(0).toUpperCase() || '?';

  useEffect(() => {
    if (!open) return;
    function handleClickOutside(event: MouseEvent) {
      if (!containerRef.current?.contains(event.target as Node)) {
        setOpen(false);
      }
    }
    document.addEventListener('mousedown', handleClickOutside);
    return () => document.removeEventListener('mousedown', handleClickOutside);
  }, [open]);

  function handleLogout() {
    setOpen(false);
    logout.mutate(undefined, {
      onSettled: () => navigate('/login', { replace: true }),
    });
  }

  return (
    <div ref={containerRef} className="relative">
      <button
        type="button"
        onClick={() => setOpen((previous) => !previous)}
        className="inline-flex items-center gap-2 rounded-lg px-2 py-1.5 text-sm transition-colors hover:bg-sc-bg-card"
        aria-haspopup="menu"
        aria-expanded={open}
      >
        <span className="inline-flex h-7 w-7 items-center justify-center rounded-full bg-sc-primary text-xs font-semibold text-sc-primary-foreground">
          {initial}
        </span>
        <span className="hidden text-sm font-medium text-sc-text sm:inline">{displayName}</span>
        <ChevronDown size={14} className="text-sc-text-muted" aria-hidden />
      </button>

      {open ? (
        <div
          role="menu"
          className="absolute right-0 top-full z-20 mt-2 w-56 overflow-hidden rounded-md border border-sc-border bg-sc-bg-card shadow-lg"
        >
          <div className="border-b border-sc-border px-3 py-2">
            <p className="truncate text-sm font-semibold text-sc-text" title={displayName}>
              {displayName}
            </p>
            <p className="truncate text-xs text-sc-text-muted" title={user.email}>
              {user.email}
            </p>
          </div>
          <button
            type="button"
            role="menuitem"
            onClick={handleLogout}
            disabled={logout.isPending}
            className="flex w-full items-center gap-2 px-3 py-2 text-left text-sm text-sc-text transition-colors hover:bg-sc-bg-secondary disabled:opacity-60"
          >
            <LogOut size={14} aria-hidden />
            {t('topbar.signOut')}
          </button>
        </div>
      ) : null}
    </div>
  );
}
