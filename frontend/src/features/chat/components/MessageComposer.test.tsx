import { QueryClient, QueryClientProvider } from '@tanstack/react-query';
import { render, screen, waitFor } from '@testing-library/react';
import userEvent from '@testing-library/user-event';
import type { ReactNode } from 'react';
import { afterEach, beforeEach, describe, expect, it, vi } from 'vitest';

import '@/i18n/i18n';
import { clearAuthToken, writeAuthToken } from '@/lib/authToken';

import { MessageComposer } from './MessageComposer';

const sessionId = '11111111-1111-4111-8111-111111111111';
const threadId = '22222222-2222-4222-8222-222222222222';
const senderId = '44444444-4444-4444-8444-444444444444';
const engineId = '66666666-6666-4666-8666-666666666666';
const messageId = '33333333-3333-4333-8333-333333333333';

const sampleMessage = {
  id: messageId,
  sessionId,
  threadId,
  senderType: 'user' as const,
  senderId,
  recipientType: 'agent' as const,
  recipientId: engineId,
  subject: 'Hello',
  priority: 'P2' as const,
  content: 'Hi there',
  contentType: 'text' as const,
  metadata: {},
  replyToMessageId: null,
  engineId,
  engineType: 'cli_terminal' as const,
  classifiedBy: 'sender' as const,
  classificationConfidence: null,
  createdAt: '2026-11-16T10:10:00+00:00',
};

function mockResponse(body: unknown, status = 200): Response {
  return new Response(body == null ? null : JSON.stringify(body), {
    status,
    headers: { 'content-type': 'application/json' },
  });
}

function renderComposer() {
  const queryClient = new QueryClient({
    defaultOptions: { queries: { retry: false }, mutations: { retry: false } },
  });
  function Wrapper({ children }: { children: ReactNode }) {
    return <QueryClientProvider client={queryClient}>{children}</QueryClientProvider>;
  }
  return render(<MessageComposer sessionId={sessionId} threadId={threadId} senderId={senderId} />, {
    wrapper: Wrapper,
  });
}

beforeEach(() => {
  writeAuthToken('test-token');
  vi.stubGlobal('fetch', vi.fn());
});

afterEach(() => {
  clearAuthToken();
  vi.unstubAllGlobals();
  vi.restoreAllMocks();
});

describe('MessageComposer', () => {
  it('renders subject, content, priority, engineId and engineType fields', () => {
    renderComposer();

    expect(screen.getByLabelText('Subject')).toBeInTheDocument();
    expect(screen.getByLabelText('Content')).toBeInTheDocument();
    expect(screen.getByLabelText('Priority')).toBeInTheDocument();
    expect(screen.getByLabelText('Engine ID')).toBeInTheDocument();
    expect(screen.getByLabelText('Engine type')).toBeInTheDocument();
    expect(screen.getByRole('button', { name: 'Send' })).toBeInTheDocument();
  });

  it('shows a validation error when subject is empty on submit', async () => {
    renderComposer();

    await userEvent.type(screen.getByLabelText('Content'), 'Hi');
    await userEvent.type(screen.getByLabelText('Engine ID'), engineId);
    await userEvent.click(screen.getByRole('button', { name: 'Send' }));

    expect(await screen.findByText('Subject is required.')).toBeInTheDocument();
    expect(vi.mocked(fetch)).not.toHaveBeenCalled();
  });

  it('shows a validation error when subject exceeds 120 characters', async () => {
    renderComposer();

    await userEvent.type(screen.getByLabelText('Subject'), 'a'.repeat(121));
    await userEvent.type(screen.getByLabelText('Content'), 'Hi');
    await userEvent.type(screen.getByLabelText('Engine ID'), engineId);
    await userEvent.click(screen.getByRole('button', { name: 'Send' }));

    expect(await screen.findByText('Subject is too long (max 120).')).toBeInTheDocument();
    expect(vi.mocked(fetch)).not.toHaveBeenCalled();
  });

  it('shows a validation error when content is empty on submit', async () => {
    renderComposer();

    await userEvent.type(screen.getByLabelText('Subject'), 'Hello');
    await userEvent.type(screen.getByLabelText('Engine ID'), engineId);
    await userEvent.click(screen.getByRole('button', { name: 'Send' }));

    expect(await screen.findByText('Content is required.')).toBeInTheDocument();
    expect(vi.mocked(fetch)).not.toHaveBeenCalled();
  });

  it('shows a validation error when engineId is not a UUID', async () => {
    renderComposer();

    await userEvent.type(screen.getByLabelText('Subject'), 'Hello');
    await userEvent.type(screen.getByLabelText('Content'), 'Hi');
    await userEvent.type(screen.getByLabelText('Engine ID'), 'not-a-uuid');
    await userEvent.click(screen.getByRole('button', { name: 'Send' }));

    expect(await screen.findByText('Engine ID must be a UUID.')).toBeInTheDocument();
    expect(vi.mocked(fetch)).not.toHaveBeenCalled();
  });

  it('POSTs the envelope payload when the form is valid and resets the form on success', async () => {
    vi.mocked(fetch).mockResolvedValueOnce(mockResponse(sampleMessage, 201));

    renderComposer();

    const subject = screen.getByLabelText('Subject') as HTMLInputElement;
    const content = screen.getByLabelText('Content') as HTMLTextAreaElement;
    const engineIdInput = screen.getByLabelText('Engine ID') as HTMLInputElement;

    await userEvent.type(subject, 'Hello');
    await userEvent.type(content, 'Hi there');
    await userEvent.type(engineIdInput, engineId);
    await userEvent.click(screen.getByRole('button', { name: 'Send' }));

    await waitFor(() => expect(vi.mocked(fetch)).toHaveBeenCalledTimes(1));
    expect(vi.mocked(fetch).mock.calls[0]?.[0]).toBe('/api/chat/messages');
    const init = vi.mocked(fetch).mock.calls[0]?.[1] as RequestInit;
    expect(init.method).toBe('POST');
    expect(JSON.parse(init.body as string)).toEqual({
      sessionId,
      threadId,
      senderType: 'user',
      senderId,
      recipientType: 'agent',
      recipientId: engineId,
      subject: 'Hello',
      priority: 'P2',
      content: 'Hi there',
      contentType: 'text',
      metadata: {},
      replyToMessageId: null,
      engineId,
      engineType: 'cli_terminal',
      classifiedBy: 'sender',
      classificationConfidence: null,
    });

    await waitFor(() => expect(subject.value).toBe(''));
    expect(content.value).toBe('');
    expect(engineIdInput.value).toBe('');
  });

  it('sends the selected priority and engine type when the user changes the dropdowns', async () => {
    vi.mocked(fetch).mockResolvedValueOnce(mockResponse(sampleMessage, 201));

    renderComposer();

    await userEvent.type(screen.getByLabelText('Subject'), 'Alert');
    await userEvent.type(screen.getByLabelText('Content'), 'Urgent');
    await userEvent.type(screen.getByLabelText('Engine ID'), engineId);
    await userEvent.selectOptions(screen.getByLabelText('Priority'), 'P0');
    await userEvent.selectOptions(screen.getByLabelText('Engine type'), 'webhook');
    await userEvent.click(screen.getByRole('button', { name: 'Send' }));

    await waitFor(() => expect(vi.mocked(fetch)).toHaveBeenCalledTimes(1));
    const init = vi.mocked(fetch).mock.calls[0]?.[1] as RequestInit;
    const body = JSON.parse(init.body as string);
    expect(body.priority).toBe('P0');
    expect(body.engineType).toBe('webhook');
  });

  it('shows a generic server error when the API responds with a non-2xx status', async () => {
    vi.mocked(fetch).mockResolvedValueOnce(
      mockResponse({ message: 'Thread does not belong to session' }, 400),
    );

    renderComposer();

    await userEvent.type(screen.getByLabelText('Subject'), 'Hello');
    await userEvent.type(screen.getByLabelText('Content'), 'Hi');
    await userEvent.type(screen.getByLabelText('Engine ID'), engineId);
    await userEvent.click(screen.getByRole('button', { name: 'Send' }));

    expect(
      await screen.findByText('Could not send the message. Please try again.'),
    ).toBeInTheDocument();
  });
});
