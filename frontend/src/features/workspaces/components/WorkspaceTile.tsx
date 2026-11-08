import { Pencil, Trash2, Users } from 'lucide-react';
import { useTranslation } from 'react-i18next';
import { Link } from 'react-router-dom';

import { RolePill } from '@/features/user-management/components/RolePill';

import { findColorOption, type Workspace } from '../types';

interface WorkspaceTileProps {
  workspace: Workspace;
  onEdit: (workspace: Workspace) => void;
  onDelete: (workspace: Workspace) => void;
}

export function WorkspaceTile({ workspace, onEdit, onDelete }: WorkspaceTileProps) {
  const { t } = useTranslation();
  const color = findColorOption(workspace.iconColor);

  return (
    <article
      className="group relative flex flex-col gap-4 rounded-xl border border-sc-border bg-sc-bg-card p-4 transition-colors hover:border-sc-text-muted"
      aria-label={t('workspaces.tile.aria', { name: workspace.name })}
    >
      <div className="flex items-center gap-3">
        <span
          className={`inline-flex h-12 w-12 flex-none items-center justify-center rounded-lg text-base font-semibold ${color.bgClass} ${color.textClass}`}
          aria-hidden
        >
          {workspace.iconInitials}
        </span>
        <div className="flex min-w-0 flex-col gap-1">
          <h2 className="truncate text-base font-semibold text-sc-text">{workspace.name}</h2>
          <p className="truncate text-xs text-sc-text-muted">{workspace.slug}</p>
          {workspace.role ? (
            <div>
              <RolePill role={workspace.role} size="sm" />
            </div>
          ) : null}
        </div>
      </div>

      <div className="flex items-center justify-end gap-1 opacity-0 transition-opacity group-hover:opacity-100 focus-within:opacity-100">
        <Link
          to={`/workspace/${workspace.id}/settings/members`}
          aria-label={t('workspaces.tile.membersAria', { name: workspace.name })}
          title={t('workspaces.tile.members')}
          className="inline-flex h-8 w-8 items-center justify-center rounded-md text-sc-text-muted transition-colors hover:bg-sc-bg hover:text-sc-text"
        >
          <Users size={14} aria-hidden />
        </Link>
        <button
          type="button"
          onClick={() => onEdit(workspace)}
          aria-label={t('workspaces.tile.editAria', { name: workspace.name })}
          title={t('workspaces.tile.edit')}
          className="inline-flex h-8 w-8 items-center justify-center rounded-md text-sc-text-muted transition-colors hover:bg-sc-bg hover:text-sc-text"
        >
          <Pencil size={14} aria-hidden />
        </button>
        <button
          type="button"
          onClick={() => onDelete(workspace)}
          aria-label={t('workspaces.tile.deleteAria', { name: workspace.name })}
          title={t('workspaces.tile.delete')}
          className="inline-flex h-8 w-8 items-center justify-center rounded-md text-sc-text-muted transition-colors hover:bg-red-50 hover:text-red-600"
        >
          <Trash2 size={14} aria-hidden />
        </button>
      </div>
    </article>
  );
}
