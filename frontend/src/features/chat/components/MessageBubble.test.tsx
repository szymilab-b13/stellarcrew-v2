import { render, screen } from '@testing-library/react';
import { describe, expect, it } from 'vitest';

import '@/i18n/i18n';

import type { ChatMessage } from '../types';
import { MessageBubble } from './MessageBubble';

const baseMessage: ChatMessage = {
  id: 'm00000000-0000-0000-0000-000000000001',
  sessionId: 'ssssssss-ssss-4sss-8sss-ssssssssssss',
  threadId: 'tttttttt-tttt-4ttt-8ttt-tttttttttttt',
  senderType: 'user',
  senderId: 'user-id-1',
  recipientType: 'agent',
  recipientId: 'agent-id-1',
  subject: 'Deploy CHAT-FOUNDATION',
  priority: 'P2',
  content: 'Hej, odpal deploy.',
  contentType: 'text',
  metadata: {},
  replyToMessageId: null,
  engineId: 'engine-id',
  engineType: 'cli_terminal',
  classifiedBy: 'sender',
  classificationConfidence: null,
  createdAt: '2026-11-18T10:40:00+00:00',
};

describe('MessageBubble', () => {
  it('renders user messages aligned to the right with the emerald bubble style', () => {
    render(<MessageBubble message={baseMessage} />);

    const bubble = screen.getByRole('article');
    expect(bubble).toHaveAttribute('data-sender-type', 'user');
    expect(bubble.parentElement?.className).toContain('justify-end');
    expect(bubble.className).toContain('bg-emerald-500/15');
  });

  it('renders agent messages aligned to the left tinted with the per-agent color', () => {
    render(
      <MessageBubble
        message={{ ...baseMessage, senderType: 'agent', content: 'Deploy odpalony.' }}
        agentName="Hefajstos"
        agentColor="#06b6d4"
      />,
    );

    const bubble = screen.getByRole('article') as HTMLElement;
    expect(bubble).toHaveAttribute('data-sender-type', 'agent');
    expect(bubble.parentElement?.className).toContain('justify-start');
    expect(bubble.style.backgroundColor).toBe('rgba(6, 182, 212, 0.2)');
    expect(bubble.style.borderColor).toBe('rgba(6, 182, 212, 0.4)');
    expect(screen.getByText('Hefajstos')).toBeInTheDocument();
    expect(screen.getByText('Deploy odpalony.')).toBeInTheDocument();
  });

  it('shows the message content and a short timestamp', () => {
    render(<MessageBubble message={baseMessage} />);

    expect(screen.getByText('Hej, odpal deploy.')).toBeInTheDocument();
    const timestamp = screen.getByRole('article').querySelector('p.text-right');
    expect(timestamp?.textContent).toMatch(/\d{1,2}:\d{2}/);
  });

  it('does not show the subject or priority label in the bubble', () => {
    render(<MessageBubble message={baseMessage} />);

    expect(screen.queryByText('Deploy CHAT-FOUNDATION')).not.toBeInTheDocument();
    expect(screen.queryByText(/^P2$/)).not.toBeInTheDocument();
  });
});
