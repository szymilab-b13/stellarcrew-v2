import { Loader2 } from 'lucide-react';
import { useTranslation } from 'react-i18next';
import { Navigate, Outlet } from 'react-router-dom';

import { useMe } from '@/features/auth/api/useMe';

export function RequireSuperAdmin() {
  const { t } = useTranslation();
  const me = useMe();

  if (me.isPending) {
    return (
      <div
        role="status"
        className="flex flex-1 items-center justify-center gap-2 text-sm text-sc-text-muted"
      >
        <Loader2 size={16} className="animate-spin" aria-hidden />
        <span>{t('sidebar.loading')}</span>
      </div>
    );
  }

  if (me.isError || !me.data) {
    return <Navigate to="/login" replace />;
  }

  if (me.data.role !== 'admin') {
    return <Navigate to="/dashboard" replace />;
  }

  return <Outlet />;
}
