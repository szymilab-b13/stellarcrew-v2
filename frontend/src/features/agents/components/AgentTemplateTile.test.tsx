import { render, screen } from '@testing-library/react';
import { MemoryRouter } from 'react-router-dom';
import { describe, expect, it } from 'vitest';

import '@/i18n/i18n';

import type { AgentTemplate } from '../types';
import { AgentTemplateTile } from './AgentTemplateTile';

const template: AgentTemplate = {
  id: '44444444-4444-4444-8444-444444444444',
  slug: 'maja',
  name: 'Maja',
  role: 'Project Manager',
  roleDescription: 'Keeps the roadmap on track and nudges the team toward delivery.',
  expertise: '',
  workflow: '',
  style: '',
  outputFormat: '',
  createdAt: '2026-11-15T10:00:00+00:00',
  updatedAt: '2026-11-15T10:00:00+00:00',
};

function renderTile(override: Partial<AgentTemplate> = {}) {
  return render(
    <MemoryRouter>
      <AgentTemplateTile template={{ ...template, ...override }} />
    </MemoryRouter>,
  );
}

describe('AgentTemplateTile', () => {
  it('should render the template name, role and role description', () => {
    renderTile();

    expect(screen.getByRole('heading', { level: 2, name: 'Maja' })).toBeInTheDocument();
    expect(screen.getByText('Project Manager')).toBeInTheDocument();
    expect(screen.getByText(/Keeps the roadmap on track/i)).toBeInTheDocument();
  });

  it('should render initials derived from the template name', () => {
    renderTile();

    expect(screen.getByText('MA')).toBeInTheDocument();
  });

  it('should link to /agents/new/template/:slug', () => {
    renderTile();

    const link = screen.getByRole('link', { name: /open maja/i });
    expect(link).toHaveAttribute('href', '/agents/new/template/maja');
  });

  it('should omit the role description paragraph when empty', () => {
    renderTile({ roleDescription: '' });

    expect(screen.queryByText(/Keeps the roadmap on track/i)).not.toBeInTheDocument();
  });
});
