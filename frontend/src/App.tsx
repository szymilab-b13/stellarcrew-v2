import { BrowserRouter, Navigate, Route, Routes } from 'react-router-dom';

import { AppLayout } from './components/app/AppLayout';
import { InstallGate } from './components/app/InstallGate';
import { LoginPage } from './features/auth';
import { AdminUsersPage } from './features/user-management';
import { CurrentWorkspaceGuard } from './features/workspaces/components/CurrentWorkspaceGuard';
import { HomePage } from './routes/HomePage';
import { InstallPage } from './routes/InstallPage';
import { DashboardPage } from './routes/DashboardPage';
import { WorkspacesPage } from './routes/WorkspacesPage';
import { RequireSuperAdmin } from './shared/guards/RequireSuperAdmin';

export function App() {
  return (
    <BrowserRouter>
      <InstallGate>
        <Routes>
          <Route path="/" element={<HomePage />} />
          <Route path="/install" element={<InstallPage />} />
          <Route path="/login" element={<LoginPage />} />
          <Route element={<AppLayout />}>
            <Route path="/workspaces" element={<WorkspacesPage />} />
            <Route element={<CurrentWorkspaceGuard />}>
              <Route path="/dashboard" element={<DashboardPage />} />
            </Route>
            <Route element={<RequireSuperAdmin />}>
              <Route path="/admin/users" element={<AdminUsersPage />} />
            </Route>
          </Route>
          <Route path="*" element={<Navigate to="/" replace />} />
        </Routes>
      </InstallGate>
    </BrowserRouter>
  );
}
