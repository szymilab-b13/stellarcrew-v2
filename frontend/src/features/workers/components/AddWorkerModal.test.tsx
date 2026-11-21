import { QueryClient, QueryClientProvider } from '@tanstack/react-query';
import { render, screen } from '@testing-library/react';
import userEvent from '@testing-library/user-event';
import type { ReactNode } from 'react';
import { afterEach, beforeEach, describe, expect, it, vi } from 'vitest';

import '@/i18n/i18n';
import { clearAuthToken, writeAuthToken } from '@/lib/authToken';

import { AddWorkerModal } from './AddWorkerModal';

function mockResponse(body: unknown, status = 200): Response {
  return new Response(body == null ? null : JSON.stringify(body), {
    status,
    headers: { 'content-type': 'application/json' },
  });
}

function renderModal(onClose = vi.fn()) {
  const queryClient = new QueryClient({
    defaultOptions: { queries: { retry: false }, mutations: { retry: false } },
  });
  function Wrapper({ children }: { children: ReactNode }) {
    return <QueryClientProvider client={queryClient}>{children}</QueryClientProvider>;
  }
  const result = render(<AddWorkerModal onClose={onClose} />, { wrapper: Wrapper });
  return { ...result, onClose };
}

function stubClipboard(writeText: (text: string) => Promise<void>) {
  Object.defineProperty(navigator, 'clipboard', {
    configurable: true,
    value: { writeText },
  });
}

describe('AddWorkerModal', () => {
  beforeEach(() => {
    writeAuthToken('test-token');
    stubClipboard(vi.fn().mockResolvedValue(undefined));
  });

  afterEach(() => {
    clearAuthToken();
    vi.restoreAllMocks();
  });

  it('shows intro state with Generate button before a code is created', () => {
    renderModal();

    expect(
      screen.getByRole('button', { name: /Wygeneruj kod parowania|Generate pairing code/i }),
    ).toBeInTheDocument();
    expect(screen.queryByText(/ABCD-/)).not.toBeInTheDocument();
  });

  it('renders the pairing code and install command after generation', async () => {
    const user = userEvent.setup();
    const expiresAt = new Date(Date.now() + 10 * 60 * 1000).toISOString();
    vi.spyOn(globalThis, 'fetch').mockResolvedValue(
      mockResponse(
        {
          code: 'ABCD-2345',
          expiresAt,
          pairCommand:
            'curl -fsSL http://localhost:8000/install-worker.sh | bash -s -- --code ABCD-2345',
        },
        201,
      ),
    );

    renderModal();
    await user.click(
      screen.getByRole('button', { name: /Wygeneruj kod parowania|Generate pairing code/i }),
    );

    expect(await screen.findByText('ABCD-2345')).toBeInTheDocument();
    expect(
      screen.getByText(
        /curl -fsSL http:\/\/localhost:8000\/install-worker.sh \| bash -s -- --code ABCD-2345/,
      ),
    ).toBeInTheDocument();
  });

  it('copies the install command to clipboard when copy button is clicked', async () => {
    const user = userEvent.setup();
    const writeText = vi.fn().mockResolvedValue(undefined);
    stubClipboard(writeText);
    vi.spyOn(globalThis, 'fetch').mockResolvedValue(
      mockResponse(
        {
          code: 'ABCD-2345',
          expiresAt: new Date(Date.now() + 10 * 60 * 1000).toISOString(),
          pairCommand: 'curl ... --code ABCD-2345',
        },
        201,
      ),
    );

    renderModal();
    await user.click(
      screen.getByRole('button', { name: /Wygeneruj kod parowania|Generate pairing code/i }),
    );
    await screen.findByText('ABCD-2345');
    await user.click(screen.getByRole('button', { name: /Skopiuj komendę|Copy install command/i }));

    expect(writeText).toHaveBeenCalledWith('curl ... --code ABCD-2345');
  });

  it('calls onClose when Escape is pressed', async () => {
    const user = userEvent.setup();
    const { onClose } = renderModal();

    await user.keyboard('{Escape}');

    expect(onClose).toHaveBeenCalled();
  });

  it('shows an error message when code generation fails', async () => {
    const user = userEvent.setup();
    vi.spyOn(globalThis, 'fetch').mockResolvedValue(mockResponse({ message: 'nope' }, 500));

    renderModal();
    await user.click(
      screen.getByRole('button', { name: /Wygeneruj kod parowania|Generate pairing code/i }),
    );

    expect(
      await screen.findByText(/Nie udało się wygenerować kodu|Could not generate pairing code/i),
    ).toBeInTheDocument();
  });
});
