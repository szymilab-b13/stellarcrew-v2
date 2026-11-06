import { BrowserRouter, Navigate, Route, Routes } from 'react-router-dom';

import { AppLayout } from './components/app/AppLayout';
import { LoginPage } from './features/auth';
import { CurrentWorkspaceGuard } from './features/workspaces/components/CurrentWorkspaceGuard';
import { HomePage } from './routes/HomePage';
import { InstallPage } from './routes/InstallPage';
import { DashboardPage } from './routes/DashboardPage';
import { WorkspacesPage } from './routes/WorkspacesPage';

export function App() {
  return (
    <BrowserRouter>
      <Routes>
        <Route path="/" element={<HomePage />} />
        <Route path="/install" element={<InstallPage />} />
        <Route path="/login" element={<LoginPage />} />
        <Route element={<AppLayout />}>
          <Route path="/workspaces" element={<WorkspacesPage />} />
          <Route element={<CurrentWorkspaceGuard />}>
            <Route path="/dashboard" element={<DashboardPage />} />
          </Route>
        </Route>
        <Route path="*" element={<Navigate to="/" replace />} />
      </Routes>
    </BrowserRouter>
  );
}
