import { Navigate } from 'react-router-dom';

import { useInstallStatus } from '@/features/install/api/useInstallStatus';
import { readAuthToken } from '@/lib/authToken';

export function HomePage() {
  const install = useInstallStatus();

  if (install.isPending) {
    return (
      <main className="flex min-h-screen items-center justify-center bg-sc-bg text-sm text-sc-text-muted">
        Loading…
      </main>
    );
  }

  if (install.isError) {
    return (
      <main className="flex min-h-screen items-center justify-center bg-sc-bg text-sm text-sc-text-muted">
        Could not reach the API. Make sure the backend is running.
      </main>
    );
  }

  if (!install.data?.isInstalled) {
    return <Navigate to="/install" replace />;
  }

  if (readAuthToken() === null) {
    return <Navigate to="/login" replace />;
  }

  return <Navigate to="/dashboard" replace />;
}
