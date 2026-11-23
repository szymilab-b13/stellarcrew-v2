import { Copy, Eye, MoreHorizontal, Trash2 } from 'lucide-react';
import { useEffect, useLayoutEffect, useRef, useState } from 'react';
import { createPortal } from 'react-dom';
import { useTranslation } from 'react-i18next';
import { useNavigate } from 'react-router-dom';

import { useRevokeWorker } from '../api/useRevokeWorker';

import { RevokeWorkerDialog } from './RevokeWorkerDialog';

interface WorkerRowActionsMenuProps {
  workerId: string;
  workerName: string;
}

interface MenuPosition {
  top: number;
  right: number;
}

const MENU_OFFSET = 4;

export function WorkerRowActionsMenu({ workerId, workerName }: WorkerRowActionsMenuProps) {
  const { t } = useTranslation();
  const navigate = useNavigate();
  const triggerRef = useRef<HTMLButtonElement>(null);
  const menuRef = useRef<HTMLDivElement>(null);
  const [open, setOpen] = useState(false);
  const [menuPosition, setMenuPosition] = useState<MenuPosition | null>(null);
  const [revokeDialogOpen, setRevokeDialogOpen] = useState(false);
  const [copied, setCopied] = useState(false);

  const revoke = useRevokeWorker();

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

  useEffect(() => {
    if (!copied) return;
    const timeout = window.setTimeout(() => setCopied(false), 1500);
    return () => window.clearTimeout(timeout);
  }, [copied]);

  function openDetails() {
    setOpen(false);
    navigate(`/settings/workers/${workerId}`);
  }

  async function copyWorkerId() {
    try {
      await navigator.clipboard.writeText(workerId);
      setCopied(true);
    } catch {
      setCopied(false);
    }
    setOpen(false);
  }

  function openRevokeDialog() {
    setOpen(false);
    revoke.reset();
    setRevokeDialogOpen(true);
  }

  function closeRevokeDialog() {
    setRevokeDialogOpen(false);
    revoke.reset();
  }

  function confirmRevoke() {
    revoke.mutate(workerId, {
      onSuccess: () => setRevokeDialogOpen(false),
    });
  }

  const menu =
    open && menuPosition ? (
      <div
        ref={menuRef}
        role="menu"
        style={{ position: 'fixed', top: menuPosition.top, right: menuPosition.right }}
        className="z-50 w-56 overflow-hidden rounded-md border border-sc-border bg-sc-bg-card shadow-lg"
      >
        <button
          type="button"
          role="menuitem"
          onClick={openDetails}
          className="flex w-full items-center gap-2 px-3 py-2 text-left text-sm text-sc-text transition-colors hover:bg-sc-bg-secondary"
        >
          <Eye size={14} aria-hidden />
          {t('workers.list.rowActions.details')}
        </button>
        <button
          type="button"
          role="menuitem"
          onClick={() => void copyWorkerId()}
          className="flex w-full items-center gap-2 px-3 py-2 text-left text-sm text-sc-text transition-colors hover:bg-sc-bg-secondary"
        >
          <Copy size={14} aria-hidden />
          {t('workers.list.rowActions.copyId')}
        </button>
        <button
          type="button"
          role="menuitem"
          onClick={openRevokeDialog}
          className="flex w-full items-center gap-2 px-3 py-2 text-left text-sm text-sc-danger transition-colors hover:bg-sc-danger/10"
        >
          <Trash2 size={14} aria-hidden />
          {t('workers.list.rowActions.revoke')}
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
        aria-label={t('workers.list.rowActions.aria', { name: workerName })}
      >
        <MoreHorizontal size={16} className="text-sc-text-muted" aria-hidden />
      </button>

      {copied ? (
        <span role="status" className="sr-only">
          {t('workers.list.rowActions.copied')}
        </span>
      ) : null}

      {menu ? createPortal(menu, document.body) : null}

      {revokeDialogOpen ? (
        <RevokeWorkerDialog
          name={workerName}
          isSubmitting={revoke.isPending}
          error={revoke.error}
          onClose={closeRevokeDialog}
          onConfirm={confirmRevoke}
        />
      ) : null}
    </>
  );
}
