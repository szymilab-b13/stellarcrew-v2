import { Loader2 } from 'lucide-react';
import { useEffect } from 'react';
import { useTranslation } from 'react-i18next';
import { Navigate, Outlet, useLocation } from 'react-router-dom';

import { useWorkspacesList } from '../api/useWorkspaces';
import { useCurrentWorkspace } from '../state/useCurrentWorkspace';

export function CurrentWorkspaceGuard() {
  const { t } = useTranslation();
  const location = useLocation();
  const list = useWorkspacesList();
  const currentWorkspaceId = useCurrentWorkspace((state) => state.currentWorkspaceId);
  const setCurrentWorkspaceId = useCurrentWorkspace((state) => state.setCurrentWorkspaceId);

  const workspaces = list.data;
  const selectionIsStale =
    currentWorkspaceId !== null &&
    workspaces !== undefined &&
    !workspaces.some((workspace) => workspace.id === currentWorkspaceId);
  const autoSelectId =
    currentWorkspaceId === null && workspaces !== undefined && workspaces.length === 1
      ? workspaces[0].id
      : null;

  useEffect(() => {
    if (selectionIsStale) {
      setCurrentWorkspaceId(null);
      return;
    }
    if (autoSelectId !== null) {
      setCurrentWorkspaceId(autoSelectId);
    }
  }, [autoSelectId, selectionIsStale, setCurrentWorkspaceId]);

  if (list.isPending) {
    return (
      <div
        role="status"
        className="flex flex-1 items-center justify-center gap-2 text-sm text-sc-text-muted"
      >
        <Loader2 size={16} className="animate-spin" aria-hidden />
        <span>{t('workspaces.loading')}</span>
      </div>
    );
  }

  if (list.isError) {
    return (
      <p
        role="alert"
        className="mx-auto mt-10 max-w-md rounded-md border border-red-300 bg-red-50 px-3 py-2 text-sm text-red-900"
      >
        {t('workspaces.loadError')}
      </p>
    );
  }

  const effectiveWorkspaceId = selectionIsStale ? null : (currentWorkspaceId ?? autoSelectId);

  if (effectiveWorkspaceId === null) {
    return <Navigate to="/workspaces" replace state={{ from: location.pathname }} />;
  }

  return <Outlet />;
}
