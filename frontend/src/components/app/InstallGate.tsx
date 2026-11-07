import { Loader2 } from 'lucide-react';
import { type ReactNode } from 'react';
import { useTranslation } from 'react-i18next';
import { Navigate, useLocation } from 'react-router-dom';

import { useInstallStatus } from '@/features/install/api/useInstallStatus';

const INSTALL_PATH = '/install';

interface InstallGateProps {
  children: ReactNode;
}

export function InstallGate({ children }: InstallGateProps) {
  const { t } = useTranslation();
  const location = useLocation();
  const install = useInstallStatus();

  if (install.isPending) {
    return (
      <main
        role="status"
        aria-live="polite"
        className="flex min-h-screen items-center justify-center gap-2 bg-sc-bg text-sm text-sc-text-muted"
      >
        <Loader2 size={16} className="animate-spin" aria-hidden />
        <span>{t('common.loadingStatus')}</span>
      </main>
    );
  }

  if (install.isError) {
    return (
      <main
        role="alert"
        className="flex min-h-screen items-center justify-center bg-sc-bg px-6 text-sm text-sc-text-muted"
      >
        {t('api.unreachableBody')}
      </main>
    );
  }

  if (!install.data?.isInstalled && location.pathname !== INSTALL_PATH) {
    return <Navigate to={INSTALL_PATH} replace />;
  }

  return <>{children}</>;
}
