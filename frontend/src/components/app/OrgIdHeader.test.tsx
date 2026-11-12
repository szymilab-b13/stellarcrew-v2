import { QueryClient, QueryClientProvider } from '@tanstack/react-query';
import { fireEvent, render, screen, waitFor } from '@testing-library/react';
import type { ReactNode } from 'react';
import { afterEach, beforeEach, describe, expect, it, vi } from 'vitest';

import { clearAuthToken, writeAuthToken } from '@/lib/authToken';

import { OrgIdHeader } from './OrgIdHeader';

function mockResponse(status: number, body: unknown = null): Response {
  return new Response(body === null ? null : JSON.stringify(body), {
    status,
    headers: { 'content-type': 'application/json' },
  });
}

function renderHeader(collapsed = false) {
  const client = new QueryClient({ defaultOptions: { queries: { retry: false } } });
  function Wrapper({ children }: { children: ReactNode }) {
    return <QueryClientProvider client={client}>{children}</QueryClientProvider>;
  }
  return render(<OrgIdHeader collapsed={collapsed} />, { wrapper: Wrapper });
}

const fullOrg = {
  id: 'abcd1234-aaaa-bbbb-cccc-111122223333',
  name: "Tomek's Organization",
  slug: 'tomeks-organization',
  ownerUserId: '00000000-0000-0000-0000-000000000001',
  createdAt: '2026-11-10T10:00:00+00:00',
  updatedAt: '2026-11-10T10:00:00+00:00',
};

describe('OrgIdHeader', () => {
  beforeEach(() => {
    writeAuthToken('test-token');
    vi.stubGlobal('fetch', vi.fn());
  });

  afterEach(() => {
    clearAuthToken();
    vi.unstubAllGlobals();
    vi.restoreAllMocks();
  });

  it('should render shortened UUID (8 chars, hyphens stripped) and label when organization loads', async () => {
    vi.mocked(fetch).mockResolvedValueOnce(mockResponse(200, fullOrg));

    renderHeader();

    expect(await screen.findByText('abcd1234')).toBeInTheDocument();
    expect(screen.getByText(/organization id/i)).toBeInTheDocument();
  });

  it('should copy the full UUID to the clipboard and show copied feedback on click', async () => {
    vi.mocked(fetch).mockResolvedValueOnce(mockResponse(200, fullOrg));
    const writeText = vi.fn().mockResolvedValue(undefined);
    Object.defineProperty(window.navigator, 'clipboard', {
      value: { writeText },
      configurable: true,
      writable: true,
    });

    renderHeader();

    fireEvent.click(await screen.findByRole('button', { name: /copy organization id/i }));

    await waitFor(() => expect(writeText).toHaveBeenCalledWith(fullOrg.id));
    await waitFor(() => expect(screen.getByText(/copied/i)).toBeInTheDocument());
  });

  it('should render nothing when the user has no organization (404)', async () => {
    vi.mocked(fetch).mockResolvedValueOnce(mockResponse(404));

    const { container } = renderHeader();

    await waitFor(() => expect(container.firstChild).toBeNull());
  });

  it('should render a 2-letter tile with full UUID in title when collapsed', async () => {
    vi.mocked(fetch).mockResolvedValueOnce(mockResponse(200, fullOrg));

    renderHeader(true);

    const tile = await screen.findByTitle(new RegExp(fullOrg.id, 'i'));
    expect(tile).toHaveTextContent('AB');
  });
});
