import { BrowserRouter, Route, Routes } from 'react-router-dom';

import { LoginPage } from './features/auth';
import { HomePage } from './routes/HomePage';
import { InstallPage } from './routes/InstallPage';

export function App() {
  return (
    <BrowserRouter>
      <Routes>
        <Route path="/" element={<HomePage />} />
        <Route path="/install" element={<InstallPage />} />
        <Route path="/login" element={<LoginPage />} />
      </Routes>
    </BrowserRouter>
  );
}
