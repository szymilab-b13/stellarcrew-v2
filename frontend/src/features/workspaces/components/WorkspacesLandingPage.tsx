import { Loader2 } from 'lucide-react';
import { useMemo, useState } from 'react';
import { useTranslation } from 'react-i18next';

import { usePageBreadcrumbs } from '@/components/app/useBreadcrumbs';

import {
  useCreateWorkspace,
  useDeleteWorkspace,
  useUpdateWorkspace,
  useWorkspacesList,
} from '../api/useWorkspaces';
import type { Workspace } from '../types';
import { AddWorkspaceTile } from './AddWorkspaceTile';
import { DeleteConfirmDialog } from './DeleteConfirmDialog';
import { WorkspaceDialog, type WorkspaceDialogMode } from './WorkspaceDialog';
import { WorkspaceTile } from './WorkspaceTile';

interface DialogState {
  mode: WorkspaceDialogMode;
  workspace: Workspace | null;
}

export function WorkspacesLandingPage() {
  const { t } = useTranslation();
  const list = useWorkspacesList();
  const createMutation = useCreateWorkspace();
  const updateMutation = useUpdateWorkspace();
  const deleteMutation = useDeleteWorkspace();

  const breadcrumbs = useMemo(() => [{ label: t('workspaces.breadcrumb') }], [t]);
  usePageBreadcrumbs(breadcrumbs);

  const [dialog, setDialog] = useState<DialogState | null>(null);
  const [toDelete, setToDelete] = useState<Workspace | null>(null);

  function openCreate() {
    createMutation.reset();
    setDialog({ mode: 'create', workspace: null });
  }

  function openEdit(workspace: Workspace) {
    updateMutation.reset();
    setDialog({ mode: 'edit', workspace });
  }

  function openDelete(workspace: Workspace) {
    deleteMutation.reset();
    setToDelete(workspace);
  }

  function closeDialog() {
    setDialog(null);
  }

  function closeDelete() {
    setToDelete(null);
  }

  function handleDialogSubmit(values: { name: string; iconColor: string; iconInitials: string }) {
    if (!dialog) return;
    if (dialog.mode === 'create') {
      createMutation.mutate(values, { onSuccess: closeDialog });
      return;
    }
    if (dialog.workspace) {
      updateMutation.mutate({ id: dialog.workspace.id, input: values }, { onSuccess: closeDialog });
    }
  }

  function handleDeleteConfirm() {
    if (!toDelete) return;
    deleteMutation.mutate(toDelete.id, { onSuccess: closeDelete });
  }

  return (
    <div className="flex w-full flex-col gap-6">
      <header className="flex flex-col gap-2">
        <h1 className="text-3xl font-semibold tracking-tight">{t('workspaces.title')}</h1>
        <p className="text-sm text-sc-text-muted">{t('workspaces.subtitle')}</p>
      </header>

      {list.isPending ? (
        <p className="flex items-center gap-2 text-sm text-sc-text-muted">
          <Loader2 className="animate-spin" size={14} aria-hidden />
          {t('workspaces.loading')}
        </p>
      ) : null}

      {list.isError ? (
        <p
          role="alert"
          className="rounded-md border border-red-300 bg-red-50 px-3 py-2 text-sm text-red-900"
        >
          {t('workspaces.loadError')}
        </p>
      ) : null}

      {list.data ? (
        <section
          aria-label={t('workspaces.listAria')}
          className="grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4"
        >
          {list.data.map((workspace) => (
            <WorkspaceTile
              key={workspace.id}
              workspace={workspace}
              onEdit={openEdit}
              onDelete={openDelete}
            />
          ))}
          <AddWorkspaceTile onClick={openCreate} />
        </section>
      ) : null}

      {dialog ? (
        <WorkspaceDialog
          mode={dialog.mode}
          workspace={dialog.workspace}
          isSubmitting={
            dialog.mode === 'create' ? createMutation.isPending : updateMutation.isPending
          }
          error={dialog.mode === 'create' ? createMutation.error : updateMutation.error}
          onClose={closeDialog}
          onSubmit={handleDialogSubmit}
        />
      ) : null}

      {toDelete ? (
        <DeleteConfirmDialog
          workspace={toDelete}
          isSubmitting={deleteMutation.isPending}
          error={deleteMutation.error}
          onClose={closeDelete}
          onConfirm={handleDeleteConfirm}
        />
      ) : null}
    </div>
  );
}
