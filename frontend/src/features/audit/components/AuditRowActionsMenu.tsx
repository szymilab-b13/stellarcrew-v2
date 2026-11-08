import { Eye, MoreHorizontal, RotateCcw } from 'lucide-react';
import { useEffect, useLayoutEffect, useRef, useState } from 'react';
import { createPortal } from 'react-dom';
import { useTranslation } from 'react-i18next';

import type { AuditEntry } from '../types';

interface AuditRowActionsMenuProps {
  entry: AuditEntry;
  canUndo: boolean;
  onDetailsClick: (entry: AuditEntry) => void;
  onUndoClick: (entry: AuditEntry) => void;
}

interface MenuPosition {
  top: number;
  right: number;
}

const MENU_OFFSET = 4;

export function AuditRowActionsMenu({
  entry,
  canUndo,
  onDetailsClick,
  onUndoClick,
}: AuditRowActionsMenuProps) {
  const { t } = useTranslation();
  const triggerRef = useRef<HTMLButtonElement>(null);
  const menuRef = useRef<HTMLDivElement>(null);
  const [open, setOpen] = useState(false);
  const [menuPosition, setMenuPosition] = useState<MenuPosition | null>(null);

  useLayoutEffect(() => {
    if (!open) {
      setMenuPosition(null);
      return;
    }
    function updatePosition() {
      const rect = triggerRef.current?.getBoundingClientRect();
      if (!rect) return;
      setMenuPosition({
        top: rect.bottom + MENU_OFFSET,
        right: window.innerWidth - rect.right,
      });
    }
    updatePosition();
    window.addEventListener('scroll', updatePosition, true);
    window.addEventListener('resize', updatePosition);
    return () => {
      window.removeEventListener('scroll', updatePosition, true);
      window.removeEventListener('resize', updatePosition);
    };
  }, [open]);

  useEffect(() => {
    if (!open) return;
    function handleClickOutside(event: MouseEvent) {
      const target = event.target as Node;
      if (triggerRef.current?.contains(target)) return;
      if (menuRef.current?.contains(target)) return;
      setOpen(false);
    }
    document.addEventListener('mousedown', handleClickOutside);
    return () => document.removeEventListener('mousedown', handleClickOutside);
  }, [open]);

  function handleDetails() {
    setOpen(false);
    onDetailsClick(entry);
  }

  function handleUndo() {
    setOpen(false);
    onUndoClick(entry);
  }

  const menu =
    open && menuPosition ? (
      <div
        ref={menuRef}
        role="menu"
        style={{ position: 'fixed', top: menuPosition.top, right: menuPosition.right }}
        className="z-50 w-48 overflow-hidden rounded-md border border-sc-border bg-sc-bg-card shadow-lg"
      >
        <button
          type="button"
          role="menuitem"
          onClick={handleDetails}
          className="flex w-full items-center gap-2 px-3 py-2 text-left text-sm text-sc-text transition-colors hover:bg-sc-bg-secondary"
        >
          <Eye size={14} aria-hidden />
          {t('audit.row.actionDetails')}
        </button>
        <button
          type="button"
          role="menuitem"
          onClick={handleUndo}
          disabled={!canUndo}
          className="flex w-full items-center gap-2 px-3 py-2 text-left text-sm text-sc-text transition-colors hover:bg-sc-bg-secondary disabled:cursor-not-allowed disabled:text-sc-text-muted"
        >
          <RotateCcw size={14} aria-hidden />
          {t('audit.row.actionUndo')}
        </button>
      </div>
    ) : null;

  return (
    <>
      <button
        ref={triggerRef}
        type="button"
        onClick={() => setOpen((previous) => !previous)}
        className="inline-flex items-center gap-2 rounded-lg px-2 py-1.5 text-sm transition-colors hover:bg-sc-bg-card"
        aria-haspopup="menu"
        aria-expanded={open}
        aria-label={t('audit.row.actionsAria')}
      >
        <MoreHorizontal size={16} className="text-sc-text-muted" aria-hidden />
      </button>

      {menu ? createPortal(menu, document.body) : null}
    </>
  );
}
