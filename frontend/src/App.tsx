import { BrowserRouter, Route, Routes } from 'react-router-dom';

import { HomePage } from './routes/HomePage';
import { InstallPage } from './routes/InstallPage';

export function App() {
  return (
    <BrowserRouter>
      <main className="min-h-screen bg-sc-bg px-6 py-12 text-sc-text">
        <div className="mx-auto flex max-w-3xl flex-col gap-6">
          <Routes>
            <Route path="/" element={<HomePage />} />
            <Route path="/install" element={<InstallPage />} />
          </Routes>
        </div>
      </main>
    </BrowserRouter>
  );
}
