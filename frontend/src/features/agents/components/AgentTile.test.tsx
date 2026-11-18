import { render, screen } from '@testing-library/react';
import { MemoryRouter } from 'react-router-dom';
import { describe, expect, it } from 'vitest';

import '@/i18n/i18n';

import type { Agent } from '../types';
import { AgentTile } from './AgentTile';

const agent: Agent = {
  id: '11111111-1111-4111-8111-111111111111',
  organizationId: 'org-1',
  ownerUserId: 'user-1',
  sourceTemplateId: null,
  slug: 'builder-bot',
  name: 'Builder Bot',
  role: 'Autonomous builder',
  roleDescription: 'Ships tasks end-to-end.',
  expertise: 'Symfony',
  workflow: 'TDD',
  style: 'Terse',
  outputFormat: 'Markdown',
  isActive: true,
  color: '#6366f1',
  createdAt: '2026-11-15T10:00:00+00:00',
  updatedAt: '2026-11-15T10:00:00+00:00',
};

function renderTile(overrides?: Partial<Agent>) {
  return render(
    <MemoryRouter>
      <AgentTile agent={{ ...agent, ...overrides }} />
    </MemoryRouter>,
  );
}

describe('AgentTile', () => {
  it('should render the agent name and role', () => {
    renderTile();

    expect(screen.getByRole('heading', { level: 2, name: 'Builder Bot' })).toBeInTheDocument();
    expect(screen.getByText('Autonomous builder')).toBeInTheDocument();
  });

  it('should render a "from template" badge when sourceTemplateId is present', () => {
    renderTile({ sourceTemplateId: 'tpl-1' });

    expect(screen.getByText(/From template/i)).toBeInTheDocument();
  });

  it('should skip the "from template" badge when sourceTemplateId is null', () => {
    renderTile({ sourceTemplateId: null });

    expect(screen.queryByText(/From template/i)).not.toBeInTheDocument();
  });

  it('should render a formatted created date', () => {
    renderTile();

    expect(screen.getByText(/Created/)).toBeInTheDocument();
  });

  it('should expose an aria-label with the agent name on the card', () => {
    renderTile();

    expect(screen.getByRole('group', { name: /Builder Bot/ })).toBeInTheDocument();
  });

  it('should link to the agent overview page', () => {
    renderTile();

    const link = screen.getByRole('link', { name: /Open Builder Bot/ });
    expect(link).toHaveAttribute('href', '/agents/11111111-1111-4111-8111-111111111111/overview');
  });
});
