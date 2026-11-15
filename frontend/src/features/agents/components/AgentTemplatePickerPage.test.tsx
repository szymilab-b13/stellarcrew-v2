import { QueryClient, QueryClientProvider } from '@tanstack/react-query';
import { render, screen } from '@testing-library/react';
import type { ReactNode } from 'react';
import { MemoryRouter, Route, Routes } from 'react-router-dom';
import { afterEach, beforeEach, describe, expect, it, vi } from 'vitest';

import '@/i18n/i18n';
import { clearAuthToken, writeAuthToken } from '@/lib/authToken';

import type { AgentTemplate } from '../types';
import { AgentTemplatePickerPage } from './AgentTemplatePickerPage';

function mockResponse(body: unknown, status = 200): Response {
  return new Response(body == null ? null : JSON.stringify(body), {
    status,
    headers: { 'content-type': 'application/json' },
  });
}

function makeFetch(body: unknown, status = 200) {
  return vi.fn(async (input: RequestInfo | URL) => {
    const url = typeof input === 'string' ? input : input.toString();
    if (url === '/api/agent-templates') {
      return mockResponse(body, status);
    }
    return mockResponse({}, 404);
  });
}

function renderPage() {
  const queryClient = new QueryClient({
    defaultOptions: { queries: { retry: false }, mutations: { retry: false } },
  });
  function Wrapper({ children }: { children: ReactNode }) {
    return (
      <QueryClientProvider client={queryClient}>
        <MemoryRouter initialEntries={['/agents/new/template']}>
          <Routes>
            <Route path="/agents" element={<div>list</div>} />
            <Route path="/agents/new/template" element={children} />
          </Routes>
        </MemoryRouter>
      </QueryClientProvider>
    );
  }
  return render(<AgentTemplatePickerPage />, { wrapper: Wrapper });
}

const templates: AgentTemplate[] = [
  {
    id: '11111111-1111-4111-8111-111111111111',
    slug: 'maja',
    name: 'Maja',
    role: 'Project Manager',
    roleDescription: 'Keeps the roadmap on track.',
    expertise: '',
    workflow: '',
    style: '',
    outputFormat: '',
    createdAt: '2026-11-01T10:00:00+00:00',
    updatedAt: '2026-11-01T10:00:00+00:00',
  },
  {
    id: '22222222-2222-4222-8222-222222222222',
    slug: 'viktor',
    name: 'Viktor Builder',
    role: 'Engineer',
    roleDescription: 'Ships vertical slices with tests.',
    expertise: '',
    workflow: '',
    style: '',
    outputFormat: '',
    createdAt: '2026-11-02T10:00:00+00:00',
    updatedAt: '2026-11-02T10:00:00+00:00',
  },
];

describe('AgentTemplatePickerPage', () => {
  beforeEach(() => {
    writeAuthToken('test-token');
  });

  afterEach(() => {
    clearAuthToken();
    vi.unstubAllGlobals();
    vi.restoreAllMocks();
  });

  it('should render the page heading and back link to /agents', async () => {
    vi.stubGlobal('fetch', makeFetch({ items: [] }));

    renderPage();

    expect(
      await screen.findByRole('heading', { level: 1, name: /pick a template/i }),
    ).toBeInTheDocument();
    expect(screen.getByRole('link', { name: /back to agents/i })).toHaveAttribute(
      'href',
      '/agents',
    );
  });

  it('should render a tile for each returned template', async () => {
    vi.stubGlobal('fetch', makeFetch({ items: templates }));

    renderPage();

    expect(await screen.findByRole('heading', { level: 2, name: 'Maja' })).toBeInTheDocument();
    expect(screen.getByRole('heading', { level: 2, name: 'Viktor Builder' })).toBeInTheDocument();
    expect(screen.getByRole('link', { name: /open maja/i })).toHaveAttribute(
      'href',
      '/agents/new/template/maja',
    );
    expect(screen.getByRole('link', { name: /open viktor builder/i })).toHaveAttribute(
      'href',
      '/agents/new/template/viktor',
    );
  });

  it('should render an error alert when the templates request fails', async () => {
    vi.stubGlobal('fetch', makeFetch(null, 500));

    renderPage();

    const alert = await screen.findByRole('alert');
    expect(alert).toHaveTextContent(/could not load templates/i);
  });

  it('should render an empty state when no templates are returned', async () => {
    vi.stubGlobal('fetch', makeFetch({ items: [] }));

    renderPage();

    expect(await screen.findByText(/no templates available/i)).toBeInTheDocument();
  });
});
