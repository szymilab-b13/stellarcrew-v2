import { BrowserRouter, Navigate, Route, Routes } from 'react-router-dom';

import { AppLayout } from './components/app/AppLayout';
import { InstallGate } from './components/app/InstallGate';
import { LoginPage } from './features/auth';
import { AdminAuditPage } from './features/audit';
import { AdminUserDetailPage, AdminUsersPage } from './features/user-management';
import { CurrentWorkspaceGuard } from './features/workspaces/components/CurrentWorkspaceGuard';
import { WorkspaceOverviewPage } from './features/workspaces/components/WorkspaceOverviewPage';
import { WorkspaceProjectsPage } from './features/workspaces/projects';
import { MembersSettingsPage } from './features/workspaces/settings';
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
            <Route path="/workspace/:wsId" element={<Navigate to="overview" replace />} />
            <Route path="/workspace/:wsId/overview" element={<WorkspaceOverviewPage />} />
            <Route path="/workspace/:wsId/projects" element={<WorkspaceProjectsPage />} />
            <Route path="/workspace/:wsId/settings/members" element={<MembersSettingsPage />} />
            <Route element={<CurrentWorkspaceGuard />}>
              <Route path="/dashboard" element={<DashboardPage />} />
            </Route>
            <Route element={<RequireSuperAdmin />}>
              <Route path="/admin/users" element={<AdminUsersPage />} />
              <Route path="/admin/users/:id" element={<AdminUserDetailPage />} />
              <Route path="/admin/audit" element={<AdminAuditPage />} />
            </Route>
          </Route>
          <Route path="*" element={<Navigate to="/" replace />} />
        </Routes>
      </InstallGate>
    </BrowserRouter>
  );
}
