import { Loader2 } from 'lucide-react';
import { Navigate, Outlet, useLocation } from 'react-router-dom';

import { useMe } from '@/features/auth/api/useMe';
import { useRealtime } from '@/features/realtime/useRealtime';
import { readAuthToken } from '@/lib/authToken';

import { AppTopbar } from './AppTopbar';
import { Sidebar } from './Sidebar';

export function AppLayout() {
  const location = useLocation();
  const token = readAuthToken();
  const me = useMe();
  useRealtime();

  if (token === null) {
    return <Navigate to="/login" replace state={{ from: location.pathname }} />;
  }

  if (me.isPending) {
    return (
      <div
        role="status"
        className="flex min-h-screen items-center justify-center bg-sc-bg text-sc-text-muted"
      >
        <Loader2 size={18} className="mr-2 animate-spin" aria-hidden />
        <span>Loading…</span>
      </div>
    );
  }

  if (me.isError || !me.data) {
    return <Navigate to="/login" replace />;
  }

  return (
    <div className="flex h-screen overflow-hidden bg-sc-bg text-sc-text">
      <Sidebar />
      <div className="flex min-w-0 flex-1 flex-col">
        <AppTopbar user={me.data} />
        <main className="flex-1 overflow-y-auto p-7">
          <Outlet />
        </main>
      </div>
    </div>
  );
}
