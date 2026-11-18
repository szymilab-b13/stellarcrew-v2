import { render, screen } from '@testing-library/react';
import { describe, expect, it } from 'vitest';

import '@/i18n/i18n';

import { ChatHeader } from './ChatHeader';

const conversation = {
  agentId: 'aaaaaaaa-aaaa-4aaa-8aaa-aaaaaaaaaaaa',
  agentName: 'Hefajstos Builder',
  agentSlug: 'hefajstos',
  agentRole: 'Autonomous Builder',
  agentColor: '#06b6d4',
  lastMessagePreview: 'test',
  lastMessageAt: '2026-11-18T07:30:00+00:00',
  threadCount: 3,
  isNew: false,
};

describe('ChatHeader', () => {
  it('renders the agent name and role', () => {
    render(<ChatHeader conversation={conversation} onOpenSettings={() => undefined} />);

    expect(screen.getByText('Hefajstos Builder')).toBeInTheDocument();
    expect(screen.getByText('Autonomous Builder')).toBeInTheDocument();
  });

  it('applies the per-agent color to the avatar', () => {
    const { container } = render(
      <ChatHeader conversation={conversation} onOpenSettings={() => undefined} />,
    );

    const avatar = container.querySelector<HTMLElement>('[style*="background"]');
    expect(avatar?.style.backgroundColor).toBe('rgba(6, 182, 212, 0.2)');
    expect(avatar?.style.color).toBe('rgb(6, 182, 212)');
  });
});
